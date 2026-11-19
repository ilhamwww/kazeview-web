<?php

namespace App\Filament\Streaming\Pages;

use App\Services\ChitogenxService;
use Filament\Pages\Page;

class Indonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia/{provider}';
    protected static string $view = 'filament.streaming.pages.indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public const PROVIDER = 'chitogenx';

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = 'streaming', ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return '/indonesia/' . self::PROVIDER;
    }

    public int $page = 1;
    public string $category = 'all';
    public string $provider = self::PROVIDER;
    public string $search = '';

    public array $trending = [];
    public array $items = [];
    public array $latestMovies = [];
    public array $latestSeries = [];
    public array $spotlightItems = [];
    public int $totalPages = 1;
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
        ['id' => self::PROVIDER, 'label' => 'Chitogenx'],
    ];

    public function mount(?string $provider = null): void
    {
        // Provider Rebahin & LK21 sudah dipensiunkan; semua diarahkan ke Chitogenx.
        if ($provider !== null && strtolower($provider) !== self::PROVIDER) {
            $this->redirect('/indonesia/' . self::PROVIDER, navigate: true);

            return;
        }

        $this->provider = self::PROVIDER;
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
        return redirect()->to('/indonesia/' . self::PROVIDER);
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
        if (!$this->hasMorePages) {
            return;
        }

        $this->page++;

        $response = ChitogenxService::indonesiaCatalog($this->category, $this->page, 24, $this->search);
        $newItems = $response['data'] ?? [];

        $existing = array_column($this->items, 'slug');
        $fresh = array_values(array_filter($newItems, fn ($it) => !in_array($it['slug'] ?? '', $existing, true)));

        if (!empty($fresh)) {
            $this->items = array_merge($this->items, $fresh);
        }

        $this->hasMorePages = ($response['pagination']['hasMore'] ?? false) && !empty($fresh);
    }

    public function loadItems(): void
    {
        $response = ChitogenxService::indonesiaCatalog($this->category, $this->page, 24, $this->search);

        $this->items = $response['data'] ?? [];
        $this->totalPages = (int) ($response['pagination']['totalPages'] ?? 1);
        $this->hasMorePages = (bool) ($response['pagination']['hasMore'] ?? false);

        if ($this->category === 'all' && $this->search === '') {
            $this->trending = ChitogenxService::indonesiaCatalog('all', 1, 10)['data'] ?? [];
            $this->latestMovies = ChitogenxService::indonesiaCatalog('movie', 1, 15)['data'] ?? [];
            $this->latestSeries = ChitogenxService::indonesiaCatalog('series', 1, 15)['data'] ?? [];
            $this->spotlightItems = array_slice($this->trending, 2, 6);
        } else {
            $this->trending = [];
            $this->latestMovies = [];
            $this->latestSeries = [];
            $this->spotlightItems = [];
        }
    }
}
