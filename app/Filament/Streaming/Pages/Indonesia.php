<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\IndonesiaCatalogVaultService;

class Indonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia/{server?}';
    protected static string $view = 'filament.streaming.pages.indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $server = 'server-1';
    public int $page = 1;
    public string $category = 'all';
    public string $provider = 'moviebox'; // 'moviebox', 'rebahin', 'indoxxi'
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

    public array $providers = [
        ['id' => 'server-1', 'provider' => 'moviebox', 'label' => 'Server 1 (1080p Ultra HD)', 'badge' => 'Server 1', 'url' => '/indonesia/server-1'],
        ['id' => 'server-2', 'provider' => 'rebahin', 'label' => 'Server 2 (Arsip Lengkap)', 'badge' => 'Server 2', 'url' => '/indonesia/server-2'],
        ['id' => 'server-3', 'provider' => 'indoxxi', 'label' => 'Server 3 (Mirror)', 'badge' => 'Server 3', 'url' => '/indonesia/server-3'],
    ];

    public function mount(?string $server = null): void
    {
        $serverParam = strtolower(trim((string) $server));
        if (empty($serverParam) || $serverParam === 'indonesia') {
            $this->server = 'server-1';
        } else {
            $this->server = in_array($serverParam, ['server-1', 'server-2', 'server-3']) ? $serverParam : 'server-1';
        }

        $serverMap = [
            'server-1' => 'moviebox',
            'server-2' => 'rebahin',
            'server-3' => 'indoxxi',
        ];
        $this->provider = $serverMap[$this->server] ?? 'moviebox';

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
        $srv = $prov;
        if ($prov === 'moviebox') $srv = 'server-1';
        elseif ($prov === 'rebahin') $srv = 'server-2';
        elseif ($prov === 'indoxxi') $srv = 'server-3';

        return redirect()->to('/indonesia/' . $srv);
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
