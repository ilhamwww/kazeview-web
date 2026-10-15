<?php

namespace App\Filament\Streaming\Pages;

use App\Services\OtakudesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnimeV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Anime V2';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime-v2/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v2';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;

    public array $mirrors = [];
    public array $downloads = [];
    public int $activeMirrorIndex = 0;

    public string $currentStreamUrl = '';
    public ?string $currentDirectMp4 = null;
    public bool $isDirectMp4 = false;
    public string $currentQuality = '720p HD';
    public string $currentServer = 'Default';

    public bool $isFavorited = false;

    public function mount(string $anime_slug, string $episode_slug): void
    {
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $scraper = new OtakudesuScraperService();
        $episodeUrl = rtrim($scraper->getBaseUrl(), '/') . '/episode/' . $episode_slug . '/';

        $cacheKey = "v2_otaku_ep_{$anime_slug}_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 21600, function () use ($scraper, $episodeUrl) {
            return $scraper->scrapeEpisodeDetail($episodeUrl);
        });

        if (!$this->episodeDetail) {
            abort(404, 'Episode tidak ditemukan');
        }

        // Load series detail
        $seriesUrl = $this->episodeDetail['anime_url'] ?: (rtrim($scraper->getBaseUrl(), '/') . '/anime/' . $anime_slug . '/');
        $seriesCacheKey = 'v2_otaku_detail_' . md5($seriesUrl);
        $this->seriesDetail = Cache::remember($seriesCacheKey, 43200, function () use ($scraper, $seriesUrl) {
            return $scraper->scrapeAnimeSeriesDetail($seriesUrl);
        });

        $this->mirrors = $this->episodeDetail['mirrors'] ?? [];
        $this->downloads = $this->episodeDetail['downloads'] ?? [];

        // Set default active stream
        $defaultStream = $this->episodeDetail['default_stream'] ?? [];
        $this->currentStreamUrl = $defaultStream['stream_url'] ?? ($this->episodeDetail['video_url'] ?? '');
        $this->currentDirectMp4 = $defaultStream['direct_mp4'] ?? ($this->episodeDetail['direct_mp4'] ?? null);
        $this->isDirectMp4 = !empty($this->currentDirectMp4);
        $this->currentQuality = $defaultStream['quality'] ?? ($this->episodeDetail['quality'] ?? '720p HD');
        $this->currentServer = $defaultStream['server'] ?? 'Server Utama';
        $this->activeMirrorIndex = 0;

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v2:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime V2');
            $thumbnail = $this->seriesDetail['thumbnail'] ?? null;
            $epNum = (string) ($this->episodeDetail['episode_number'] ?? '1');

            $history = $user->watchHistories()
                ->where('item_id', 'anime_v2:' . $this->animeSlug)
                ->first();

            $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
            if (!in_array($epNum, $watchedList, true)) {
                $watchedList[] = $epNum;
            }

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => 'anime_v2:' . $this->animeSlug,
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

    public function switchMirror(int $index): void
    {
        if (!isset($this->mirrors[$index])) {
            return;
        }

        $target = $this->mirrors[$index];
        $this->activeMirrorIndex = $index;

        $scraper = new OtakudesuScraperService();
        $resolved = $scraper->resolveMirror(
            $this->episodeDetail['episode_url'],
            $target['id'],
            $target['i'],
            $target['q'],
            $target['server']
        );

        if ($resolved && !empty($resolved['stream_url'])) {
            $this->currentStreamUrl = $resolved['stream_url'];
            $this->currentDirectMp4 = $resolved['direct_mp4'] ?? null;
            $this->isDirectMp4 = !empty($this->currentDirectMp4);
            $this->currentQuality = $resolved['quality'] ?? $target['quality'];
            $this->currentServer = $resolved['server'] ?? $target['server'];

            \Filament\Notifications\Notification::make()
                ->title("Beralih ke Server {$target['label']}")
                ->success()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title("Gagal memuat mirror {$target['label']}. Silakan coba mirror lain.")
                ->danger()
                ->send();
        }
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v2:' . $this->animeSlug)
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
                'item_id' => 'v2:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->seriesDetail['title'] ?? 'Anime',
                'thumbnail' => $this->seriesDetail['thumbnail'] ?? null,
                'url' => \App\Filament\Streaming\Pages\AnimeDetailV2::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
