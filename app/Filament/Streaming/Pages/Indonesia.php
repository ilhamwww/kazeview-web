<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\RebahinService;

class Indonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia';
    protected static ?string $slug = 'indonesia';
    protected static string $view = 'filament.streaming.pages.indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;
    public string $category = 'all';
    public string $search = '';

    public array $trending = [];
    public array $items = [];
    public int $totalPages = 100;
    public bool $hasMorePages = true;

    protected $queryString = [
        'category' => ['except' => 'all'],
        'search' => ['except' => '', 'as' => 'q'],
    ];

    public array $categories = [
        ['id' => 'all', 'label' => 'Semua', 'emoji' => '🌐'],
        ['id' => 'movie', 'label' => 'Film', 'emoji' => '🎬'],
        ['id' => 'series', 'label' => 'Series', 'emoji' => '📺'],
        ['id' => '2026', 'label' => '2026', 'emoji' => '🔥'],
        ['id' => '2025', 'label' => '2025', 'emoji' => '📅'],
        ['id' => '2024', 'label' => '2024', 'emoji' => '📅'],
        ['id' => 'classic', 'label' => '2023 & Sebelumnya', 'emoji' => '⏳'],
    ];

    public function mount(): void
    {
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
        $response = RebahinService::getIndonesiaCatalog($this->category, $this->page, $this->search);
        
        $newItems = $response['data'] ?? [];
        $this->items = array_merge($this->items, $newItems);
        
        if (empty($newItems) || $this->page >= 100) {
            $this->hasMorePages = false;
        }
    }

    public function loadItems(): void
    {
        $response = RebahinService::getIndonesiaCatalog($this->category, $this->page, $this->search);
        
        $this->items = $response['data'] ?? [];
        if ($this->category === 'all' && empty($this->search)) {
            $this->trending = array_slice($this->items, 0, 10);
        } else {
            $this->trending = [];
        }
        $this->hasMorePages = count($this->items) > 0;
    }
}
