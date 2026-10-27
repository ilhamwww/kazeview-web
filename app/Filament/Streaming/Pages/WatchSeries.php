<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

use Livewire\Attributes\Url;

class WatchSeries extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Series';
    protected static ?string $title = 'Watch Series';
    protected static ?string $slug = 'series/{slug}/season/{seasonNumber}/episode/{episodeNumber}';
    protected static string $view = 'filament.streaming.pages.watch-series';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public $seriesSlug;
    public $seasonNumber;
    public $episodeNumber;
    public ?array $series = null;
    public ?array $streamData = null;
    public ?array $activeEpisode = null;
    public array $episodes = [];
    public array $servers = [];
    public float $savedProgressSeconds = 0;

    public function mount($slug, $seasonNumber, $episodeNumber)
    {
        $this->seriesSlug = $slug;
        $this->seasonNumber = (int)$seasonNumber;
        $this->episodeNumber = (int)$episodeNumber;

        $this->series = StreamingService::getSeriesDetail($slug);

        if (!$this->series) {
            abort(404);
        }

        $defaultSeasonNumber = $this->series['defaultSeason']['seasonNumber'] ?? 1;

        if ($this->seasonNumber === (int)$defaultSeasonNumber) {
            $this->episodes = $this->series['defaultSeason']['episodes'] ?? [];
        } else {
            $seasonDetail = StreamingService::getSeriesSeasonDetail($slug, $this->seasonNumber);
            if ($seasonDetail && isset($seasonDetail['season']['episodes'])) {
                $this->episodes = $seasonDetail['season']['episodes'];
            }
        }

        // Find active episode info from current season's episodes
        foreach ($this->episodes as $ep) {
            if ((int)$ep['episodeNumber'] === $this->episodeNumber) {
                $this->activeEpisode = $ep;
                break;
            }
        }

        if ($this->activeEpisode) {
            // Server-side subscription check
            if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
                $this->streamData = [
                    'video_url' => '',
                    'subtitles' => [],
                    'title' => $this->activeEpisode['name'] ?? '',
                ];
                $this->servers = [];
                return;
            }

            // 1. Fetch stream (Native IDLIX via NUC Resolver / Rebahin)
            $cachedStream = !empty($this->activeEpisode['id']) ? StreamingService::getValidCachedEpisodeStream($this->activeEpisode['id']) : null;
            if (!$cachedStream && !empty($this->activeEpisode['id'])) {
                $cachedStream = StreamingService::getEpisodeStream($this->activeEpisode['id']);
            }

            if ($cachedStream) {
                $this->streamData = $cachedStream;
            } else {
                $this->streamData = [
                    'video_url' => '',
                    'subtitles' => [],
                    'title' => $this->activeEpisode['name'] ?? '',
                ];
            }

            // 2. Setup servers (HLS vs Embed)
            $servers = [];
            $hasNativeStream = !empty($this->streamData['video_url']);
            $isEmbedEpisode = !empty($this->activeEpisode['servers']);

            if ($hasNativeStream) {
                $servers[] = [
                    'id' => 'server_direct',
                    'name' => 'Server 1 (Native HD - Bebas Iklan)',
                    'type' => 'hls',
                    'embed_url' => '',
                ];
            }

            if ($isEmbedEpisode) {
                if (!$hasNativeStream) {
                    $servers[] = [
                        'id' => 'server1',
                        'name' => 'Server 1 (HD)',
                        'type' => 'hls',
                        'embed_url' => '',
                    ];
                }

                foreach ($this->activeEpisode['servers'] as $idx => $s) {
                    $servers[] = [
                        'id' => 'server_mirror_' . ($idx + 1),
                        'name' => 'Server ' . (count($servers) + 1) . ' (Mirror)',
                        'type' => 'embed',
                        'embed_url' => $s['embed_url'] ?? '',
                    ];
                }
            } else {
                // Universal TMDB Embed Engine for Global TV Series
                $tmdbId = $this->series['tmdbId'] ?? ($this->series['tmdb_id'] ?? null);
                if (!$tmdbId && !empty($this->series['id']) && is_numeric($this->series['id'])) {
                    $tmdbId = $this->series['id'];
                }
                $season = (int)$this->seasonNumber;
                $episode = (int)$this->episodeNumber;

                if ($tmdbId) {
                    $sOffset = count($servers) + 1;
                    $servers[] = [
                        'id' => 'server_vidlink',
                        'name' => "Server {$sOffset} (VidLink HD)",
                        'type' => 'embed',
                        'embed_url' => "https://vidlink.pro/tv/{$tmdbId}/{$season}/{$episode}?primaryColor=E50914&nextbutton=true",
                    ];
                    $sOffset++;
                    $servers[] = [
                        'id' => 'server_2embed',
                        'name' => "Server {$sOffset} (2Embed)",
                        'type' => 'embed',
                        'embed_url' => "https://www.2embed.cc/embedtv/{$tmdbId}&s={$season}&e={$episode}",
                    ];
                    $sOffset++;
                    $servers[] = [
                        'id' => 'server_multiembed',
                        'name' => "Server {$sOffset} (MultiEmbed)",
                        'type' => 'embed',
                        'embed_url' => "https://multiembed.mov/?video_id={$tmdbId}&tmdb=1&s={$season}&e={$episode}",
                    ];
                    $sOffset++;
                    $servers[] = [
                        'id' => 'server_vidsrc',
                        'name' => "Server {$sOffset} (VidSrc)",
                        'type' => 'embed',
                        'embed_url' => "https://vidsrc.sh/embed/tv/{$tmdbId}/{$season}/{$episode}",
                    ];
                } elseif (!$hasNativeStream) {
                    $servers[] = [
                        'id' => 'server1',
                        'name' => 'Server 1 (HD)',
                        'type' => 'hls',
                        'embed_url' => '',
                    ];
                }

                if (!empty($this->activeEpisode['mirror_servers'])) {
                    foreach ($this->activeEpisode['mirror_servers'] as $idx => $s) {
                        $servers[] = [
                            'id' => 'server_mirror_' . ($idx + 1),
                            'name' => 'Server ' . (count($servers) + 1) . ' (Mirror)',
                            'type' => 'embed',
                            'embed_url' => $s['embed_url'] ?? '',
                        ];
                    }
                }
            }
            $this->servers = $servers;

            // Record Watch History
            if (auth()->check()) {
                $user = auth()->user();
                $seriesTitle = $this->series['title'] ?? 'Series';
                $thumbnail = $this->series['posterPath'] ?? ($this->series['poster_path'] ?? ($this->series['poster'] ?? ($this->series['thumbnail'] ?? ($this->series['backdropPath'] ?? null))));
                $epNum = (string) ($this->activeEpisode['episodeNumber'] ?? $this->episodeNumber);

                $history = $user->watchHistories()
                    ->where('item_id', $this->seriesSlug)
                    ->where('item_type', 'series')
                    ->first();

                $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
                if (!in_array($epNum, $watchedList, true)) {
                    $watchedList[] = $epNum;
                }

                if ($history && $history->last_episode == $epNum && $history->progress_seconds > 10 && $history->progress_percent < 95) {
                    $this->savedProgressSeconds = (float) $history->progress_seconds;
                }

                $user->watchHistories()->updateOrCreate(
                    [
                        'item_id' => $this->seriesSlug,
                        'item_type' => 'series',
                    ],
                    [
                        'title' => $seriesTitle,
                        'thumbnail' => $thumbnail,
                        'last_episode' => $epNum,
                        'watched_episodes' => $watchedList,
                        'url' => request()->url(),
                    ]
                );
            }
        } else {
            abort(404);
        }
    }
}
