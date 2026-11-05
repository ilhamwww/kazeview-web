<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\IndonesiaMediaItem;
use App\Services\NartoMovieBoxService;
use App\Services\RebahinService;
use App\Services\IndoxxiTechService;
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
            $detail = RebahinService::getMovieDetail($fullSlug);
            if ($detail) {
                $this->media = $detail;
                // Hanya server-server bawaan Rebahin
                $this->servers = $detail['servers'] ?? [
                    [
                        'id' => 'server1',
                        'name' => 'Server 2 (HD)',
                        'type' => 'hls',
                        'embed_url' => '',
                    ]
                ];
            }
        }
        // 3. SERVER 3 (INDOXXI) - HANYA RESOURCE SERVER 3
        elseif ($this->server === 'server-3') {
            $detail = IndoxxiTechService::getMovieDetail($fullSlug);
            if ($detail) {
                $this->media = $detail;
                // Hanya server-server bawaan IndoXXI (TIDAK ADA REBAHIN / MOVIEBOX)
                $rawServers = $detail['servers'] ?? [];
                $ixServers = array_values(array_filter($rawServers, function ($s) {
                    return str_starts_with($s['id'] ?? '', 'server_ix') || str_starts_with($s['id'] ?? '', 'server_indoxxi');
                }));
                $this->servers = !empty($ixServers) ? $ixServers : $rawServers;
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
