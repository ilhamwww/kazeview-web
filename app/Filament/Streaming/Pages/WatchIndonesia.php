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
    protected static ?string $slug = 'indonesia/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-indonesia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

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

    public function mount(string $slug, ?string $server = null): void
    {
        // Kompatibilitas bila route diakses /indonesia/{server}/{slug}
        if ($server !== null && in_array(strtolower($slug), ['server-1', 'server-2', 'server-3', 'server-4'])) {
            $slug = $server;
        }

        $this->server = 'rebahin';
        $this->serverLabel = 'Rebahin';
        $this->slugParam = $slug;

        // Normalisasi slug ke Rebahin (rb-)
        $fullSlug = str_starts_with($slug, 'rb-') ? $slug : 'rb-' . $slug;
        $rawSlug = preg_replace('/^rb-/', '', $fullSlug);

        // Cari di database vault lokal dulu untuk mengecek metadata & tipe
        $vaultItem = IndonesiaMediaItem::where('slug', $fullSlug)
            ->orWhere(function ($q) use ($rawSlug) {
                $q->where('source', 'server2')
                  ->where('raw_slug', $rawSlug);
            })
            ->first();

        $this->isSeries = $vaultItem && $vaultItem->type === 'series';

        // REBAHIN RESOURCE
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
                    if (empty($epServers)) {
                        $epServers[] = [
                            'id' => 'server_rb_1',
                            'name' => 'Server (HD)',
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
                        'name' => $s['name'] ?? 'Server (HD)',
                        'type' => 'embed',
                        'embed_url' => $s['embed_url'] ?? '',
                    ];
                }
                if (empty($this->servers)) {
                    $this->servers[] = [
                        'id' => 'server_rb_default',
                        'name' => 'Server (HD)',
                        'type' => 'embed',
                        'embed_url' => $detail['url'] ?? '',
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
