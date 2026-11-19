<?php

namespace App\Filament\Streaming\Pages;

use App\Services\NontonAnimeIdScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeDetailV5 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime Detail V5';
    protected static ?string $title = 'Anime Detail';
    protected static ?string $slug = 'anime-v5/{slug}';
    protected static string $view = 'filament.streaming.pages.anime-detail-v5';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public ?array $detail = null;
    public string $animeUrl = '';
    public bool $isFavorited = false;
    public array $watchedEpisodes = [];
    public ?string $lastWatchedEpisode = null;

    public function mount(string $slug): mixed
    {
        $cleanSlug = trim($slug, '/');
        // Bersihkan slug jika ketambahan suffix episode
        $cleanSlug = preg_replace('/-episode-\d+.*$/i', '', $cleanSlug);

        $this->animeSlug = $cleanSlug;

        $scraper = new NontonAnimeIdScraperService();
        $this->animeUrl = rtrim($scraper->getBaseUrl(), '/') . '/anime/' . $cleanSlug . '/';

        $cacheKey = "v5_na_detail_{$cleanSlug}";
        $this->detail = Cache::remember($cacheKey, 7200, function () use ($scraper, $cleanSlug) {
            return $scraper->scrapeAnimeSeriesDetail($cleanSlug);
        });

        if (empty($this->detail)) {
            abort(404, 'Detail anime tidak ditemukan');
        }

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v5:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        if (auth()->check()) {
            $history = auth()->user()->watchHistories()
                ->where('item_type', 'anime')
                ->whereIn('item_id', ['anime_v5:' . $this->animeSlug, 'v5:' . $this->animeSlug])
                ->first();
            $this->watchedEpisodes = array_values(array_unique(array_map('strval', is_array($history?->watched_episodes) ? $history->watched_episodes : [])));
            $this->lastWatchedEpisode = $history?->last_episode !== null ? (string) $history->last_episode : null;
        }

        return null;
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v5:' . $this->animeSlug)
            ->where('item_type', 'anime')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => 'anime_v5:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->detail['title'] ?? 'Anime Detail',
                'thumbnail' => $this->detail['thumbnail'] ?? null,
                'url' => AnimeDetailV5::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
