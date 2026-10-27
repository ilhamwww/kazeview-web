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

        // 1. Coba ambil episode langsung dari Mirainime
        $cacheKey = "v1_ep_{$anime_slug}_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 3600, function () use ($scraper, $anime_slug, $episode_slug) {
            $data = $scraper->scrapeEpisodeDetail($anime_slug, $episode_slug);
            if ($data && (($data['title'] ?? '') === '404' || empty($data['video_url']))) {
                return null;
            }
            return $data;
        });

        // 2. Jika tidak ditemukan atau 404, coba cari lewat Otakudesu & mapping judul Mirainime
        if (!$this->episodeDetail || empty($this->episodeDetail['video_url']) || ($this->episodeDetail['title'] ?? '') === '404') {
            $otakuScraper = new \App\Services\OtakudesuScraperService();
            $otakuSeries = Cache::remember('otaku_series_' . md5($anime_slug), 43200, function () use ($otakuScraper, $anime_slug) {
                return $otakuScraper->scrapeAnimeSeriesDetail('https://otakudesu.blog/anime/' . $anime_slug . '/');
            });

            // Coba cari apakah judul ini ada di Mirainime dengan slug lain
            if ($otakuSeries && !empty($otakuSeries['title'])) {
                $cleanTitle = AnimeMetadataService::cleanTitle($otakuSeries['title']);
                $miraiMatches = $scraper->searchAnime($cleanTitle);
                if (!empty($miraiMatches[0]['slug'])) {
                    $matchedMiraiSlug = $miraiMatches[0]['slug'];
                    $freshMiraiEp = $scraper->scrapeEpisodeDetail($matchedMiraiSlug, $episode_slug);
                    if ($freshMiraiEp && !empty($freshMiraiEp['video_url']) && ($freshMiraiEp['title'] ?? '') !== '404') {
                        $this->episodeDetail = $freshMiraiEp;
                        $anime_slug = $matchedMiraiSlug;
                        $this->animeSlug = $matchedMiraiSlug;
                        Cache::put($cacheKey, $freshMiraiEp, 3600);
                    }
                }
            }

            // Jika masih belum ada di Mirainime, ambil stream video dari Otakudesu
            if (!$this->episodeDetail || empty($this->episodeDetail['video_url']) || ($this->episodeDetail['title'] ?? '') === '404') {
                if ($otakuSeries && !empty($otakuSeries['episodes'])) {
                    $matchedEp = null;
                    foreach ($otakuSeries['episodes'] as $ep) {
                        $epNum = (string)($ep['episode'] ?? '');
                        if ($epNum === (string)$episode_slug || str_contains($ep['title'] ?? '', 'Episode ' . $episode_slug)) {
                            $matchedEp = $ep;
                            break;
                        }
                    }
                    if ($matchedEp && !empty($matchedEp['url'])) {
                        $epFull = $otakuScraper->scrapeEpisodeDetail($matchedEp['url']);
                        if ($epFull) {
                            $stream = $epFull['video_url'] ?? ($epFull['default_stream']['stream_url'] ?? ($epFull['direct_mp4'] ?? ($epFull['iframe_url'] ?? '')));
                            $this->episodeDetail = [
                                'title' => $matchedEp['title'] ?? ($otakuSeries['title'] . ' Episode ' . $episode_slug),
                                'episode_number' => $episode_slug,
                                'video_url' => $stream,
                                'anime_slug' => $anime_slug,
                                'source_url' => $matchedEp['url'],
                                'mirrors' => $epFull['mirrors'] ?? [],
                                'iframe_url' => $epFull['iframe_url'] ?? '',
                            ];
                        }
                    }
                }
            }
        }

        if (!$this->episodeDetail) {
            abort(404, 'Episode tidak ditemukan');
        }

        // Load series detail
        $seriesCacheKey = 'v1_detail_' . md5($anime_slug);
        $this->seriesDetail = Cache::remember($seriesCacheKey, 43200, function () use ($scraper, $anime_slug) {
            $d = $scraper->scrapeAnimeSeriesDetail($anime_slug);
            if ($d && (($d['title'] ?? '') === '404' || empty($d['episodes']))) {
                return null;
            }
            return $d;
        });

        // Series Detail Fallback ke Otakudesu jika 404 / kosong
        if (!$this->seriesDetail || ($this->seriesDetail['title'] ?? '') === '404' || empty($this->seriesDetail['episodes'])) {
            $otakuScraper = new \App\Services\OtakudesuScraperService();
            $otakuSeries = Cache::remember('otaku_series_' . md5($anime_slug), 43200, function () use ($otakuScraper, $anime_slug) {
                return $otakuScraper->scrapeAnimeSeriesDetail('https://otakudesu.blog/anime/' . $anime_slug . '/');
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
        $isDirect = false;
        if (!empty($rawStream)) {
            if (str_starts_with($rawStream, 'http') && (str_contains($rawStream, '.mp4') || str_contains($rawStream, 'suzune.web.id') || str_contains($rawStream, 'erisboreas.my.id') || str_contains($rawStream, 'archive.org'))) {
                $isDirect = true;
                if (str_contains($rawStream, '/api/proxy-stream')) {
                    $this->currentStreamUrl = $rawStream;
                } else {
                    $this->currentStreamUrl = url('/api/proxy-stream?url=' . urlencode($rawStream));
                }
            } else {
                $this->currentStreamUrl = $rawStream;
            }
        } else {
            $this->currentStreamUrl = '';
        }

        // Servers List setup (Server 1 Direct HD as default)
        $servers = [];
        if (!empty($this->currentStreamUrl) && $isDirect) {
            $servers[] = [
                'id' => 'server1',
                'name' => 'Server 1 (Direct HD - V1)',
                'type' => 'mp4',
                'stream_url' => $this->currentStreamUrl,
                'embed_url' => '',
            ];
        }

        // Server Otakudesu (Direct HD / DesuStream / Embed)
        $otakuStream = $this->downloads['stream_url'] ?? ($this->episodeDetail['direct_mp4'] ?? null);
        $otakuIframe = $this->downloads['iframe_url'] ?? ($this->episodeDetail['iframe_url'] ?? null);

        if (!empty($otakuStream)) {
            // Direct MP4 dari Otakudesu / Archive.org
            $servers[] = [
                'id' => 'server_otaku_direct',
                'name' => 'Server ' . (count($servers) + 1) . ' (Alt Direct HD)',
                'type' => 'mp4',
                'stream_url' => $otakuStream,
                'embed_url' => '',
            ];
        } elseif (!empty($otakuIframe)) {
            // Embed DesuStream jika ada
            $servers[] = [
                'id' => 'server_otaku_embed',
                'name' => 'Server ' . (count($servers) + 1) . ' (DesuStream)',
                'type' => 'embed',
                'stream_url' => '',
                'embed_url' => $otakuIframe,
            ];
        }

        // Mirror servers if available in episodeDetail or downloads
        $mirrors = $this->episodeDetail['mirrors'] ?? ($this->downloads['mirrors'] ?? []);
        if (!empty($mirrors)) {
            foreach ($mirrors as $idx => $m) {
                if (!empty($m['url'])) {
                    $servers[] = [
                        'id' => 'server_mirror_' . ($idx + 1),
                        'name' => 'Server ' . (count($servers) + 1) . ' (' . ($m['name'] ?? ($m['label'] ?? 'Mirror')) . ')',
                        'type' => 'embed',
                        'stream_url' => '',
                        'embed_url' => $m['url'],
                    ];
                }
            }
        }

        $this->servers = $servers;
        if (!empty($servers)) {
            $this->activeServer = $servers[0]['id'];
            $this->isEmbed = ($servers[0]['type'] === 'embed');
            $this->currentEmbedUrl = $servers[0]['embed_url'] ?? '';
            if (!$this->isEmbed && !empty($servers[0]['stream_url'])) {
                $this->currentStreamUrl = $servers[0]['stream_url'];
            }
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
