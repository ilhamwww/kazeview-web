<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class KoreanDrama extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'Drakor';
    protected static ?string $title = 'Drama Korea (Drakor)';
    protected static ?string $slug = 'korean-drama';
    protected static string $view = 'filament.streaming.pages.korean-drama';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;

    public array $trending = [];
    public array $items = [];
    public int $totalPages = 1;
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
        $limit = 36;
        $response = StreamingService::getKoreanDrama($this->page, $limit, 'latest');
        
        $newItems = $response['data'] ?? [];
        $this->items = array_merge($this->items, $newItems);
        
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        if ($this->page >= $this->totalPages || empty($newItems)) {
            $this->hasMorePages = false;
        }
    }

    public function loadItems(): void
    {
        $limit = 36;
        $response = StreamingService::getKoreanDrama($this->page, $limit, 'latest');
        
        $this->items = $response['data'] ?? [];
        $this->trending = array_slice($this->items, 0, 10);
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        $this->hasMorePages = ($this->page < $this->totalPages) && (count($this->items) > 0);
    }
}
