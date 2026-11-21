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

    public function mount($slug)
    {
        [$series, $canonical] = $this->resolveSlug($slug, 'series');

        if (!$series) {
            abort(404);
        }

        // URL lama dengan akhiran acak diarahkan ke slug bersih.
        if ($canonical !== $slug) {
            $this->redirect('/series/' . $canonical);
            return;
        }

        $this->seriesSlug = $slug;
        $this->series = $series;

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

    /**
     * Slug kanonik: buang akhiran acak dari detailPath Filmbox, dan cari
     * detailPath yang benar kalau slug pendek belum ketemu.
     *
     * @return array{0:?array,1:string} [data, slug kanonik]
     */
    protected function resolveSlug(string $slug, string $type): array
    {
        $slug = trim($slug);
        $clean = preg_replace('/-[A-Za-z0-9]{8,16}$/', '', $slug) ?: $slug;

        $data = $type === 'series'
            ? \App\Services\StreamingService::getSeriesDetail($slug)
            : \App\Services\StreamingService::getMovieDetail($slug);

        if ($data) {
            return [$data, $clean];
        }

        // Cari detailPath asli lewat katalog Filmbox (+ pencarian judul).
        if ($hit = \App\Services\FilmboxService::findBySlug($clean)) {
            $candidate = (string) ($hit['detail_path'] ?? '') ?: (string) ($hit['slug'] ?? '');

            $found = $type === 'series'
                ? \App\Services\StreamingService::getSeriesDetail($candidate)
                : \App\Services\StreamingService::getMovieDetail($candidate);

            if ($found) {
                return [$found, (string) ($hit['slug'] ?? '') ?: $clean];
            }
        }

        // Coba detailPath asli (dengan akhiran) lewat katalog Filmbox.
        $pool = $type === 'series'
            ? \App\Services\FilmboxService::pool('series')
            : \App\Services\FilmboxService::pool('movie');

        foreach ($pool as $item) {
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemPath = (string) ($item['detail_path'] ?? '');

            if ($itemSlug !== $clean && $itemPath !== $slug) {
                continue;
            }

            $candidate = $itemPath !== '' ? $itemPath : $itemSlug;

            $found = $type === 'series'
                ? \App\Services\StreamingService::getSeriesDetail($candidate)
                : \App\Services\StreamingService::getMovieDetail($candidate);

            if ($found) {
                return [$found, $itemSlug !== '' ? $itemSlug : $clean];
            }
        }

        return [null, $clean];
    }

}
