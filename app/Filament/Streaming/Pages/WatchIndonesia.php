<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\IndonesiaMediaItem;
use App\Services\NartoMovieBoxService;
use App\Services\RebahinService;
use App\Services\NobarLk21Service;
use App\Services\IndoxxiTechService;
use App\Services\PencuriMovieService;
use App\Services\StreamingService;

class WatchIndonesia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - KZSTRM';
    protected static ?string $slug = 'indonesia/{provider}/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = 'streaming', ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $provider = $parameters['provider'] ?? 'rebahin';
        $slug = $parameters['slug'] ?? '';
        return "/indonesia/{$provider}/{$slug}";
    }
    public string $server = 'rebahin';
    public string $slugParam = '';
    public string $serverLabel = 'Rebahin';
    public ?array $media = null;
    public array $servers = [];
    public bool $isSeries = false;
    public array $episodes = [];
    public int $currentEpisode = 1;
    public string $currentEpisodeTitle = '';
    public bool $isFavorited = false;
    public float $savedProgressSeconds = 0;

    public function mount(string $provider, string $slug): void
    {
        $this->providerParam = strtolower($provider);
        $this->slugParam = $slug;

        $providerLabelMap = [
            'rebahin' => 'Rebahin',
            'lk21' => 'Layarkaca21',
            'moviebox' => 'MovieBox HD',
        ];

        $this->server = $this->providerParam;
        $this->serverLabel = $providerLabelMap[$this->providerParam] ?? ucfirst($this->providerParam);

        // Tentukan prefix slug berdasarkan provider
        $prefixMap = [
            'rebahin' => 'rb-',
            'lk21' => 'lk-',
            'moviebox' => 'mb-',
        ];
        $prefix = $prefixMap[$this->providerParam] ?? '';

        $fullSlug = $prefix && !str_starts_with($slug, $prefix) ? $prefix . $slug : $slug;
        $rawSlug = $prefix ? preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $fullSlug) : $fullSlug;

        // Cari di database vault lokal dulu untuk mengecek metadata & tipe
        $vaultItem = IndonesiaMediaItem::where('slug', $fullSlug)
            ->orWhere(function ($q) use ($rawSlug) {
                $q->where('raw_slug', $rawSlug);
            })
            ->first();

        $this->isSeries = $vaultItem && $vaultItem->type === 'series';

        // REBAHIN RESOURCE
        if ($this->providerParam === 'rebahin') {
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
                                'name' => 'Server (' . ($idx === 0 ? 'HD' : 'Mirror ' . $idx) . ')',
                                'type' => 'embed',
                                'embed_url' => $s['embed_url'] ?? '',
                            ];
                        }
                        if (empty($epServers) && !empty($s['embed_url'])) {
                            $epServers[] = [
                                'id' => 'server_rb_1',
                                'name' => 'Server (HD)',
                                'type' => 'embed',
                                'embed_url' => $s['embed_url'],
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
                            'name' => $s['name'] ?? 'Server (HD)',
                            'type' => 'embed',
                            'embed_url' => $s['embed_url'] ?? '',
                        ];
                    }
                    if (empty($this->servers) && !empty($detail['url'])) {
                        $this->servers[] = [
                            'id' => 'server_rb_default',
                            'name' => 'Server (HD)',
                            'type' => 'embed',
                            'embed_url' => $detail['url'],
                        ];
                    }
                }
            }
        }
        // LAYARKACA21 (LK21) RESOURCE
        elseif ($this->providerParam === 'lk21') {
            $detail = NobarLk21Service::getDetail($fullSlug, $this->currentEpisode);
            if ($detail) {
                $this->media = $detail;
                $this->servers = $detail['servers'] ?? [];
                $this->isSeries = !empty($detail['episodes']);
                if ($this->isSeries) {
                    $this->episodes = $detail['episodes'];
                    $this->currentEpisodeTitle = "Episode {$this->currentEpisode}";
                }
            }
        }
        // MOVIEBOX RESOURCE
        elseif ($this->providerParam === 'moviebox') {
            if ($this->isSeries || str_contains($fullSlug, 'series') || str_contains($fullSlug, 'season')) {
                $detail = NartoMovieBoxService::getSeriesDetail($fullSlug);
                if ($detail) {
                    $this->isSeries = true;
                    $this->media = $detail;
                    $rawEps = $detail['episodes'] ?? ($detail['defaultSeason']['episodes'] ?? []);
                    $this->episodes = [];
                    foreach ($rawEps as $re) {
                        $num = (int) ($re['episodeNumber'] ?? ($re['number'] ?? 1));
                        $name = $re['name'] ?? ($re['title'] ?? ('Episode ' . $num));
                        $this->episodes[] = [
                            'episode_number' => $num,
                            'episodeNumber' => $num,
                            'title' => $name,
                            'name' => $name,
                            'stream_url' => $re['stream_url'] ?? ($re['play_url'] ?? ''),
                            'subtitles' => $re['subtitles'] ?? [],
                            'stillPath' => $re['stillPath'] ?? ($detail['poster'] ?? ''),
                        ];
                    }
                    $firstEp = $this->episodes[0] ?? null;
                    if ($firstEp) {
                        $this->media['video_url'] = $firstEp['stream_url'] ?? '';
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

        if (!$this->media) {
            abort(404, "Konten tidak ditemukan pada {$this->serverLabel}");
        }

        // Simpan riwayat tontonan supaya EXP dan progress tercatat sejak pemutar dibuka.
        // EXP film tetap menunggu progress >= 80%; serial mendapat EXP per episode yang baru ditonton.
        if (auth()->check()) {
            try {
                $historyType = $this->isSeries ? 'indonesia_series' : 'indonesia_movie';
                $history = auth()->user()->watchHistories()->firstOrNew([
                    'item_id' => $this->slugParam,
                    'item_type' => $historyType,
                ]);
                $history->fill([
                    'title' => $this->media['title'] ?? 'Film Indonesia',
                    'thumbnail' => $this->media['poster'] ?? ($this->media['posterPath'] ?? null),
                    'last_episode' => (string) $this->currentEpisode,
                    'url' => request()->url(),
                ]);
                $history->save();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Indonesia watch history skipped', ['error' => $e->getMessage()]);
            }
        }

        // Simpan title halaman
        static::$title = ($this->media['title'] ?? 'Nonton Film') . " - {$this->serverLabel} - KZSTRM"; 
    }

    public function selectEpisode(int $epNum): void
    {
        $this->currentEpisode = $epNum;
        if (auth()->check() && $this->isSeries) {
            try {
                $history = auth()->user()->watchHistories()->firstOrNew([
                    'item_id' => $this->slugParam,
                    'item_type' => 'indonesia_series',
                ]);
                $history->fill([
                    'title' => $this->media['title'] ?? 'Series Indonesia',
                    'thumbnail' => $this->media['poster'] ?? ($this->media['posterPath'] ?? null),
                    'last_episode' => (string) $epNum,
                    'url' => request()->url(),
                ]);
                $history->save();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Indonesia episode history skipped', ['error' => $e->getMessage()]);
            }
        }
        if ($this->server === 'server-1') {
            $stream = NartoMovieBoxService::resolveEpisodeStream($this->media['slug'] ?? '', $epNum);
            if ($stream && !empty($stream['video_url'])) {
                $this->media['video_url'] = $stream['video_url'];
                $this->media['subtitles'] = $stream['subtitles'] ?? [];
            }
        }
    }
}
