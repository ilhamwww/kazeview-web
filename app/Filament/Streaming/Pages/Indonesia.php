<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\IndonesiaCatalogVaultService;

class Indonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia';
    protected static string $view = 'filament.streaming.pages.indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $server = 'rebahin';
    public int $page = 1;
    public string $category = 'all';
    public string $provider = 'rebahin';
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

    public array $providers = [];

    public function mount(?string $server = null): void
    {
        $this->server = 'rebahin';
        $this->provider = 'rebahin';

        $this->page = 1;
        $this->category = request()->query('category', 'all');
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

    public function setProvider(string $prov): mixed
    {
        return redirect()->to('/indonesia');
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
        $this->search = '';
        $this->page = 1;
        $this->loadItems();
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $this->page++;
        $vault = IndonesiaCatalogVaultService::getCatalog(
            $this->category,
            $this->page,
            24,
            $this->search,
            $this->provider
        );

        $newItems = $vault['data'] ?? [];
        $this->items = array_merge($this->items, $newItems);
        $this->hasMorePages = $vault['pagination']['hasMore'] ?? false;
    }

    public function loadItems(): void
    {
        $vault = IndonesiaCatalogVaultService::getCatalog(
            $this->category,
            $this->page,
            24,
            $this->search,
            $this->provider
        );

        $this->items = $vault['data'] ?? [];
        $this->totalPages = $vault['pagination']['totalPages'] ?? 1;
        $this->hasMorePages = $vault['pagination']['hasMore'] ?? false;

        if ($this->category === 'all' && empty($this->search)) {
            $this->trending = IndonesiaCatalogVaultService::getTrending(10, $this->provider);
            $this->latestMovies = IndonesiaCatalogVaultService::getLatestMovies(15, $this->provider);
            $this->latestSeries = IndonesiaCatalogVaultService::getLatestSeries(15, $this->provider);
            $this->spotlightItems = array_slice($this->trending, 2, 6);
        } else {
            $this->trending = [];
            $this->latestMovies = [];
            $this->latestSeries = [];
            $this->spotlightItems = [];
        }

        $this->hasMorePages = count($this->items) > 0;
    }
}
