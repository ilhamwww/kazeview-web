<?php

namespace App\Filament\Streaming\Pages;

use App\Services\MirainimeScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Anime V1';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime-v1/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;

    public string $currentStreamUrl = '';
    public string $currentServer = 'Direct HD (MP4)';

    public ?string $prevEpisodeSlug = null;
    public ?string $nextEpisodeSlug = null;

    public bool $isFavorited = false;

    public function mount(string $anime_slug, string $episode_slug): void
    {
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $scraper = new MirainimeScraperService();

        $cacheKey = "v1_ep_{$anime_slug}_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 3600, function () use ($scraper, $anime_slug, $episode_slug) {
            return $scraper->scrapeEpisodeDetail($anime_slug, $episode_slug);
        });

        // Re-scrape jika cached video_url kosong atau gagal
        if (empty($this->episodeDetail['video_url'])) {
            $fresh = $scraper->scrapeEpisodeDetail($anime_slug, $episode_slug);
            if ($fresh && !empty($fresh['video_url'])) {
                $this->episodeDetail = $fresh;
                Cache::put($cacheKey, $fresh, 3600);
            }
        }

        if (!$this->episodeDetail) {
            abort(404, 'Episode tidak ditemukan');
        }

        // Load series detail
        $seriesCacheKey = 'v1_detail_' . md5($anime_slug);
        $this->seriesDetail = Cache::remember($seriesCacheKey, 43200, function () use ($scraper, $anime_slug) {
            return $scraper->scrapeAnimeSeriesDetail($anime_slug);
        });

        // Determine Prev & Next episodes
        $episodes = $this->seriesDetail['episodes'] ?? [];
        $currentIndex = null;
        foreach ($episodes as $idx => $ep) {
            if ((string)($ep['episode'] ?? '') === (string)$this->episodeSlug) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex !== null) {
            $this->prevEpisodeSlug = isset($episodes[$currentIndex - 1]) ? (string)($episodes[$currentIndex - 1]['episode'] ?? null) : null;
            $this->nextEpisodeSlug = isset($episodes[$currentIndex + 1]) ? (string)($episodes[$currentIndex + 1]['episode'] ?? null) : null;
        }

        $rawStream = $this->episodeDetail['video_url'] ?? '';
        if (!empty($rawStream)) {
            if (str_contains($rawStream, '/api/proxy-stream')) {
                $this->currentStreamUrl = $rawStream;
            } else {
                $this->currentStreamUrl = url('/api/proxy-stream?url=' . urlencode($rawStream));
            }
        } else {
            $this->currentStreamUrl = '';
        }

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v1:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime V1');
            $thumbnail = $this->seriesDetail['thumbnail'] ?? null;
            $epNum = (string) ($this->episodeDetail['episode_number'] ?? $this->episodeSlug);

            $history = $user->watchHistories()
                ->where('item_id', 'anime_v1:' . $this->animeSlug)
                ->first();

            $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
            if (!in_array($epNum, $watchedList, true)) {
                $watchedList[] = $epNum;
            }

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => 'anime_v1:' . $this->animeSlug,
                    'item_type' => 'anime',
                ],
                [
                    'title' => $animeTitle,
                    'thumbnail' => $thumbnail,
                    'last_episode' => $epNum,
                    'watched_episodes' => $watchedList,
                    'url' => request()->url(),
                ]
            );
        }
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v1:' . $this->animeSlug)
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
                'item_id' => 'v1:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->seriesDetail['title'] ?? 'Anime',
                'thumbnail' => $this->seriesDetail['thumbnail'] ?? null,
                'url' => \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
