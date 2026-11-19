<?php

namespace App\Filament\Streaming\Pages;

use App\Services\Lk21ScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ExploreServerLk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationLabel = 'Server LK';
    protected static ?string $title = 'Server LK (18+)';
    protected static ?string $slug = 'server-lk';
    protected static string $view = 'filament.streaming.pages.explore-server-lk';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $pageNumber = 1;
    public ?int $selectedCategory = null;
    public string $search = '';
    public array $movies = [];
    public array $featured = [];
    public array $categories = [];
    public int $totalPages = 1;
    public int $totalItems = 0;

    public function mount(): void
    {
        $scraper = new Lk21ScraperService();
        $this->categories = $scraper->getCategories();

        // Get initial movies and pick first 4-5 as featured spotlight on page 1
        $this->loadMovies();

        if (empty($this->search) && empty($this->selectedCategory) && $this->pageNumber === 1 && !empty($this->movies)) {
            $this->featured = array_slice($this->movies, 0, 4);
        }
    }

    public function loadMovies(): void
    {
        $scraper = new Lk21ScraperService();
        $result = $scraper->getMovies(
            page: $this->pageNumber,
            categoryId: $this->selectedCategory,
            search: $this->search,
            perPage: 24
        );

        $this->movies = $result['items'] ?? [];
        $this->totalPages = $result['totalPages'] ?? 1;
        $this->totalItems = $result['totalItems'] ?? 0;
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->loadMovies();
    }

    public function setCategory(?int $categoryId): void
    {
        $this->selectedCategory = $categoryId;
        $this->pageNumber = 1;
        $this->loadMovies();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = null;
        $this->pageNumber = 1;
        $this->loadMovies();
    }

    public function nextPage(): void
    {
        if ($this->pageNumber < $this->totalPages) {
            $this->pageNumber++;
            $this->loadMovies();
        }
    }

    public function previousPage(): void
    {
        if ($this->pageNumber > 1) {
            $this->pageNumber--;
            $this->loadMovies();
        }
    }
}
