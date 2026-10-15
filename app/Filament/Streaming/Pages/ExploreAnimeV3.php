<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnoboyScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreAnimeV3 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime V3';
    protected static ?string $title = 'Explore Anime V3';
    protected static ?string $slug = 'anime-v3';
    protected static string $view = 'filament.streaming.pages.explore-anime-v3';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';

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
        $this->genres = $this->rememberCache('v3_anoboy_genres', 86400, function () {
            $scraper = new AnoboyScraperService();
            return $scraper->scrapeGenres();
        });

        $this->loadAnimes();
    }

    public function loadAnimes(): void
    {
        $scraper = new AnoboyScraperService();
        if (filled($this->search)) {
            $cacheKey = 'v3_anoboy_search_' . md5($this->search);
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            });
        } elseif (filled($this->genre)) {
            $cacheKey = "v3_anoboy_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 43200, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            });
        } else {
            $cacheKey = "v3_anoboy_list_{$this->status}_{$this->pageNumber}";
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
