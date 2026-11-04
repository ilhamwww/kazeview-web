<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\ShinigamiService;
use App\Services\IzanamiScansService;
use App\Services\WurmzScraperService;

class Komik extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Webtoon';
    protected static ?string $title = 'Baca Webtoon & Manhwa Sub Indo - KZSTRM';
    protected static ?string $slug = 'komik/{provider?}';
    protected static string $view = 'filament.streaming.pages.komik';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $provider = 'native'; // Default langsung buka katalog Native KZSTRM
    public int $page = 1;
    public string $format = 'all'; // all, manhwa, manga, manhua
    public string $tab = 'popular'; // popular, latest
    public string $search = '';

    public array $heroItems = [];
    public array $latestHorizontal = [];
    public array $topRanked = [];
    public array $items = [];
    public int $totalPage = 1;
    public bool $hasMore = false;

    protected $queryString = [
        'format' => ['except' => 'all'],
        'tab' => ['except' => 'popular'],
        'search' => ['except' => '', 'as' => 'q'],
        'page' => ['except' => 1],
    ];

    public array $providers = [
        [
            'id' => 'native',
            'name' => 'Native KZSTRM',
            'tag' => 'Fast / Local',
            'desc' => 'Database mandiri & poster lokal super cepat',
            'is_nuc' => false,
        ],
        [
            'id' => 'wurmz',
            'name' => 'Wurmz Scans',
            'tag' => 'Direct VPS',
            'desc' => 'Ribuan Manga & Manhwa lengkap (HD Natsu CDN)',
            'is_nuc' => false,
        ],
        [
            'id' => 'izanami',
            'name' => 'Izanami Scans',
            'tag' => 'Google CDN',
            'desc' => 'Manhua & Manhwa update cepat bebas blokir',
            'is_nuc' => false,
        ],
        [
            'id' => 'shinigami',
            'name' => 'Shinigami ID',
            'tag' => 'NUC Bridge',
            'desc' => 'Agregator komik populer via NUC relay',
            'is_nuc' => true,
        ],
    ];

    public array $formats = [
        ['id' => 'all', 'label' => 'Semua Tipe', 'icon' => 'globe'],
        ['id' => 'manhwa', 'label' => 'Manhwa (Korea)', 'icon' => 'sparkles'],
        ['id' => 'manga', 'label' => 'Manga (Jepang)', 'icon' => 'fire'],
        ['id' => 'manhua', 'label' => 'Manhua (China)', 'icon' => 'bolt'],
    ];

    public function mount(?string $provider = null): void
    {
        $requestedProvider = $provider ?: request()->query('provider', 'native');
        $this->provider = is_string($requestedProvider) && in_array(strtolower($requestedProvider), ['native', 'wurmz', 'izanami', 'shinigami', 'picker'], true)
            ? strtolower($requestedProvider) : 'native';

        $this->format = request()->query('format', 'all');
        $this->tab = request()->query('tab', 'popular');
        $this->search = request()->query('q', '');
        $this->page = max(1, (int) request()->query('page', 1));

        $this->loadData();
    }

    public function setProvider(string $p): void
    {
        if (in_array($p, ['native', 'wurmz', 'izanami', 'shinigami'])) {
            $this->provider = $p;
            $this->page = 1;
            $this->search = '';
            $this->heroItems = [];
            $this->latestHorizontal = [];
            $this->topRanked = [];
            $this->loadData();
        }
    }

    public function setFormat(string $fmt): void
    {
        $this->format = $fmt;
        $this->page = 1;
        $this->search = '';
        $this->loadData();
    }

    public function setTab(string $t): void
    {
        $this->tab = $t;
        $this->page = 1;
        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadData();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->page = 1;
        $this->loadData();
    }

    public function nextPage(): void
    {
        if ($this->hasMore) {
            $this->page++;
            $this->loadData();
            $this->dispatch('scroll-to-catalog');
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadData();
            $this->dispatch('scroll-to-catalog');
        }
    }

    /**
     * Cache hasil hanya jika tidak kosong, supaya relay mati
     * tidak membuat seksi kosong tersimpan berjam-jam.
     */
    protected function rememberNonEmpty(string $key, int $ttl, \Closure $callback): array
    {
        $cached = \Illuminate\Support\Facades\Cache::get($key);
        if (!empty($cached)) {
            return $cached;
        }

        $value = $callback();
        if (!empty($value)) {
            \Illuminate\Support\Facades\Cache::put($key, $value, $ttl);
        }

        return $value ?: [];
    }

    public function loadData(): void
    {
        if (!in_array($this->provider, ['native', 'wurmz', 'izanami', 'shinigami'], true)) {
            $this->heroItems = $this->latestHorizontal = $this->topRanked = $this->items = [];
            $this->hasMore = false;
            $this->totalPage = 1;
            return;
        }

        $service = match ($this->provider) {
            'native' => app(\App\Services\NativeComicService::class),
            'wurmz' => app(WurmzScraperService::class),
            'izanami' => app(IzanamiScansService::class),
            'shinigami' => app(ShinigamiService::class),
            default => app(ShinigamiService::class),
        };

        // Jika user sedang tidak mencari kata kunci, siapkan hero banner, top 10, dan rilisan terbaru
        if (empty($this->search)) {
            // Hero carousel: 5 komik terpopuler, cache 6 jam
            if (empty($this->heroItems)) {
                $this->heroItems = $this->rememberNonEmpty("kz_webtoon_hero_{$this->provider}_v3", 21600, function () use ($service) {
                    return array_slice($service->getPopular(1, 6)['data'] ?? [], 0, 5);
                });
            }

            // Top 10 peringkat (semua tipe), cache 6 jam
            if (empty($this->topRanked)) {
                $this->topRanked = $this->rememberNonEmpty("kz_webtoon_top10_{$this->provider}_v3", 21600, function () use ($service) {
                    return array_slice($service->getPopular(1, 24)['data'] ?? [], 0, 10);
                });
            }

            // Rail update terbaru, cache 30 menit
            if (empty($this->latestHorizontal)) {
                $this->latestHorizontal = $this->rememberNonEmpty("kz_webtoon_latest_{$this->provider}_v3", 1800, function () use ($service) {
                    return array_slice($service->getLatest(1, 16)['data'] ?? [], 0, 16);
                });
            }
        }

        // Main Grid items
        $fmt = $this->format !== 'all' ? $this->format : null;
        $gridLimit = $this->provider === 'wurmz' ? 48 : 24;

        if (!empty($this->search)) {
            $result = $service->search($this->search, $this->page, 24);
        } elseif ($this->tab === 'latest') {
            $result = $service->getLatest($this->page, $gridLimit, $fmt);
        } else {
            $result = $service->getPopular($this->page, $gridLimit, $fmt);
        }

        $this->items = $result['data'] ?? [];
        $this->totalPage = $result['total_page'] ?? 1;
        $this->hasMore = $result['has_next'] ?? false;
    }
}
