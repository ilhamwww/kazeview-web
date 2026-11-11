<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class TVSeries extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'TV Series';
    protected static ?string $title = 'TV Series';
    protected static ?string $slug = 'tv-series';
    protected static string $view = 'filament.streaming.pages.tv-series';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;

    public array $trending = [];
    public array $series = [];
    public int $totalPages = 1;
    public bool $hasMorePages = true;

    public function mount(): void
    {
        $this->page = 1;
        $this->trending = StreamingService::getTrendingSeries(10, '7d'); // Get trending tv series for the hero slider
        $this->loadSeries();
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $this->page++;
        $limit = 36;
        $response = StreamingService::getSeries($this->page, $limit, 'createdAt');
        
        $newSeries = $response['data'] ?? [];
        $this->series = array_merge($this->series, $newSeries);
        
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        if ($this->page >= $this->totalPages || empty($newSeries)) {
            $this->hasMorePages = false;
        }
    }

    public function loadSeries(): void
    {
        $limit = 36;
        $response = StreamingService::getSeries($this->page, $limit, 'createdAt');
        
        $this->series = $response['data'] ?? [];
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        $this->hasMorePages = ($this->page < $this->totalPages);
    }
}