<?php

namespace App\Filament\Streaming\Pages;

use App\Services\MirainimeScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Anime';
    protected static ?string $slug = 'anime';
    protected static string $view = 'filament.streaming.pages.explore-anime-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';
    public array $home = [];
    public array $recent = [];

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
        $this->genres = $this->rememberCache('v1_genres', 86400, function () {
            $scraper = new MirainimeScraperService();
            return $scraper->scrapeGenres();
        });

        // Beranda V1: episode terbaru, tren, musim ini (cache singkat supaya tetap fresh)
        $this->home = $this->rememberCache('v1_home', 600, function () {
            $data = (new MirainimeScraperService())->scrapeHomepage();
            return empty($data['latest']) && empty($data['hero']) ? [] : $data;
        }) ?? [];

        $this->recent = $this->rememberCache('v1_recent_feed', 600, function () {
            return (new MirainimeScraperService())->scrapeRecentFeed(24);
        }) ?? [];

        $this->loadAnimes();
    }

    /**
     * Beranda hanya tampil saat tidak ada pencarian/filter dan di halaman 1.
     */
    public function getShowHomeProperty(): bool
    {
        return blank($this->search) && blank($this->genre) && blank($this->status) && $this->pageNumber === 1;
    }

    public function loadAnimes(): void
    {
        $scraper = new MirainimeScraperService();
        if (filled($this->search)) {
            $cacheKey = 'v1_search_' . md5($this->search);
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            });
        } elseif (filled($this->genre)) {
            $cacheKey = "v1_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            });
        } else {
            $cacheKey = "v1_list_{$this->status}_{$this->pageNumber}";
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
