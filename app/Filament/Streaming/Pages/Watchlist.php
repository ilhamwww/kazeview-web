<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Watchlist extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationLabel = 'Daftar Tontonan';
    protected static ?string $title = 'Daftar Tontonan Saya - KZSTRM';
    protected static ?string $slug = 'watchlist';
    protected static string $view = 'filament.streaming.pages.watchlist';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $activeFilter = 'all'; // all, movie, tv_series, dracin, anime

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
                ->title('Dihapus dari Daftar Tontonan')
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
        }

        return $query->get();
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
            ];
        }

        $favs = auth()->user()->favorites()->get();

        return [
            'all' => $favs->count(),
            'movie' => $favs->where('item_type', 'movie')->count(),
            'tv_series' => $favs->where('item_type', 'tv_series')->count(),
            'dracin' => $favs->where('item_type', 'dracin')->count(),
            'anime' => $favs->whereIn('item_type', ['anime', 'bstation'])->count(),
        ];
    }
}
