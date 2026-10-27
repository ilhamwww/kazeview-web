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
        $anime_slug = preg_replace('/^(?:anime_v\d+:|v\d+:|anime:)/', '', $anime_slug);
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $this->loadSeriesDetail();

        $resolved = $this->resolveEpisodeData($this->episodeSlug);
        if (!$resolved) {
            abort(404, 'Episode tidak ditemukan');
        }

        $this->applyEpisodeData($resolved);

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v1:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        $this->saveWatchHistory($this->episodeSlug);
    }

    protected function loadSeriesDetail(): void
    {
        $scraper = new MirainimeScraperService();
        $seriesCacheKey = 'v1_detail_' . md5($this->animeSlug);
        $this->seriesDetail = Cache::remember($seriesCacheKey, 43200, function () use ($scraper) {
            $d = $scraper->scrapeAnimeSeriesDetail($this->animeSlug);
            if ($d && (($d['title'] ?? '') === '404' || empty($d['episodes']))) {
                return null;
            }
            return $d;
        });

        // Series Detail Fallback ke Otakudesu jika 404 / kosong
        if (!$this->seriesDetail || ($this->seriesDetail['title'] ?? '') === '404' || empty($this->seriesDetail['episodes'])) {
            $otakuScraper = new \App\Services\OtakudesuScraperService();
            $otakuSeries = Cache::remember('otaku_series_' . md5($this->animeSlug), 43200, function () use ($otakuScraper) {
                return $otakuScraper->scrapeAnimeSeriesDetail('https://otakudesu.blog/anime/' . $this->animeSlug . '/');
            });
            if ($otakuSeries && !empty($otakuSeries['title'])) {
                $rawEpisodes = $otakuSeries['episodes'] ?? [];
                usort($rawEpisodes, function ($a, $b) {
                    $ea = (int)preg_replace('/[^\d]/', '', (string)($a['episode'] ?? '0'));
                    $eb = (int)preg_replace('/[^\d]/', '', (string)($b['episode'] ?? '0'));
                    return $ea <=> $eb;
                });
                $this->seriesDetail = [
                    'title' => $otakuSeries['title'] ?? '',
                    'thumbnail' => $otakuSeries['thumbnail'] ?? ($otakuSeries['poster'] ?? ''),
                    'episodes' => array_map(function ($ep) {
                        return [
                            'episode' => (string)($ep['episode'] ?? '1'),
                            'title' => $ep['title'] ?? ('Episode ' . ($ep['episode'] ?? '1')),
                        ];
                    }, $rawEpisodes),
                ];
            }
        }

        $animeTitle = $this->seriesDetail['title'] ?? '';
        $this->metadata = AnimeMetadataService::getMetadata($animeTitle);
    }

    public function resolveEpisodeData(string $epSlug): ?array
    {
        $cacheKey = "v1_assembled_{$this->animeSlug}_{$epSlug}";
        return Cache::remember($cacheKey, 3600, function () use ($epSlug) {
            $scraper = new MirainimeScraperService();
            $epCacheKey = "v1_ep_{$this->animeSlug}_{$epSlug}";

            // 1. Coba ambil episode langsung dari Mirainime
            $epDetail = Cache::remember($epCacheKey, 3600, function () use ($scraper, $epSlug) {
                $data = $scraper->scrapeEpisodeDetail($this->animeSlug, $epSlug);
                if ($data && (($data['title'] ?? '') === '404' || empty($data['video_url']))) {
                    return null;
                }
                return $data;
            });

            // 2. Jika tidak ditemukan atau 404, coba cari lewat Otakudesu & mapping judul Mirainime
            if (!$epDetail || empty($epDetail['video_url']) || ($epDetail['title'] ?? '') === '404') {
                $otakuScraper = new \App\Services\OtakudesuScraperService();
                $otakuSeries = Cache::remember('otaku_series_' . md5($this->animeSlug), 43200, function () use ($otakuScraper) {
                    return $otakuScraper->scrapeAnimeSeriesDetail('https://otakudesu.blog/anime/' . $this->animeSlug . '/');
                });

                if ($otakuSeries && !empty($otakuSeries['title'])) {
                    $cleanTitle = AnimeMetadataService::cleanTitle($otakuSeries['title']);
                    $miraiMatches = $scraper->searchAnime($cleanTitle);
                    if (!empty($miraiMatches[0]['slug'])) {
                        $matchedMiraiSlug = $miraiMatches[0]['slug'];
                        $freshMiraiEp = $scraper->scrapeEpisodeDetail($matchedMiraiSlug, $epSlug);
                        if ($freshMiraiEp && !empty($freshMiraiEp['video_url']) && ($freshMiraiEp['title'] ?? '') !== '404') {
                            $epDetail = $freshMiraiEp;
                            Cache::put($epCacheKey, $freshMiraiEp, 3600);
                        }
                    }
                }

                // Jika masih belum ada di Mirainime, ambil stream video dari Otakudesu
                if (!$epDetail || empty($epDetail['video_url']) || ($epDetail['title'] ?? '') === '404') {
                    if ($otakuSeries && !empty($otakuSeries['episodes'])) {
                        $matchedEp = null;
                        foreach ($otakuSeries['episodes'] as $ep) {
                            $epNum = (string)($ep['episode'] ?? '');
                            if ($epNum === (string)$epSlug || str_contains($ep['title'] ?? '', 'Episode ' . $epSlug)) {
                                $matchedEp = $ep;
                                break;
                            }
                        }
                        if ($matchedEp && !empty($matchedEp['url'])) {
                            $epFull = $otakuScraper->scrapeEpisodeDetail($matchedEp['url']);
                            if ($epFull) {
                                $stream = $epFull['video_url'] ?? ($epFull['default_stream']['stream_url'] ?? ($epFull['direct_mp4'] ?? ($epFull['iframe_url'] ?? '')));
                                $epDetail = [
                                    'title' => $matchedEp['title'] ?? (($otakuSeries['title'] ?? 'Anime') . ' Episode ' . $epSlug),
                                    'episode_number' => $epSlug,
                                    'video_url' => $stream,
                                    'anime_slug' => $this->animeSlug,
                                    'source_url' => $matchedEp['url'],
                                    'mirrors' => $epFull['mirrors'] ?? [],
                                    'iframe_url' => $epFull['iframe_url'] ?? '',
                                ];
                            }
                        }
                    }
                }
            }

            if (!$epDetail) {
                return null;
            }

            $animeTitle = $this->seriesDetail['title'] ?? ($epDetail['title'] ?? '');
            $downloads = AnimeDownloadResolverService::getDownloads($animeTitle, $epSlug);

            $rawStream = $epDetail['video_url'] ?? '';
            $currentStreamUrl = '';
            $isDirect = false;
            if (!empty($rawStream)) {
                if (str_starts_with($rawStream, 'http') && (str_contains($rawStream, '.mp4') || str_contains($rawStream, 'suzune.web.id') || str_contains($rawStream, 'erisboreas.my.id') || str_contains($rawStream, 'archive.org'))) {
                    $isDirect = true;
                    if (str_contains($rawStream, '/api/proxy-stream')) {
                        $currentStreamUrl = $rawStream;
                    } else {
                        $currentStreamUrl = '/api/proxy-stream?url=' . urlencode($rawStream);
                    }
                } else {
                    $currentStreamUrl = $rawStream;
                }
            }

            // Servers List setup (Server 1 Direct FHD as default)
            $servers = [];
            if (!empty($currentStreamUrl) && $isDirect) {
                $servers[] = [
                    'id' => 'server1',
                    'name' => 'Server 1 (Direct FHD - Slow)',
                    'type' => 'mp4',
                    'stream_url' => $currentStreamUrl,
                    'embed_url' => '',
                ];
            }

            // Server Otakudesu (Alt HD - Fast / DesuStream / Embed)
            $otakuStream = $downloads['stream_url'] ?? ($epDetail['direct_mp4'] ?? null);
            $otakuIframe = $downloads['iframe_url'] ?? ($epDetail['iframe_url'] ?? null);

            if (!empty($otakuStream)) {
                $servers[] = [
                    'id' => 'server_otaku_direct',
                    'name' => 'Server ' . (count($servers) + 1) . ' (Alt HD - Fast)',
                    'type' => 'mp4',
                    'stream_url' => $otakuStream,
                    'embed_url' => '',
                ];
            } elseif (!empty($otakuIframe)) {
                $servers[] = [
                    'id' => 'server_otaku_embed',
                    'name' => 'Server ' . (count($servers) + 1) . ' (DesuStream)',
                    'type' => 'embed',
                    'stream_url' => '',
                    'embed_url' => $otakuIframe,
                ];
            }

            // Mirror servers
            $mirrors = $epDetail['mirrors'] ?? ($downloads['mirrors'] ?? []);
            foreach ($mirrors as $idx => $m) {
                $mUrl = $m['url'] ?? '';
                if (!empty($mUrl) && str_starts_with($mUrl, 'http')) {
                    $servers[] = [
                        'id' => 'server_mirror_' . ($idx + 1),
                        'name' => 'Server ' . (count($servers) + 1) . ' (' . ($m['name'] ?? ($m['label'] ?? 'Mirror')) . ')',
                        'type' => 'embed',
                        'stream_url' => '',
                        'embed_url' => $mUrl,
                    ];
                }
            }

            return [
                'episode' => (string)$epSlug,
                'title' => $epDetail['title'] ?? ($animeTitle . ' Episode ' . $epSlug),
                'stream_url' => $currentStreamUrl,
                'servers' => $servers,
                'downloads' => $downloads,
                'url' => url('/anime/' . $this->animeSlug . '/episode/' . $epSlug),
            ];
        });
    }

    protected function applyEpisodeData(array $data): void
    {
        $this->episodeSlug = (string)($data['episode'] ?? $this->episodeSlug);
        $this->episodeDetail = [
            'title' => $data['title'] ?? '',
            'episode_number' => $this->episodeSlug,
            'video_url' => $data['stream_url'] ?? '',
            'downloads' => $data['downloads'] ?? null,
        ];
        $this->currentStreamUrl = $data['stream_url'] ?? '';
        $this->servers = $data['servers'] ?? [];
        $this->downloads = $data['downloads'] ?? null;

        $this->activeServer = 'server1';
        $this->isEmbed = false;
        $this->currentEmbedUrl = '';
        if (!empty($this->servers)) {
            $active = collect($this->servers)->firstWhere('id', 'server1') ?? $this->servers[0];
            $this->activeServer = $active['id'];
            $this->isEmbed = ($active['type'] === 'embed');
            $this->currentEmbedUrl = $active['embed_url'] ?? '';
            if (!$this->isEmbed && !empty($active['stream_url'])) {
                $this->currentStreamUrl = $active['stream_url'];
            }
        }

        // Determine Prev & Next
        $episodes = $this->seriesDetail['episodes'] ?? [];
        $currentIndex = null;
        foreach ($episodes as $idx => $ep) {
            if ((string)($ep['episode'] ?? '') === (string)$this->episodeSlug) {
                $currentIndex = $idx;
                break;
            }
        }

        $this->prevEpisodeSlug = null;
        $this->nextEpisodeSlug = null;
        if ($currentIndex !== null) {
            $this->prevEpisodeSlug = isset($episodes[$currentIndex - 1]) ? (string)($episodes[$currentIndex - 1]['episode'] ?? null) : null;
            $this->nextEpisodeSlug = isset($episodes[$currentIndex + 1]) ? (string)($episodes[$currentIndex + 1]['episode'] ?? null) : null;
        }
    }

    protected function saveWatchHistory(string $epNum): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime');
        $thumbnail = $this->seriesDetail['thumbnail'] ?? ($this->metadata['poster'] ?? null);

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
                'url' => url('/anime/' . $this->animeSlug . '/episode/' . $epNum),
            ]
        );
    }

    /**
     * Resolves adjacent episodes in background for zero-lag buffer (Dracin-style).
     */
    public function prefetchNeighborEpisodes(array $epSlugs): array
    {
        $this->skipRender();
        $results = [];
        foreach ($epSlugs as $slug) {
            $slugStr = (string)$slug;
            if ($slugStr === '' || $slugStr === '0') continue;
            try {
                $data = $this->resolveEpisodeData($slugStr);
                if ($data) {
                    $results[$slugStr] = $data;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return $results;
    }

    public function fetchEpisodeData(string $epSlug): ?array
    {
        $this->skipRender();
        return $this->resolveEpisodeData((string)$epSlug);
    }

    public function recordEpisodeWatched(string $epSlug): void
    {
        $this->skipRender();
        $this->saveWatchHistory((string)$epSlug);
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
