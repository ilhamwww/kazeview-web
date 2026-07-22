<?php

namespace App\Filament\Pages;

use App\Services\AnoboyScraperService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

use Illuminate\Support\Facades\Cache;

class ExploreAnime extends Page
{

    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Scraper';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Jelajah Anime (Live)';
    protected static ?string $navigationLabel = 'Explore Anime';
    protected static string $view = 'filament.pages.explore-anime';

    public int $pageNumber = 1;
    public array $animes = [];
    public array $genres = [];
    public string $search = '';
    public string $genre = '';
    public string $status = '';

    protected function rememberCache(string $key, int $ttl, \Closure $callback)
    {
        $value = Cache::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        
        if ($value !== null && (!is_array($value) || !empty($value))) {
            Cache::put($key, $value, $ttl);

            $keys = Cache::get('anoboy_explore_keys', []);
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                Cache::put('anoboy_explore_keys', $keys, 86400 * 30);
            }
        }

        return $value;
    }

    public function mount(): void
    {
        $this->genres = $this->rememberCache('anoboy_explore_genres', 86400, function () {
            $scraper = new AnoboyScraperService();
            return $scraper->scrapeGenres();
        });

        $this->loadAnimes();
    }

    public function loadAnimes(): void
    {
        $scraper = new AnoboyScraperService();
        if (filled($this->search)) {
            $cacheKey = 'anoboy_explore_search_' . md5($this->search);
            $this->animes = $this->rememberCache($cacheKey, 3600, function () use ($scraper) {
                return $scraper->searchAnime($this->search);
            });
        } elseif (filled($this->genre)) {
            $cacheKey = "anoboy_explore_genre_{$this->genre}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 86400, function () use ($scraper) {
                return $scraper->scrapeAnimeByGenre($this->genre, $this->pageNumber);
            });
        } else {
            $cacheKey = "anoboy_explore_list_{$this->status}_{$this->pageNumber}";
            $this->animes = $this->rememberCache($cacheKey, 86400, function () use ($scraper) {
                return $scraper->scrapeAnimeList($this->pageNumber, $this->status);
            });
        }
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->genre = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedGenre(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function updatedStatus(): void
    {
        $this->pageNumber = 1;
        $this->search = '';
        $this->genre = '';
        $this->loadAnimes();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->genre = '';
        $this->status = '';
        $this->pageNumber = 1;
        $this->loadAnimes();
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
        $this->loadAnimes();
    }

    public function previousPage(): void
    {
        if ($this->pageNumber > 1) {
            $this->pageNumber--;
            $this->loadAnimes();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Bersihkan Cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Bersihkan Cache Jelajah')
                ->modalDescription('Apakah Anda yakin ingin menghapus semua cache hasil jelajah anime dan detail anime? Ini akan memaksa sistem mengambil data langsung dari situs Anoboy lagi.')
                ->action(fn () => $this->clearExploreCache()),
        ];
    }

    public function clearExploreCache(): void
    {
        $keys = Cache::get('anoboy_explore_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('anoboy_explore_keys');
        Cache::forget('anoboy_explore_genres');

        \Filament\Notifications\Notification::make()
            ->title('Cache jelajah berhasil dibersihkan')
            ->success()
            ->send();

        $this->genres = $this->rememberCache('anoboy_explore_genres', 86400, function () {
            $scraper = new AnoboyScraperService();
            return $scraper->scrapeGenres();
        });

        $this->pageNumber = 1;
        $this->search = '';
        $this->genre = '';
        $this->status = '';
        $this->loadAnimes();
    }

    public function detailAction(): Action
    {
        return Action::make('detailAction')
            ->modalHeading(fn (array $arguments) => $arguments['title'] ?? 'Detail Anime')
            ->modalContent(function (array $arguments) {
                $url = $arguments['url'] ?? '';
                if (empty($url)) {
                    return view('filament.resources.anime-detail-modal', [
                        'detail' => null,
                        'anime' => null,
                    ]);
                }

                $scraper = new AnoboyScraperService();
                $cacheKey = 'anoboy_explore_detail_' . md5($url);
                $detail = $this->rememberCache($cacheKey, 86400, function () use ($scraper, $url) {
                    return $scraper->scrapeAnimeSeriesDetail($url);
                });

                return view('filament.resources.anime-detail-modal', [
                    'detail' => $detail,
                    // We mock an anime object for the view because it previously expected an Eloquent model
                    'anime' => (object) [
                        'title' => $arguments['title'] ?? 'Detail Anime',
                        'url' => $url,
                        'thumbnail' => $arguments['thumbnail'] ?? '',
                        'status' => $arguments['status'] ?? '',
                        'type' => $arguments['type'] ?? '',
                        'episode' => $arguments['episode'] ?? '',
                    ],
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth('4xl');
    }
}