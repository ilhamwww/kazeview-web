<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimeDownloadResolverService;
use App\Services\AnimeMetadataService;
use App\Services\MirainimeScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnime extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Nonton Anime';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;
    public ?array $metadata = null;
    public ?array $downloads = null;

    public array $servers = [];
    public string $activeServer = 'server1';
    public bool $isEmbed = false;
    public string $currentEmbedUrl = '';

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

        $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? '');

        // Official metadata
        $this->metadata = AnimeMetadataService::getMetadata($animeTitle);

        // Otakudesu downloads for this episode
        $this->downloads = AnimeDownloadResolverService::getDownloads($animeTitle, $this->episodeSlug);

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

        // Servers List setup (Server 1 Direct HD as default)
        $servers = [
            [
                'id' => 'server1',
                'name' => 'Server 1 (Direct HD - V1)',
                'type' => 'mp4',
                'stream_url' => $this->currentStreamUrl,
                'embed_url' => '',
            ]
        ];

        // Otakudesu Hub if available
        if (!empty($this->downloads['otaku_url'])) {
            $servers[] = [
                'id' => 'server_otaku',
                'name' => 'Server 2 (Otakudesu Hub)',
                'type' => 'embed',
                'stream_url' => '',
                'embed_url' => $this->downloads['otaku_url'],
            ];
        }

        // Mirror servers if available in episodeDetail
        if (!empty($this->episodeDetail['mirrors'])) {
            foreach ($this->episodeDetail['mirrors'] as $idx => $m) {
                $servers[] = [
                    'id' => 'server_mirror_' . ($idx + 1),
                    'name' => 'Server ' . ($idx + 2) . ' (' . ($m['name'] ?? 'Mirror') . ')',
                    'type' => 'embed',
                    'stream_url' => '',
                    'embed_url' => $m['url'] ?? '',
                ];
            }
        }

        $this->servers = $servers;
        $this->activeServer = 'server1';
        $this->isEmbed = false;

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

    public function switchServer(string $serverId): void
    {
        $this->activeServer = $serverId;
        foreach ($this->servers as $s) {
            if ($s['id'] === $serverId) {
                $this->isEmbed = ($s['type'] === 'embed');
                $this->currentEmbedUrl = $s['embed_url'] ?? '';
                if (!$this->isEmbed && !empty($s['stream_url'])) {
                    $this->currentStreamUrl = $s['stream_url'];
                }
                break;
            }
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
