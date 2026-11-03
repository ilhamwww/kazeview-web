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
        ['id' => 'manhwa', 'label' => 'Manhwa (Korea) 🇰🇷', 'icon' => 'sparkles'],
        ['id' => 'manga', 'label' => 'Manga (Jepang) 🇯🇵', 'icon' => 'fire'],
        ['id' => 'manhua', 'label' => 'Manhua (China) 🇨🇳', 'icon' => 'bolt'],
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

    public function loadData(): void
    {
        $service = app(ShinigamiService::class);

        // Jika user sedang tidak mencari kata kunci, siapkan hero banner dan seksi rilisan terbaru
        if (empty($this->search)) {
            // Hero items (ambil dari 5 manhwa/komik terpopuler) di-cache 6 jam
            if (empty($this->heroItems)) {
                $this->heroItems = \Illuminate\Support\Facades\Cache::remember('kz_komik_hero_items', 21600, function () use ($service) {
                    $popular = $service->getPopular(1, 6, 'manhwa');
                    return $popular['data'] ?? [];
                });
            }

            // Latest updates rail di-cache 30 menit
            if (empty($this->latestHorizontal)) {
                $this->latestHorizontal = \Illuminate\Support\Facades\Cache::remember('kz_komik_latest_rail', 1800, function () use ($service) {
                    $latest = $service->getLatest(1, 12);
                    return $latest['data'] ?? [];
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
