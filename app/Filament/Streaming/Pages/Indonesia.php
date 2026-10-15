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

    public array $trending = [];
    public array $items = [];
    public int $totalPages = 100;
    public bool $hasMorePages = true;

    public function mount(): void
    {
        $this->page = 1;
        $this->loadItems();
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $this->page++;
        $response = RebahinService::getIndonesia($this->page);
        
        $newItems = $response['data'] ?? [];
        $this->items = array_merge($this->items, $newItems);
        
        if (empty($newItems) || $this->page >= 100) {
            $this->hasMorePages = false;
        }
    }

    public function loadItems(): void
    {
        $response = RebahinService::getIndonesia($this->page);
        
        $this->items = $response['data'] ?? [];
        $this->trending = array_slice($this->items, 0, 6);
        $this->hasMorePages = count($this->items) > 0;
    }
}
