<?php

namespace App\Filament\Streaming\Pages;

use App\Services\OtakudesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnimeV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime V2';
    protected static ?string $title = 'Explore Anime V2';
    protected static ?string $slug = 'anime-v2';
    protected static string $view = 'filament.streaming.pages.explore-anime-v2';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';
    public array $ongoing = [];
    public array $completed = [];

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
        $this->genres = $this->rememberCache('v2_otaku_genres', 86400, function () {
            $scraper = new OtakudesuScraperService();
            return $scraper->scrapeGenres();
        });

        // Beranda V2: rilis episode terbaru (ongoing) + baru tamat
        $this->ongoing = $this->rememberCache('v2_otaku_home_ongoing', 900, function () {
            return (new OtakudesuScraperService())->scrapeAnimeList(1, 'ongoing');
        }) ?? [];
        $this->completed = $this->rememberCache('v2_otaku_home_completed', 3600, function () {
            return (new OtakudesuScraperService())->scrapeAnimeList(1, 'completed');
        }) ?? [];

        $this->loadAnimes();
    }

    public function getShowHomeProperty(): bool
    {
        return blank($this->search) && blank($this->genre) && blank($this->status) && $this->pageNumber === 1;
    }

    public function loadAnimes(): void
    {
        $scraper = new OtakudesuScraperService();
        if (filled($this->search)) {
            $cacheKey = 'v2_otaku_search_' . md5($this->search);
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            });
        } elseif (filled($this->genre)) {
            $cacheKey = "v2_otaku_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            });
        } else {
            $cacheKey = "v2_otaku_list_{$this->status}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeList($this->pageNumber, $this->status);
            });
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
