<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class Movies extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationLabel = 'Movies';
    protected static ?string $title = 'Movies';
    protected static ?string $slug = 'movies';
    protected static string $view = 'filament.streaming.pages.movies';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;

    public array $trending = [];
    public array $movies = [];
    public int $totalPages = 1;
    public bool $hasMorePages = true;

    public function mount(): void
    {
        $this->page = 1;
        $this->trending = StreamingService::getTrendingMovies(10, '7d'); // Get some trending movies for the hero slider
        $this->loadMovies();
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $this->page++;
        $limit = 36;
        $response = StreamingService::getMovies($this->page, $limit, 'createdAt');
        
        $newMovies = $response['data'] ?? [];
        $this->movies = array_merge($this->movies, $newMovies);
        
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        if ($this->page >= $this->totalPages || empty($newMovies)) {
            $this->hasMorePages = false;
        }
    }

    public function loadMovies(): void
    {
        $limit = 36;
        $response = StreamingService::getMovies($this->page, $limit, 'createdAt');
        
        $this->movies = $response['data'] ?? [];
        $pagination = $response['pagination'] ?? [];
        $this->totalPages = $pagination['totalPages'] ?? 1;
        
        $this->hasMorePages = ($this->page < $this->totalPages);
    }
}