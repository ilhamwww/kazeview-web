<?php

namespace App\Filament\Streaming\Pages;

use App\Services\MirainimeScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Daftar Anime';
    protected static ?string $title = 'Katalog Anime Lengkap';
    protected static ?string $slug = 'anime-list';
    protected static string $view = 'filament.streaming.pages.anime-list';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    // Filters
    public string $search = '';
    public string $genre = '';
    public string $status = '';
    public string $type = '';
    public string $season = '';
    public string $year = '';
    public string $sort = 'popularity';
    public string $viewMode = 'grid'; // 'grid' | 'list'

    // Data
    public int $page = 1;
    public array $animes = [];
    public bool $hasMore = true;
    public bool $isLoadingMore = false;

    // Options dropdown
    public array $genresList = [];
    public array $seasonsList = [];
    public array $yearsList = [];

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'q'],
        'genre' => ['except' => ''],
        'status' => ['except' => ''],
        'type' => ['except' => ''],
        'season' => ['except' => ''],
        'year' => ['except' => ''],
        'sort' => ['except' => 'popularity'],
        'viewMode' => ['except' => 'grid', 'as' => 'view'],
    ];

    public function mount(): void
    {
        $this->genresList = [
            ['slug' => 'action', 'name' => 'Action'],
            ['slug' => 'adventure', 'name' => 'Adventure'],
            ['slug' => 'comedy', 'name' => 'Comedy'],
            ['slug' => 'drama', 'name' => 'Drama'],
            ['slug' => 'ecchi', 'name' => 'Ecchi'],
            ['slug' => 'fantasy', 'name' => 'Fantasy'],
            ['slug' => 'harem', 'name' => 'Harem'],
            ['slug' => 'historical', 'name' => 'Historical'],
            ['slug' => 'horror', 'name' => 'Horror'],
            ['slug' => 'isekai', 'name' => 'Isekai'],
            ['slug' => 'martial-arts', 'name' => 'Martial Arts'],
            ['slug' => 'mecha', 'name' => 'Mecha'],
            ['slug' => 'mystery', 'name' => 'Mystery'],
            ['slug' => 'psychological', 'name' => 'Psychological'],
            ['slug' => 'romance', 'name' => 'Romance'],
            ['slug' => 'school', 'name' => 'School'],
            ['slug' => 'sci-fi', 'name' => 'Sci-Fi'],
            ['slug' => 'seinen', 'name' => 'Seinen'],
            ['slug' => 'shoujo', 'name' => 'Shoujo'],
            ['slug' => 'shounen', 'name' => 'Shounen'],
            ['slug' => 'slice-of-life', 'name' => 'Slice of Life'],
            ['slug' => 'sports', 'name' => 'Sports'],
            ['slug' => 'supernatural', 'name' => 'Supernatural'],
            ['slug' => 'suspense', 'name' => 'Suspense'],
            ['slug' => 'thriller', 'name' => 'Thriller'],
        ];

        $this->seasonsList = [
            ['value' => 'fall-2026', 'label' => 'Fall 2026'],
            ['value' => 'summer-2026', 'label' => 'Summer 2026'],
            ['value' => 'spring-2026', 'label' => 'Spring 2026'],
            ['value' => 'winter-2026', 'label' => 'Winter 2026'],
            ['value' => 'fall-2025', 'label' => 'Fall 2025'],
            ['value' => 'summer-2025', 'label' => 'Summer 2025'],
            ['value' => 'spring-2025', 'label' => 'Spring 2025'],
            ['value' => 'winter-2025', 'label' => 'Winter 2025'],
        ];

        $this->yearsList = ['2026', '2025', '2024', '2023', '2022', '2021', '2020', '2019', '2018', '2017'];

        $this->loadAnimes(reset: true);
    }

    public function loadAnimes(bool $reset = false): void
    {
        if ($reset) {
            $this->page = 1;
            $this->animes = [];
            $this->hasMore = true;
        }

        $params = [
            'q' => $this->search,
            'genre' => $this->genre,
            'status' => $this->status,
            'type' => $this->type,
            'season' => $this->season,
            'year' => $this->year,
            'sort' => $this->sort,
            'page' => $this->page,
        ];

        $cacheKey = 'anime_dir_' . md5(json_encode($params));
        $newItems = Cache::remember($cacheKey, 1800, function () use ($params) {
            $scraper = new MirainimeScraperService();
            return $scraper->scrapeAnimeDirectory($params);
        });

        if (empty($newItems)) {
            $this->hasMore = false;
        } else {
            if ($reset) {
                $this->animes = $newItems;
            } else {
                // Deduplikasi saat load more
                $existingSlugs = collect($this->animes)->pluck('slug')->all();
                foreach ($newItems as $it) {
                    if (!in_array($it['slug'], $existingSlugs)) {
                        $this->animes[] = $it;
                    }
                }
            }
            if (count($newItems) < 12) {
                $this->hasMore = false;
            }
        }
    }

    public function loadMore(): void
    {
        if (!$this->hasMore || $this->isLoadingMore) {
            return;
        }

        $this->isLoadingMore = true;
        $this->page++;
        $this->loadAnimes(reset: false);
        $this->isLoadingMore = false;
    }

    public function updatedSearch(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedGenre(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedStatus(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedType(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedSeason(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedYear(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function updatedSort(): void
    {
        $this->loadAnimes(reset: true);
    }

    public function setGenre(string $g): void
    {
        $this->genre = ($this->genre === $g) ? '' : $g;
        $this->loadAnimes(reset: true);
    }

    public function setStatus(string $s): void
    {
        $this->status = ($this->status === $s) ? '' : $s;
        $this->loadAnimes(reset: true);
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        $this->loadAnimes(reset: true);
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['grid', 'list']) ? $mode : 'grid';
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->genre = '';
        $this->status = '';
        $this->type = '';
        $this->season = '';
        $this->year = '';
        $this->sort = 'popularity';
        $this->loadAnimes(reset: true);
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;
        if (filled($this->search)) $count++;
        if (filled($this->genre)) $count++;
        if (filled($this->status)) $count++;
        if (filled($this->type)) $count++;
        if (filled($this->season)) $count++;
        if (filled($this->year)) $count++;
        if ($this->sort !== 'popularity') $count++;
        return $count;
    }
}
