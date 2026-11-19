<?php

namespace App\Filament\Streaming\Pages;

use App\Services\NontonAnimeIdScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnimeV5 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime V5';
    protected static ?string $title = 'Explore Anime V5 (NontonAnimeID)';
    protected static ?string $slug = 'anime-v5';
    protected static string $view = 'filament.streaming.pages.explore-anime-v5';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public string $search = '';
    public string $status = '';
    public string $order = 'latest';
    public bool $hasNextPage = false;
    public array $latestUpdates = [];

    protected function rememberCache(string $key, int $ttl, \Closure $callback)
    {
        $value = Cache::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value !== null && (!is_array($value) || !empty($value))) {
            Cache::put($key, $value, $ttl);
        }

        return $value;
    }

    public function mount(): void
    {
        $scraper = new NontonAnimeIdScraperService();

        // Load latest updates for homepage banner/shelf
        $homeData = $this->rememberCache('v5_na_home_data', 900, function () use ($scraper) {
            return $scraper->scrapeHomepage(1);
        }) ?? [];

        $this->latestUpdates = $homeData['latest'] ?? [];

        $this->loadAnimes();
    }

    public function getShowHomeProperty(): bool
    {
        return blank($this->search) && blank($this->status) && $this->order === 'latest' && $this->pageNumber === 1;
    }

    public function loadAnimes(): void
    {
        $scraper = new NontonAnimeIdScraperService();

        if (filled($this->search)) {
            $cacheKey = 'v5_na_search_' . md5($this->search) . "_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 1800, function () use ($scraper) {
                return $scraper->searchAnime($this->search, $this->pageNumber);
            }) ?? [];
            $this->hasNextPage = count($this->animes) >= 15;
        } else {
            $cacheKey = "v5_na_list_{$this->status}_{$this->order}_{$this->pageNumber}";
            $result = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->scrapeAnimeList($this->pageNumber, $this->status, $this->order);
            }) ?? ['items' => [], 'hasNext' => false];

            $this->animes = $result['items'] ?? [];
            $this->hasNextPage = (bool) ($result['hasNext'] ?? false);
        }
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedStatus(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->loadAnimes();
    }

    public function updatedOrder(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->loadAnimes();
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
        $this->loadAnimes();
    }

    public function previousPage(): void
    {
        if ($this->pageNumber > 1) {
            $this->pageNumber--;
            $this->loadAnimes();
        }
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->status = '';
        $this->order = 'latest';
        $this->pageNumber = 1;
        $this->loadAnimes();
    }
}
