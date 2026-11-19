<?php

namespace App\Filament\Streaming\Pages;

use App\Services\SamehadakuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnimeV4 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime V4';
    protected static ?string $title = 'Explore Anime V4';
    protected static ?string $slug = 'anime-v4';
    protected static string $view = 'filament.streaming.pages.explore-anime-v4';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';
    public array $ongoing = [];
    public array $topTen = [];

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
        $scraper = new SamehadakuScraperService();

        $this->genres = $this->rememberCache('v4_samehada_genres_list', 86400, function () use ($scraper) {
            return $scraper->scrapeGenres();
        }) ?? [];

        // Homepage: Ongoing latest releases & Top 10
        $homeData = $this->rememberCache('v4_samehada_home_data', 900, function () use ($scraper) {
            return $scraper->scrapeHomepage();
        }) ?? [];

        $this->ongoing = $homeData['ongoing'] ?? [];
        $this->topTen = $homeData['topTen'] ?? [];

        $this->loadAnimes();
    }

    public function getShowHomeProperty(): bool
    {
        return blank($this->search) && blank($this->genre) && blank($this->status) && $this->pageNumber === 1;
    }

    public function loadAnimes(): void
    {
        $scraper = new SamehadakuScraperService();

        if (filled($this->search)) {
            $cacheKey = 'v4_samehada_search_' . md5($this->search) . "_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            }) ?? [];
        } elseif (filled($this->genre)) {
            $cacheKey = "v4_samehada_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 21600, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            }) ?? [];
        } else {
            $cacheKey = "v4_samehada_list_{$this->status}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 21600, function () use ($scraper) {
                return $scraper->scrapeAnimeList($this->pageNumber, $this->status);
            }) ?? [];
        }
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->genre = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedGenre(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedStatus(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->genre = '';
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
        $this->genre = '';
        $this->status = '';
        $this->pageNumber = 1;
        $this->loadAnimes();
    }
}
