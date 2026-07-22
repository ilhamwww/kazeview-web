<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class ShowSeries extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Series Detail';
    protected static ?string $title = 'Series Detail';
    protected static ?string $slug = 'series/{slug}';
    protected static string $view = 'filament.streaming.pages.show-series';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public $seriesSlug;
    public ?array $series = null;
    public $selectedSeasonNumber;
    public ?array $currentSeasonData = null;
    public bool $isFavorited = false;

    public function mount($slug): void
    {
        $this->seriesSlug = $slug;
        $this->series = StreamingService::getSeriesDetail($slug);

        if (!$this->series) {
            abort(404);
        }

        // Set default season on load
        $this->selectedSeasonNumber = $this->series['defaultSeason']['seasonNumber'] ?? 1;
        $this->currentSeasonData = $this->series['defaultSeason'] ?? null;

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', $this->seriesSlug)
                ->where('item_type', 'tv_series')
                ->exists()
            : false;
    }

    public function toggleFavorite()
    {
        if (!auth()->check()) {
            return redirect()->to(route('filament.streaming.auth.login'));
        }

        $user = auth()->user();
        $favorite = $user->favorites()
            ->where('item_id', $this->seriesSlug)
            ->where('item_type', 'tv_series')
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => $this->seriesSlug,
                'item_type' => 'tv_series',
                'title' => $this->series['title'] ?? 'TV Series Detail',
                'thumbnail' => $this->series['poster'] ?? $this->series['thumbnail'] ?? null,
                'url' => request()->url(),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }

    public function changeSeason($seasonNumber): void
    {
        $this->selectedSeasonNumber = (int)$seasonNumber;

        // If the selected season is the default season, we can use the cached default season data directly
        if ($this->selectedSeasonNumber === ($this->series['defaultSeason']['seasonNumber'] ?? 1)) {
            $this->currentSeasonData = $this->series['defaultSeason'];
            return;
        }

        // Otherwise, fetch it from the service
        $seasonDetail = StreamingService::getSeriesSeasonDetail($this->seriesSlug, $this->selectedSeasonNumber);
        if ($seasonDetail && isset($seasonDetail['season'])) {
            $this->currentSeasonData = $seasonDetail['season'];
        }
    }
}