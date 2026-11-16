<?php

namespace App\Filament\Streaming\Pages;

use App\Services\DoujindesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Doujin extends Page
{
    protected static ?string $navigationLabel = 'Doujin 18+';
    protected static ?string $title = 'Doujin & Manhwa 18+ Sub Indo - KZSTRM';
    protected static ?string $slug = 'doujin';
    protected static string $view = 'filament.streaming.pages.doujin';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public int $page = 1;
    public string $type = 'all'; // all, doujinshi, manhwa, manga
    public string $sort = 'latest_chapter'; // latest_chapter, popular, newest, rating
    public array $selectedGenres = [];
    public string $search = '';

    public array $banners = [];
    public array $genresList = [];
    public array $mangaList = [];
    public bool $hasNext = false;

    public function mount(?DoujindesuScraperService $service = null): void
    {
        $service = $service ?? app(DoujindesuScraperService::class);
        // Guard NSFW check
        $user = Auth::user();
        if (!$user || empty($user->nsfw_enabled)) {
            $this->redirect('/profile');
            return;
        }

        $this->type = request()->query('type', 'all');
        $this->sort = request()->query('sort', 'latest_chapter');
        $this->page = max(1, (int) request()->query('page', 1));
        $this->search = trim(request()->query('q', ''));
        
        $genreQuery = request()->query('genres', '');
        if (!empty($genreQuery)) {
            $this->selectedGenres = array_filter(explode(',', $genreQuery));
        }

        $this->banners = $service->getBanners();
        $this->genresList = $service->getGenres();
        $this->loadCatalog($service);
    }

    public function loadCatalog(DoujindesuScraperService $service): void
    {
        $res = $service->getMangaList(
            page: $this->page,
            type: $this->type,
            sort: $this->sort,
            genres: $this->selectedGenres,
            search: $this->search,
            limit: 24
        );

        $this->mangaList = $res['items'] ?? [];
        $this->hasNext = (bool)($res['has_next'] ?? false);
    }

    public function filterType(string $type): void
    {
        $this->type = $type;
        $this->page = 1;
        $this->loadCatalog(app(DoujindesuScraperService::class));
    }

    public function filterSort(string $sort): void
    {
        $this->sort = $sort;
        $this->page = 1;
        $this->loadCatalog(app(DoujindesuScraperService::class));
    }

    public function toggleGenre(string $slug): void
    {
        if (in_array($slug, $this->selectedGenres)) {
            $this->selectedGenres = array_values(array_diff($this->selectedGenres, [$slug]));
        } else {
            $this->selectedGenres[] = $slug;
        }
        $this->page = 1;
        $this->loadCatalog(app(DoujindesuScraperService::class));
    }

    public function clearGenres(): void
    {
        $this->selectedGenres = [];
        $this->page = 1;
        $this->loadCatalog(app(DoujindesuScraperService::class));
    }

    public function nextPage(): void
    {
        if ($this->hasNext) {
            $this->page++;
            $this->loadCatalog(app(DoujindesuScraperService::class));
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadCatalog(app(DoujindesuScraperService::class));
        }
    }
}
