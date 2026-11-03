<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\ShinigamiService;

class Komik extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Komik';
    protected static ?string $title = 'Baca Komik, Manhwa & Manga Sub Indo - KZSTRM';
    protected static ?string $slug = 'komik';
    protected static string $view = 'filament.streaming.pages.komik';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

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

    public array $formats = [
        ['id' => 'all', 'label' => 'Semua Tipe', 'icon' => 'globe'],
        ['id' => 'manhwa', 'label' => 'Manhwa (Korea)', 'icon' => 'sparkles'],
        ['id' => 'manga', 'label' => 'Manga (Jepang)', 'icon' => 'fire'],
        ['id' => 'manhua', 'label' => 'Manhua (China)', 'icon' => 'bolt'],
    ];

    public function mount(): void
    {
        $this->format = request()->query('format', 'all');
        $this->tab = request()->query('tab', 'popular');
        $this->search = request()->query('q', '');
        $this->page = max(1, (int) request()->query('page', 1));

        $this->loadData();
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
     * Cache hasil hanya jika tidak kosong, supaya gangguan relay NUC
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
        $service = app(ShinigamiService::class);

        // Jika user sedang tidak mencari kata kunci, siapkan hero banner dan seksi rilisan terbaru
        if (empty($this->search)) {
            // Hero carousel: 5 komik terpopuler, cache 6 jam. Data kosong tidak di-cache.
            if (empty($this->heroItems)) {
                $this->heroItems = $this->rememberNonEmpty('kz_komik_hero_v2', 21600, function () use ($service) {
                    return array_slice($service->getPopular(1, 6)['data'] ?? [], 0, 5);
                });
            }

            // Top 10 peringkat (semua tipe), cache 6 jam
            if (empty($this->topRanked)) {
                $this->topRanked = $this->rememberNonEmpty('kz_komik_top10_v2', 21600, function () use ($service) {
                    return array_slice($service->getPopular(1, 24)['data'] ?? [], 0, 10);
                });
            }

            // Rail update terbaru, cache 30 menit
            if (empty($this->latestHorizontal)) {
                $this->latestHorizontal = $this->rememberNonEmpty('kz_komik_latest_v2', 1800, function () use ($service) {
                    return $service->getLatest(1, 16)['data'] ?? [];
                });
            }
        }

        // Main Grid items
        $fmt = $this->format !== 'all' ? $this->format : null;

        if (!empty($this->search)) {
            $result = $service->search($this->search, $this->page, 24);
        } elseif ($this->tab === 'latest') {
            $result = $service->getLatest($this->page, 24, $fmt);
        } else {
            $result = $service->getPopular($this->page, 24, $fmt);
        }

        $this->items = $result['data'] ?? [];
        $this->totalPage = $result['total_page'] ?? 1;
        $this->hasMore = $result['has_next'] ?? false;
    }
}
