<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\IndonesiaMediaItem;
use App\Services\NartoMovieBoxService;
use App\Services\RebahinService;
use App\Services\IndoxxiTechService;
use App\Services\PencuriMovieService;
use App\Services\StreamingService;

class WatchIndonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Indonesia';
    protected static ?string $title = 'Nonton Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia/{server}/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $server = 'server-1';
    public string $slugParam = '';
    public string $serverLabel = 'Server 1';
    public ?array $media = null;
    public array $servers = [];
    public bool $isSeries = false;
    public array $episodes = [];
    public int $currentEpisode = 1;
    public string $currentEpisodeTitle = '';
    public bool $isFavorited = false;
    public float $savedProgressSeconds = 0;

    public function mount(string $server, string $slug): void
    {
        $this->server = strtolower($server);
        $this->slugParam = $slug;

        $serverMap = [
            'server-1' => [
                'prefix' => 'mb-',
                'source' => 'moviebox',
                'label' => 'Server 1 (1080p Ultra HD)',
                'badge' => 'Server 1',
            ],
            'server-2' => [
                'prefix' => 'rb-',
                'source' => 'server2',
                'label' => 'Server 2 (Arsip Lengkap)',
                'badge' => 'Server 2',
            ],
            'server-3' => [
                'prefix' => 'ix-',
                'source' => 'indoxxi',
                'label' => 'Server 3 (Mirror)',
                'badge' => 'Server 3',
            ],
            'server-4' => [
                'prefix' => 'pm-',
                'source' => 'pencurimovie',
                'label' => 'Server 4 (Arsip Bioskop)',
                'badge' => 'Server 4',
            ],
        ];

        if (!isset($serverMap[$this->server])) {
            abort(404, 'Server tidak ditemukan');
        }

        $info = $serverMap[$this->server];
        $this->serverLabel = $info['label'];
        $prefix = $info['prefix'];

        // Normalisasi slug
        $fullSlug = str_starts_with($slug, $prefix) ? $slug : $prefix . $slug;
        $rawSlug = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $fullSlug);

        // Cari di database vault lokal dulu untuk mengecek metadata & tipe
        $vaultItem = IndonesiaMediaItem::where('slug', $fullSlug)
            ->orWhere(function ($q) use ($rawSlug, $info) {
                $q->where('source', $info['source'])
                  ->where('raw_slug', $rawSlug);
            })
            ->first();

        $this->isSeries = $vaultItem && $vaultItem->type === 'series';

        // 1. SERVER 1 (MOVIEBOX HD) - HANYA RESOURCE SERVER 1
        if ($this->server === 'server-1') {
            if ($this->isSeries || str_contains($fullSlug, 'series') || str_contains($fullSlug, 'season')) {
                $detail = NartoMovieBoxService::getSeriesDetail($fullSlug);
                if ($detail) {
                    $this->isSeries = true;
                    $this->media = $detail;

                    $rawEps = $detail['episodes'] ?? ($detail['defaultSeason']['episodes'] ?? []);
                    $this->episodes = [];
                    foreach ($rawEps as $re) {
                        $num = (int) ($re['episodeNumber'] ?? ($re['number'] ?? ($re['episode_number'] ?? 1)));
                        $name = $re['name'] ?? ($re['title'] ?? ('Episode ' . $num));
                        $streamUrl = $re['stream_url'] ?? ($re['play_url'] ?? '');
                        $subtitles = $re['subtitles'] ?? ($re['multi_subtitles'] ?? []);
                        $this->episodes[] = [
                            'episode_number' => $num,
                            'episodeNumber' => $num,
                            'title' => $name,
                            'name' => $name,
                            'stream_url' => $streamUrl,
                            'subtitles' => $subtitles,
                            'stillPath' => $re['stillPath'] ?? ($detail['poster'] ?? ''),
                        ];
                    }

                    // Set video_url awal ke Episode 1
                    $firstEp = $this->episodes[0] ?? null;
                    if ($firstEp) {
                        $this->media['video_url'] = $firstEp['stream_url'] ?? '';
                        $this->media['subtitles'] = $firstEp['subtitles'] ?? [];
                        $this->currentEpisode = $firstEp['episode_number'] ?? 1;
                        $this->currentEpisodeTitle = $firstEp['title'] ?? 'Episode 1';
                    }

                    $this->servers = [
                        [
                            'id' => 'server_mb_hls',
                            'name' => 'Server 1 (1080p Ultra HD)',
                            'type' => 'hls',
                            'video_url' => $this->media['video_url'] ?? '',
                            'embed_url' => '',
                        ]
                    ];
                }
            }

            if (!$this->media) {
                $detail = NartoMovieBoxService::getMovieDetail($fullSlug);
                if ($detail) {
                    $this->media = $detail;
                    $this->servers = [
                        [
                            'id' => 'server_mb_hls',
                            'name' => 'Server 1 (1080p Ultra HD)',
                            'type' => 'hls',
                            'video_url' => $detail['video_url'] ?? '',
                            'embed_url' => '',
                        ]
                    ];
                }
            }
        }
        // 2. SERVER 2 (REBAHIN) - HANYA RESOURCE SERVER 2
        elseif ($this->server === 'server-2') {
            if ($this->isSeries || str_contains($fullSlug, 'series') || str_contains($fullSlug, 'season')) {
                $detail = RebahinService::getSeriesDetail($fullSlug);
                if ($detail) {
                    $this->isSeries = true;
                    $this->media = $detail;
                    $rawEps = $detail['episodes'] ?? ($detail['defaultSeason']['episodes'] ?? []);
                    $this->episodes = [];
                    foreach ($rawEps as $re) {
                        $num = (int) ($re['episodeNumber'] ?? ($re['number'] ?? 1));
                        $name = $re['name'] ?? ($re['title'] ?? ('Episode ' . $num));
                        $epServers = [];
                        foreach ($re['servers'] ?? [] as $idx => $s) {
                            $epServers[] = [
                                'id' => 'server_rb_' . ($idx + 1),
                                'name' => 'Server 2 (' . ($idx === 0 ? 'HD' : 'Mirror ' . $idx) . ')',
                                'type' => 'embed',
                                'embed_url' => $s['embed_url'] ?? '',
                            ];
                        }
                        if (empty($epServers)) {
                            $epServers[] = [
                                'id' => 'server_rb_1',
                                'name' => 'Server 2 (HD)',
                                'type' => 'embed',
                                'embed_url' => url('/api/streaming/embed?url=' . urlencode('https://178.211.139.171/embed/' . $rawSlug)),
                            ];
                        }
                        $firstServer = $epServers[0] ?? null;
                        $this->episodes[] = [
                            'episode_number' => $num,
                            'episodeNumber' => $num,
                            'title' => $name,
                            'name' => $name,
                            'servers' => $epServers,
                            'embed_url' => $firstServer['embed_url'] ?? '',
                            'stillPath' => $re['stillPath'] ?? ($detail['poster'] ?? ''),
                        ];
                    }

                    $firstEp = $this->episodes[0] ?? null;
                    if ($firstEp) {
                        $this->servers = $firstEp['servers'] ?? [];
                        $this->currentEpisode = $firstEp['episode_number'] ?? 1;
                        $this->currentEpisodeTitle = $firstEp['title'] ?? 'Episode 1';
                    }
                }
            }

            if (!$this->media) {
                $detail = RebahinService::getMovieDetail($fullSlug);
                if ($detail) {
                    $this->media = $detail;
                    $rawServers = $detail['servers'] ?? [];
                    $this->servers = [];
                    foreach ($rawServers as $s) {
                        $this->servers[] = [
                            'id' => $s['id'] ?? 'server1',
                            'name' => str_replace('Server 1', 'Server 2', $s['name'] ?? 'Server 2 (HD)'),
                            'type' => 'embed',
                            'embed_url' => $s['embed_url'] ?? '',
                        ];
                    }
                    if (empty($this->servers)) {
                        $this->servers[] = [
                            'id' => 'server_rb_default',
                            'name' => 'Server 2 (HD)',
                            'type' => 'embed',
                            'embed_url' => $detail['url'] ?? '',
                        ];
                    }
                }
            }
        }
        // 3. SERVER 3 (INDOXXI) - HANYA RESOURCE SERVER 3
        elseif ($this->server === 'server-3') {
            $detail = IndoxxiTechService::getMovieDetail($fullSlug);
            if ($detail) {
                $this->media = $detail;
                // Hanya server-server bawaan IndoXXI
                $rawServers = $detail['servers'] ?? [];
                $ixServers = array_values(array_filter($rawServers, function ($s) {
                    return str_starts_with($s['id'] ?? '', 'server_ix') || str_starts_with($s['id'] ?? '', 'server_indoxxi');
                }));
                $this->servers = !empty($ixServers) ? $ixServers : $rawServers;

                // Tambahkan mirror failover dari Rebahin jika tersedia
                $failoverServers = [];
                try {
                    $cleanTitle = $detail['title'] ?? '';
                    $rbSearch = RebahinService::search($cleanTitle);
                    if (!empty($rbSearch[0]['slug'])) {
                        $rbDetail = RebahinService::getMovieDetail($rbSearch[0]['slug']);
                        if (!empty($rbDetail['servers'])) {
                            foreach ($rbDetail['servers'] as $idx => $s) {
                                $failoverServers[] = [
                                    'id' => 'server_ix_failover_' . ($idx + 1),
                                    'name' => 'Server 3 (Cadangan ' . ($idx + 1) . ')',
                                    'type' => 'embed',
                                    'embed_url' => $s['embed_url'] ?? '',
                                    'is_down' => false,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {}

                // Jika server bawaan IndoXXI (Ryderjet/Playerekusu) terindikasi bermasalah, prioritaskan server Cadangan yang aktif
                $this->servers = array_merge($failoverServers, $this->servers);
            }
        }
        // 4. SERVER 4 (PENCURIMOVIE) - HANYA RESOURCE SERVER 4 (BEBAS IKLAN / NATIVE MP4)
        elseif ($this->server === 'server-4') {
            $detail = PencuriMovieService::getMovieDetail($fullSlug);
            if ($detail) {
                $this->media = $detail;
                $this->servers = $detail['servers'] ?? [];
            }
        }

        if (!$this->media) {
            abort(404, "Konten tidak ditemukan pada {$this->serverLabel}");
        }

        // Simpan title halaman
        static::$title = ($this->media['title'] ?? 'Nonton Film') . " - {$this->serverLabel} - KZSTRM";
    }

    public function selectEpisode(int $epNum): void
    {
        $this->currentEpisode = $epNum;
        if ($this->server === 'server-1') {
            $stream = NartoMovieBoxService::getEpisodeStream($this->media['slug'] ?? '', 1, $epNum);
            if ($stream && !empty($stream['video_url'])) {
                $this->media['video_url'] = $stream['video_url'];
                $this->media['subtitles'] = $stream['subtitles'] ?? [];
            }
        }
    }
}
