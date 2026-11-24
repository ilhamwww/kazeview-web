<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Watchlist extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationLabel = 'Daftar Favorit';
    protected static ?string $title = 'Daftar Favorit Saya - KZSTRM';
    protected static ?string $slug = 'watchlist';
    protected static string $view = 'filament.streaming.pages.watchlist';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $activeFilter = 'all'; // all, movie, tv_series, dracin, anime, komik, doujin

    /** Konten 18+ disembunyikan sampai pengguna memintanya. */
    public bool $showNsfw = false;

    public function toggleNsfw(): void
    {
        $this->showNsfw = ! $this->showNsfw;
    }

    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    public function removeFavorite(int $id): void
    {
        if (!auth()->check()) {
            return;
        }

        $fav = auth()->user()->favorites()->where('id', $id)->first();
        if ($fav) {
            $fav->delete();
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Daftar Favorit')
                ->success()
                ->send();
        }
    }

    public function clearAllFavorites(): void
    {
        if (!auth()->check()) {
            return;
        }

        auth()->user()->favorites()->delete();
        \Filament\Notifications\Notification::make()
            ->title('Semua daftar tontonan berhasil dibersihkan')
            ->success()
            ->send();
    }

    public function getFilteredFavoritesProperty(): Collection
    {
        if (!auth()->check()) {
            return collect([]);
        }

        $query = auth()->user()->favorites()->latest();

        if ($this->activeFilter === 'movie') {
            $query->where('item_type', 'movie');
        } elseif ($this->activeFilter === 'tv_series') {
            $query->where('item_type', 'tv_series');
        } elseif ($this->activeFilter === 'dracin') {
            $query->where('item_type', 'dracin');
        } elseif ($this->activeFilter === 'anime') {
            $query->whereIn('item_type', ['anime', 'bstation']);
        } elseif ($this->activeFilter === 'komik') {
            $query->whereIn('item_type', ['komik', 'comic', 'webtoon', 'manga']);
        } elseif ($this->activeFilter === 'doujin') {
            $query->where('item_type', 'doujin');
        }

        return $query->get()->reject(fn ($fav) => $this->isNsfw($fav))->values();
    }

    public function getNsfwFavoritesProperty(): Collection
    {
        if (! auth()->check()) {
            return collect([]);
        }

        $query = auth()->user()->favorites()->latest();

        if ($this->activeFilter === 'movie') {
            $query->where('item_type', 'movie');
        } elseif ($this->activeFilter === 'tv_series') {
            $query->where('item_type', 'tv_series');
        } elseif ($this->activeFilter === 'dracin') {
            $query->where('item_type', 'dracin');
        } elseif ($this->activeFilter === 'anime') {
            $query->whereIn('item_type', ['anime', 'bstation']);
        } elseif ($this->activeFilter === 'komik') {
            $query->whereIn('item_type', ['komik', 'comic', 'webtoon', 'manga']);
        } elseif ($this->activeFilter === 'doujin') {
            $query->where('item_type', 'doujin');
        }

        return $query->get()->filter(fn ($fav) => $this->isNsfw($fav))->values();
    }

    protected function isNsfw($fav): bool
    {
        return $fav->item_type === 'doujin' || (bool) ($fav->is_nsfw ?? false);
    }

    public function getCountsProperty(): array
    {
        if (!auth()->check()) {
            return [
                'all' => 0,
                'movie' => 0,
                'tv_series' => 0,
                'dracin' => 0,
                'anime' => 0,
                'komik' => 0,
                'doujin' => 0,
            ];
        }

        $favs = auth()->user()->favorites()->get();

        return [
            'all' => $favs->count(),
            'movie' => $favs->where('item_type', 'movie')->count(),
            'tv_series' => $favs->where('item_type', 'tv_series')->count(),
            'dracin' => $favs->where('item_type', 'dracin')->count(),
            'anime' => $favs->whereIn('item_type', ['anime', 'bstation'])->count(),
            'komik' => $favs->whereIn('item_type', ['komik', 'comic', 'webtoon', 'manga'])->count(),
            'doujin' => $favs->where('item_type', 'doujin')->count(),
        ];
    }
}
