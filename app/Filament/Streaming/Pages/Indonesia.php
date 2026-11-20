<?php

namespace App\Filament\Streaming\Pages;

use App\Services\ChitogenxService;
use App\Services\VidioService;
use App\Services\FilmboxService;
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

    public const PROVIDER = 'katalog';
    public const PROVIDER_VIDIO = 'original';
    public const PROVIDERS = [self::PROVIDER_VIDIO, self::PROVIDER];

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = 'streaming', ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return '/indonesia/' . self::PROVIDER;
    }

    public int $page = 1;
    public string $category = 'all';
    public string $provider = self::PROVIDER;
    public string $search = '';
    public string $genre = '';
    public array $genres = [];

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
        'genre' => ['except' => ''],
    ];

    public array $categories = [
        ['id' => 'all', 'label' => 'Lagi Hype 🔥'],
        ['id' => 'movie', 'label' => 'Film'],
        ['id' => 'series', 'label' => 'Series'],
        ['id' => '2026', 'label' => '2026'],
        ['id' => '2025', 'label' => '2025'],
        ['id' => '2024', 'label' => '2024'],
        ['id' => 'classic', 'label' => '2023 & Sebelumnya'],
    ];

    /**
     * Vidio baru dinyalakan lewat env setelah pemutaran terbukti penuh;
     * sampai itu selesai, pengguna tetap memakai Chitogenx.
     */
    public function providers(): array
    {
        // Nama provider tidak diekspos; hanya katalog utama yang ditampilkan.
        return [['id' => self::PROVIDER, 'label' => 'Katalog']];
    }

    public array $providers = [];

    public function mount(?string $provider = null): void
    {
        // Rebahin & LK21 sudah pensiun. Provider tak dikenal diarahkan ke Chitogenx.
        $provider = $provider !== null ? strtolower($provider) : self::PROVIDER;
        $this->providers = $this->providers();
        $allowed = array_column($this->providers, 'id');

        if (!in_array($provider, $allowed, true)) {
            $this->redirect('/indonesia/' . self::PROVIDER, navigate: true);

            return;
        }

        $this->provider = $provider;
        $this->page = 1;
        $this->category = request()->query('category', 'all');
        $this->search = request()->query('q', '');
        $this->genre = (string) request()->query('genre', '');
        $this->genres = \App\Services\FilmboxService::indonesiaGenres();
        $this->loadItems();
    }

    public function setCategory(string $cat): void
    {
        $this->category = $cat;
        $this->search = '';
        $this->page = 1;
        $this->loadItems();
    }

    public function setGenre(string $genre): void
    {
        $this->genre = $this->genre === $genre ? '' : $genre;
        $this->page = 1;
        $this->loadItems();
    }

    public function setProvider(string $prov): mixed
    {
        $allowed = array_column($this->providers(), 'id');

        return redirect('/indonesia/' . (in_array($prov, $allowed, true) ? $prov : self::PROVIDER));
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
        $this->genre = '';
        $this->page = 1;
        $this->loadItems();
    }

    /**
     * Sumber katalog mengikuti provider yang sedang aktif.
     */
    protected function catalog(string $category, int $page, int $limit, string $search = ''): array
    {
        if ($this->provider === self::PROVIDER_VIDIO) {
            return VidioService::catalog($category, $page, $limit, $search);
        }

        // Utama: Filmbox (Indocast), cadangan: Chitogenx.
        if (FilmboxService::enabled()) {
            $result = FilmboxService::indonesiaCatalog($category, $page, $limit, $search, $this->genre);

            if (!empty($result['data'])) {
                return $result;
            }
        }

        return ChitogenxService::indonesiaCatalog($category, $page, $limit, $search);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) {
            return;
        }

        $this->page++;

        $response = $this->catalog($this->category, $this->page, 24, $this->search);
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
        $response = $this->catalog($this->category, $this->page, 24, $this->search);

        $this->items = $response['data'] ?? [];
        $this->totalPages = (int) ($response['pagination']['totalPages'] ?? 1);
        $this->hasMorePages = (bool) ($response['pagination']['hasMore'] ?? false);

        if ($this->category === 'all' && $this->search === '') {
            $this->trending = $this->catalog('all', 1, 10)['data'] ?? [];
            $this->latestMovies = $this->catalog('movie', 1, 15)['data'] ?? [];
            $this->latestSeries = $this->catalog('series', 1, 15)['data'] ?? [];
            $this->spotlightItems = array_slice($this->trending, 2, 6);
        } else {
            $this->trending = [];
            $this->latestMovies = [];
            $this->latestSeries = [];
            $this->spotlightItems = [];
        }
    }
}
