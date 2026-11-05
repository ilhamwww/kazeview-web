<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\RebahinService;
use App\Services\NartoMovieBoxService;

class Indonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia';
    protected static string $view = 'filament.streaming.pages.indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;
    public string $category = 'all';
    public string $provider = 'all'; // 'all', 'moviebox', 'rebahin'
    public string $search = '';

    public array $trending = [];
    public array $items = [];
    public array $latestMovies = [];
    public array $latestSeries = [];
    public array $spotlightItems = [];
    public int $totalPages = 100;
    public bool $hasMorePages = true;

    protected $queryString = [
        'category' => ['except' => 'all'],
        'provider' => ['except' => 'all'],
        'search' => ['except' => '', 'as' => 'q'],
    ];

    public array $categories = [
        ['id' => 'all', 'label' => 'Semua'],
        ['id' => 'movie', 'label' => 'Film'],
        ['id' => 'series', 'label' => 'Series'],
        ['id' => '2026', 'label' => '2026'],
        ['id' => '2025', 'label' => '2025'],
        ['id' => '2024', 'label' => '2024'],
        ['id' => 'classic', 'label' => '2023 & Sebelumnya'],
    ];

    public array $providers = [
        ['id' => 'all', 'label' => 'Semua Provider (Combo)', 'badge' => 'Combo'],
        ['id' => 'moviebox', 'label' => 'MovieBox (1080p Ultra HD)', 'badge' => 'HD HLS'],
        ['id' => 'rebahin', 'label' => 'Rebahin (Arsip Lengkap)', 'badge' => 'Mirror'],
    ];

    public function mount(): void
    {
        $this->page = 1;
        $this->category = request()->query('category', 'all');
        $this->provider = request()->query('provider', 'all');
        $this->search = request()->query('q', '');
        $this->loadItems();
    }

    public function setCategory(string $cat): void
    {
        $this->category = $cat;
        $this->search = '';
        $this->page = 1;
        $this->loadItems();
    }

    public function setProvider(string $prov): void
    {
        $this->provider = $prov;
        $this->page = 1;
        $this->loadItems();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadItems();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->page = 1;
        $this->loadItems();
    }

    public function resetFilter(): void
    {
        $this->category = 'all';
        $this->provider = 'all';
        $this->search = '';
        $this->page = 1;
        $this->loadItems();
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $this->page++;
        $newItems = [];

        if ($this->provider === 'all' || $this->provider === 'rebahin') {
            $response = RebahinService::getIndonesiaCatalog($this->category, $this->page, $this->search);
            $newItems = $response['data'] ?? [];
        }

        $this->items = array_merge($this->items, $newItems);

        if (empty($newItems) || $this->page >= 100) {
            $this->hasMorePages = false;
        }
    }

    public function loadItems(): void
    {
        $mbItems = [];
        $rbItems = [];

        if ($this->provider === 'all' || $this->provider === 'moviebox') {
            $mbRes = NartoMovieBoxService::getIndonesiaCatalog($this->category, $this->page, $this->search);
            $mbItems = $mbRes['data'] ?? [];
        }

        if ($this->provider === 'all' || $this->provider === 'rebahin') {
            $rbRes = RebahinService::getIndonesiaCatalog($this->category, $this->page, $this->search);
            $rbItems = $rbRes['data'] ?? [];
        }

        // Gabungkan items berdasarkan pilihan provider
        if ($this->provider === 'moviebox') {
            $this->items = $mbItems;
        } elseif ($this->provider === 'rebahin') {
            $this->items = $rbItems;
        } else {
            // Combo mode: MovieBox (1080p Ultra HD) diutamakan, lalu Rebahin
            $seenTitles = [];
            $combined = [];
            foreach ($mbItems as $it) {
                $clean = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($clean) $seenTitles[$clean] = true;
                $combined[] = $it;
            }
            foreach ($rbItems as $it) {
                $clean = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($clean && !isset($seenTitles[$clean])) {
                    $seenTitles[$clean] = true;
                    $combined[] = $it;
                }
            }
            $this->items = $combined;
        }

        if ($this->category === 'all' && empty($this->search)) {
            // Trending: ambil dari MovieBox teratas (10 judul)
            $this->trending = array_slice($mbItems ?: $this->items, 0, 10);

            // Latest Movies (MovieBox movies + Rebahin movies, deduplicated by title)
            $mbMovies = array_filter($mbItems, fn($it) => ($it['type'] ?? '') === 'movie');
            $rbMovies = RebahinService::getIndonesiaCatalog('movie', 1)['data'] ?? [];
            $seenMovieTitles = [];
            $allMovies = [];
            foreach ($mbMovies as $it) {
                $k = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($k) $seenMovieTitles[$k] = true;
                $allMovies[] = $it;
            }
            foreach ($rbMovies as $it) {
                $k = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($k && !isset($seenMovieTitles[$k])) {
                    $seenMovieTitles[$k] = true;
                    $allMovies[] = $it;
                }
            }
            $this->latestMovies = array_slice($allMovies, 0, 15);

            // Latest Series (MovieBox series + Rebahin series, deduplicated by title)
            $mbSeries = array_filter($mbItems, fn($it) => ($it['type'] ?? '') === 'series');
            $rbSeries = RebahinService::getIndonesiaCatalog('series', 1)['data'] ?? [];
            $seenSeriesTitles = [];
            $allSeries = [];
            foreach ($mbSeries as $it) {
                $k = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($k) $seenSeriesTitles[$k] = true;
                $allSeries[] = $it;
            }
            foreach ($rbSeries as $it) {
                $k = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                if ($k && !isset($seenSeriesTitles[$k])) {
                    $seenSeriesTitles[$k] = true;
                    $allSeries[] = $it;
                }
            }
            $this->latestSeries = array_slice($allSeries, 0, 15);

            // Spotlight Items
            $this->spotlightItems = array_slice($mbItems ?: $this->items, 2, 6);
        } else {
            $this->trending = [];
        }

        $this->hasMorePages = count($this->items) > 0;
    }
}
