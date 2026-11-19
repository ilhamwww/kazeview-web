<?php

namespace App\Filament\Streaming\Pages;

use App\Services\NontonAnimeIdScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnimeV5 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Anime V5';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime-v5/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v5';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;

    public array $mirrors = [];
    public int $activeMirrorIndex = 0;

    public string $currentStreamUrl = '';
    public string $currentQuality = '1080p FHD';
    public string $currentServer = 'Server 1080p';
    public string $currentType = 'mp4';
    public bool $useRelay = true;

    public bool $isFavorited = false;

    public function mount(string $anime_slug, string $episode_slug): void
    {
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $scraper = new NontonAnimeIdScraperService();

        $cacheKey = "v5_na_ep_{$anime_slug}_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 7200, function () use ($scraper, $episode_slug) {
            return $scraper->scrapeEpisodeDetail($episode_slug);
        });

        if (empty($this->episodeDetail)) {
            abort(404, 'Episode anime tidak ditemukan');
        }

        // Load series detail
        $seriesSlug = $this->episodeDetail['anime_slug'] ?: preg_replace('/-episode-\d+.*$/i', '', $anime_slug);
        $this->animeSlug = $seriesSlug;
        $this->seriesDetail = $scraper->scrapeAnimeSeriesDetail($seriesSlug);
        $this->episodeSlug = (string) ($this->episodeDetail['slug'] ?? $this->episodeDetail['episode_slug'] ?? $episode_slug);

        $this->mirrors = $this->episodeDetail['mirrors'] ?? [];

        // Set default active stream
        $defaultStream = $this->episodeDetail['default_stream'] ?? [];
        $this->activeMirrorIndex = 0;
        $this->currentQuality = $defaultStream['quality'] ?? '1080p FHD';
        $this->currentServer = $defaultStream['server'] ?? 'Server Utama';
        $this->currentType = $defaultStream['type'] ?? 'mp4';

        $this->updateStreamUrl();

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v5:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime V5');
            $thumbnail = $this->seriesDetail['thumbnail'] ?? null;
            $epNum = (string) ($this->episodeDetail['episode_number'] ?? '1');

            $history = $user->watchHistories()
                ->where('item_type', 'anime')
                ->whereIn('item_id', ['anime_v5:' . $this->animeSlug, 'v5:' . $this->animeSlug])
                ->first();

            $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
            $watchedList = array_values(array_unique(array_map('strval', $watchedList)));
            $epNum = (string) $epNum;
            if (!in_array($epNum, $watchedList, true)) {
                $watchedList[] = $epNum;
            }
            $this->watchedEpisodes = $watchedList;
            $this->lastWatchedEpisode = $epNum;

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => 'anime_v5:' . $this->animeSlug,
                    'item_type' => 'anime',
                ],
                [
                    'title' => $animeTitle,
                    'thumbnail' => $thumbnail,
                    'last_episode' => $epNum,
                    'watched_episodes' => $watchedList,
                    'url' => WatchAnimeV5::getUrl(['anime_slug' => $this->animeSlug, 'episode_slug' => (string) $this->episodeSlug], panel: 'streaming'),
                ]
            );
        }
    }

    protected function updateStreamUrl(): void
    {
        if (!isset($this->mirrors[$this->activeMirrorIndex])) {
            return;
        }

        $target = $this->mirrors[$this->activeMirrorIndex];
        $this->currentStreamUrl = $this->useRelay
            ? ($target['relay_url'] ?: $target['direct_url'])
            : $target['direct_url'];
        $this->currentType = $target['type'] ?? 'mp4';
    }

    public function switchMirror(int $index): void
    {
        if (!isset($this->mirrors[$index])) {
            return;
        }

        $target = $this->mirrors[$index];
        $this->activeMirrorIndex = $index;
        $this->currentQuality = $target['quality'] ?? '720p HD';
        $this->currentServer = $target['server'] ?? $target['name'];
        $this->currentType = $target['type'] ?? 'mp4';

        $this->updateStreamUrl();

        \Filament\Notifications\Notification::make()
            ->title("Beralih ke {$target['label']}")
            ->success()
            ->send();
    }

    public function toggleRelay(): void
    {
        $this->useRelay = !$this->useRelay;
        $this->updateStreamUrl();

        $mode = $this->useRelay ? 'VPS Relay Passthrough' : 'Direct CDN';
        \Filament\Notifications\Notification::make()
            ->title("Mode Pemutar: {$mode}")
            ->info()
            ->send();
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
                'item_id' => 'v5:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->seriesDetail['title'] ?? 'Anime',
                'thumbnail' => $this->seriesDetail['thumbnail'] ?? null,
                'url' => \App\Filament\Streaming\Pages\AnimeDetailV5::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
