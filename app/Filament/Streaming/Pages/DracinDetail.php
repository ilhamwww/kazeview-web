<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DracinDetail extends Page
{
    private const RAPTDRAMA_CODE = '5193CD21848193E43FC399BA4D73BB13';

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Dracin Detail';
    protected static ?string $title = 'Drama Detail';
    protected static ?string $slug = 'dracin/{network_slug}/drama/{id}';
    protected static string $view = 'filament.streaming.pages.dracin-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $networkSlug;
    public string $dramaId;
    public ?array $network = null;
    public ?array $drama = null;
    public ?array $activeEpisode = null;
    public ?array $streamData = null;
    public bool $isFavorited = false;
    public ?string $lastWatchedEpisode = null;
    public array $watchedEpisodes = [];

    public function mount(string $network_slug, string $id): void
    {
        $this->networkSlug = $network_slug;
        $this->dramaId = $id;

        // Fetch networks from cache or API
        $networks = Cache::remember('dramabuzz_networks', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                $apiKey = $setting->dramabuzz_api_key;
                $apiUrl = $setting->dramabuzz_api_url;

                if (!$apiUrl)
                    return [];

                $response = Http::get($apiUrl, [
                    'key' => $apiKey
                ]);

                if ($response->successful() && $response->json('success')) {
                    $platforms = $response->json('platforms') ?? [];
                    $activePlatforms = collect($platforms)->filter(function ($platform) {
                        return $platform['status'] === 'active';
                    })->values()->toArray();
                } else {
                    $activePlatforms = [];
                }
            } catch (\Exception $e) {
                $activePlatforms = [];
            }

            // Ensure PineDrama is included
            if (!collect($activePlatforms)->contains('id', 'pinedrama')) {
                $activePlatforms[] = [
                    'id' => 'pinedrama',
                    'name' => 'PineDrama',
                    'logo' => null,
                    'api' => 'https://pinedrama.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure Netshort is included
            if (!collect($activePlatforms)->contains('id', 'netshort')) {
                $activePlatforms[] = [
                    'id' => 'netshort',
                    'name' => 'Netshort',
                    'logo' => null,
                    'api' => 'https://netshort.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure Shortmax is included
            if (!collect($activePlatforms)->contains('id', 'shortmax')) {
                $activePlatforms[] = [
                    'id' => 'shortmax',
                    'name' => 'Shortmax',
                    'logo' => null,
                    'api' => 'https://shortmax.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure RaptDrama is included
            if (!collect($activePlatforms)->contains('id', 'raptdrama')) {
                $activePlatforms[] = [
                    'id' => 'raptdrama',
                    'name' => 'RaptDrama',
                    'logo' => null,
                    'api' => 'https://raptdrama.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure GoodShort is included
            if (!collect($activePlatforms)->contains('id', 'goodshort')) {
                $activePlatforms[] = [
                    'id' => 'goodshort',
                    'name' => 'GoodShort',
                    'logo' => null,
                    'api' => 'https://goodshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            return $activePlatforms;
        });

        // Find the network by slug
        $this->network = collect($networks)->first(function ($net) use ($network_slug) {
            return $net['id'] === $network_slug;
        });

        if (!$this->network || empty($this->network['api'])) {
            abort(404, 'Network API not found or inactive');
        }

        $setting = ScraperSetting::getInstance();
        $apiKey = $setting->dramabuzz_api_key;

        if ($network_slug === 'goodshort') {
            $lang = request()->query('lang', 'id');
            $apiLang = $lang === 'id' ? 'in' : $lang;
            // API Goodshort Chapters/Detail URL: https://goodshort.goodbos.online/chapters/{bookId}?lang=in&code=5193CD21848193E43FC399BA4D73BB13
            $detailUrl = "https://goodshort.goodbos.online/chapters/{$id}?lang={$apiLang}&code={$apiKey}";
        } elseif ($network_slug === 'raptdrama') {
            $detailUrl = 'https://raptdrama.goodbos.online/api/allepisodes'
                . '?id=' . urlencode($id)
                . '&lang=en'
                . '&code=' . urlencode(self::RAPTDRAMA_CODE);
        } elseif ($network_slug === 'pinedrama') {
            $code = "5193CD21848193E43FC399BA4D73BB13";
            $baseUrl = 'https://pinedrama.goodbos.online';
            $detailUrl = "{$baseUrl}/detail?code={$code}&id={$id}";
        } elseif ($network_slug === 'netshort') {
            $detailUrl = "https://netshort.goodbos.online/api/drama/{$id}?lang=in";
        } elseif ($network_slug === 'shortmax') {
            $detailUrl = "https://shortmax.goodbos.online/api/v1/detail/{$id}?lang=id&code=" . urlencode($apiKey);
        } elseif ($network_slug === 'melolo') {
            $baseUrl = 'https://melolo.goodbos.online/api';
            $detailUrl = "{$baseUrl}/detail/{$id}?lang=id&code={$apiKey}";
        } else {
            $baseUrl = rtrim($this->network['api'], '/');
            $detailUrl = "{$baseUrl}/detail/{$id}?lang=id&code={$apiKey}";
        }

        try {
            // Shortmax API kadang lambat merespons; timeout longgar untuknya.
            $detailTimeout = $network_slug === 'shortmax' ? 25 : 10;
            $response = Http::timeout($detailTimeout)->get($detailUrl);
            if ($response->successful()) {
                $this->drama = $response->json();

                if ($network_slug === 'goodshort') {
                    $records = $this->drama['data']['list'] ?? [];
                    // Ambil detail drama dari Cache yang diset di DracinNetwork
                    $catalogDrama = Cache::get('goodshort_drama_' . $this->dramaId, []);
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? 'Detail Drama',
                        'name' => $catalogDrama['name'] ?? 'Detail Drama',
                        'cover' => $catalogDrama['cover'] ?? null,
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => (int) ($this->drama['data']['total'] ?? count($records)),
                        'videos' => array_map(function ($ep, $index) {
                            return [
                                'episode' => $index + 1,
                                'video_id' => (string) ($ep['id'] ?? ''),
                                'title' => $ep['chapterName'] ?? "Episode " . ($index + 1),
                                'is_lock' => !empty($ep['locked']) || !empty($ep['charged']),
                                'video_url' => $ep['cdn'] ?? null,
                                'multi_videos' => $ep['multiVideos'] ?? null,
                            ];
                        }, $records, array_keys($records)),
                    ];
                } elseif ($network_slug === 'raptdrama') {
                    $data = $this->drama['data'] ?? [];
                    $catalogDrama = Cache::get(
                        'raptdrama_drama_' . $this->dramaId,
                        [],
                    );
                    $rawEpisodes = is_array($data['episodes'] ?? null)
                        ? $data['episodes']
                        : [];

                    $videos = array_values(array_filter(array_map(
                        function (array $episode): ?array {
                            $episodeNumber = (int) ($episode['idx'] ?? 0);
                            $videoUrl = $episode['video_url'] ?? null;

                            if (
                                $episodeNumber < 1
                                || empty($episode['has_video'])
                                || !is_string($videoUrl)
                                || $videoUrl === ''
                            ) {
                                return null;
                            }

                            return [
                                'episode' => $episodeNumber,
                                'video_id' => (string) ($episode['id'] ?? ''),
                                'title' => $episode['title'] ?? "Episode {$episodeNumber}",
                                'is_lock' => (string) ($episode['is_vip'] ?? '0') === '1',
                                'video_url' => $videoUrl,
                                'bunny_id' => $episode['bunny_id'] ?? null,
                            ];
                        },
                        $rawEpisodes,
                    )));

                    usort(
                        $videos,
                        fn(array $left, array $right): int =>
                        $left['episode'] <=> $right['episode'],
                    );

                    $this->drama = [
                        'title' => $data['name'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                        'name' => $data['name'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                        'cover' => $catalogDrama['cover'] ?? null,
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => (int) (
                            $data['totalEpisodes']
                            ?? $catalogDrama['episodes']
                            ?? count($videos)
                        ),
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'pinedrama') {
                    $rawEpisodes = $this->drama['episodes'] ?? [];
                    $this->drama['intro'] = $this->drama['description'] ?? '';
                    $this->drama['episodes'] = $this->drama['total_episodes'] ?? 0;
                    $this->drama['videos'] = array_map(function ($ep) {
                        $durationMs = $ep['duration_ms'] ?? null;

                        return [
                            'episode' => (int) ($ep['episode_num'] ?? 0),
                            'video_id' => (string) ($ep['video_id'] ?? ''),
                            'duration' => $durationMs !== null
                                ? (int) round(((int) $durationMs) / 1000)
                                : (isset($ep['duration']) ? (int) $ep['duration'] : null),
                        ];
                    }, $rawEpisodes);
                } elseif ($network_slug === 'netshort') {
                    $data = $this->drama['data'] ?? [];
                    $rawEpisodes = $data['shortPlayEpisodeList'] ?? [];
                    $labels = $data['shortPlayLabels'] ?? [];
                    $this->drama = [
                        'title' => $data['shortPlayName'] ?? 'Detail Drama',
                        'name' => $data['shortPlayName'] ?? '',
                        'cover' => $data['shortPlayCover'] ?? null,
                        'intro' => is_array($labels) ? implode(', ', $labels) : (string) $labels,
                        'episodes' => (int) ($data['totalEpisode'] ?? 0),
                        'videos' => array_map(function ($ep) {
                            return [
                                'episode' => (int) ($ep['episodeNo'] ?? 0),
                                'video_id' => (string) ($ep['episodeId'] ?? ''),
                                'is_lock' => !empty($ep['isLock']),
                            ];
                        }, $rawEpisodes),
                    ];
                } elseif ($network_slug === 'shortmax') {
                    $data = $this->drama['data'] ?? [];
                    $tags = $data['tags'] ?? [];
                    $tagNames = [];
                    if (is_array($tags)) {
                        foreach ($tags as $t) {
                            if (is_array($t) && !empty($t['labelName'])) {
                                $tagNames[] = $t['labelName'];
                            } elseif (is_string($t)) {
                                $tagNames[] = $t;
                            }
                        }
                    }

                    $this->drama = [
                        'title' => $data['name'] ?? 'Detail Drama',
                        'name' => $data['name'] ?? '',
                        'cover' => $data['cover'] ?? null,
                        'intro' => $data['summary'] ?? implode(', ', $tagNames),
                        'episodes' => (int) ($data['episodes'] ?? 0),
                        'videos' => [],
                    ];

                    // Ambil daftar episode + URL video (m3u8) yang sudah ditandatangani.
                    $allepsUrl = "https://shortmax.goodbos.online/api/v1/alleps/{$id}?lang=id&code=" . urlencode($apiKey);
                    try {
                        $epsResponse = Http::timeout(25)->get($allepsUrl);
                        if ($epsResponse->successful()) {
                            $epsData = $epsResponse->json('data') ?? [];
                            $rawEpisodes = $epsData['episodes'] ?? [];
                            $this->drama['videos'] = array_map(function ($ep) {
                                $video = $ep['video'] ?? [];
                                $videoUrls = [];
                                foreach (['480', '720', '1080'] as $q) {
                                    $key = 'video_' . $q;
                                    if (!empty($video[$key])) {
                                        // m3u8 di-proxy lewat /api/proxy-hls agar lolos CORS
                                        // (Shaka fetch manifest via XHR yang tunduk CORS).
                                        $videoUrls[$q] = url('/api/proxy-hls?url=' . urlencode($video[$key]));
                                    }
                                }
                                return [
                                    'episode' => (int) ($ep['episode'] ?? 0),
                                    'video_id' => (string) ($ep['id'] ?? ''),
                                    'is_lock' => !empty($ep['locked']),
                                    'duration' => isset($ep['duration']) ? (int) $ep['duration'] : null,
                                    'video_urls' => $videoUrls,
                                ];
                            }, $rawEpisodes);

                            // Bila totalEpisodes dari alleps lebih akurat, gunakan itu.
                            if (!empty($epsData['totalEpisodes'])) {
                                $this->drama['episodes'] = (int) $epsData['totalEpisodes'];
                            }
                        }
                    } catch (\Exception $e) {
                        // Biarkan videos kosong; blade akan tampil grid berdasarkan `episodes` count.
                    }
                }
            } else {
                abort(404, 'Drama not found');
            }
        } catch (\Exception $e) {
            abort(500, 'Failed to fetch drama details');
        }

        static::$title = $this->drama['title'] ?? 'Drama Detail';

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', $this->networkSlug . ':' . $this->dramaId)
                ->where('item_type', 'dracin')
                ->exists()
            : false;

        $history = auth()->user()
            ? auth()->user()->watchHistories()
                ->where('item_id', $this->networkSlug . ':' . $this->dramaId)
                ->where('item_type', 'dracin')
                ->first()
            : null;
        $this->lastWatchedEpisode = $history ? $history->last_episode : null;
        $this->watchedEpisodes = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
    }

    public function toggleFavorite()
    {
        if (!auth()->check()) {
            return redirect()->to(route('filament.streaming.auth.login'));
        }

        $user = auth()->user();
        $favId = $this->networkSlug . ':' . $this->dramaId;
        $favorite = $user->favorites()
            ->where('item_id', $favId)
            ->where('item_type', 'dracin')
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => $favId,
                'item_type' => 'dracin',
                'title' => $this->drama['title'] ?? 'Drama Detail',
                'thumbnail' => $this->drama['cover'] ?? null,
                'url' => request()->url(),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }

    public function autoPlayMelolo(): void
    {
        if (
            $this->networkSlug !== 'melolo'
            && $this->networkSlug !== 'pinedrama'
            && $this->networkSlug !== 'netshort'
            && $this->networkSlug !== 'shortmax'
            && $this->networkSlug !== 'raptdrama'
            && $this->networkSlug !== 'goodshort'
        ) {
            return;
        }

        $episodeToPlay = null;
        if ($this->lastWatchedEpisode) {
            $episodeToPlay = (int) $this->lastWatchedEpisode;
        } else {
            if (!empty($this->drama['videos'])) {
                $firstVideo = collect($this->drama['videos'])->sortBy('episode')->first();
                if ($firstVideo) {
                    $episodeToPlay = (int) $firstVideo['episode'];
                }
            }

            if (!$episodeToPlay && !empty($this->drama['episodes'])) {
                $episodeToPlay = 1;
            }
        }

        if ($episodeToPlay) {
            $this->playEpisode($episodeToPlay);
        }
    }

    public function playEpisode(int $ep): void
    {
        // Server-side subscription check
        if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
            \Filament\Notifications\Notification::make()
                ->title('Langganan Anda telah berakhir. Hubungi admin untuk memperpanjang.')
                ->danger()
                ->send();
            return;
        }

        $this->activeEpisode = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);

        if (!$this->activeEpisode) {
            $this->activeEpisode = ['episode' => $ep];
        }

        $setting = ScraperSetting::getInstance();
        $apiKey = $setting->dramabuzz_api_key;

        if ($this->networkSlug === 'goodshort') {
            $chapterId = $this->activeEpisode['video_id'] ?? null;
            if (!$chapterId) {
                \Filament\Notifications\Notification::make()
                    ->title('Chapter ID tidak ditemukan untuk episode ini')
                    ->danger()
                    ->send();
                return;
            }

            $lang = request()->query('lang', 'id');
            $apiLang = $lang === 'id' ? 'in' : $lang;
            // API rawurl request ke bookId ($this->dramaId)
            $rawUrlEndpoint = "https://goodshort.goodbos.online/rawurl/{$this->dramaId}?lang={$apiLang}&code={$apiKey}";
            
            try {
                $rawResponse = Http::timeout(20)->get($rawUrlEndpoint);
                if ($rawResponse->successful() && (int)$rawResponse->json('status') === 0) {
                    $videoData = $rawResponse->json('data') ?? [];
                    $videoKey = $videoData['videoKey'] ?? null;
                    $episodesList = $videoData['episodes'] ?? [];
                    
                    $matchedEpisode = collect($episodesList)->first(function ($item) use ($chapterId) {
                        return (string)($item['id'] ?? '') === (string)$chapterId;
                    });
                    
                    if (!$matchedEpisode) {
                        $matchedEpisode = $episodesList[$ep - 1] ?? null;
                    }
                    
                    if (!$matchedEpisode) {
                        \Filament\Notifications\Notification::make()
                            ->title('Episode tidak ditemukan di server Goodshort')
                            ->danger()
                            ->send();
                        return;
                    }

                    $qualityList = [];
                    $allVideos = $matchedEpisode['allVideos'] ?? [];
                    if (!empty($allVideos) && is_array($allVideos)) {
                        // Urutkan resolusi 1080p, 720p, 540p
                        $sortedVideos = collect($allVideos)->sortByDesc(function ($v) {
                            $type = strtolower($v['type'] ?? '');
                            if (str_contains($type, '1080')) return 1080;
                            if (str_contains($type, '720')) return 720;
                            if (str_contains($type, '540')) return 540;
                            return 0;
                        })->values()->toArray();

                        foreach ($sortedVideos as $v) {
                            $label = $v['type'] ?? 'Auto';
                            $rawUrl = $v['rawUrl'] ?? '';
                            if ($rawUrl) {
                                $videoUrlProxy = url('/api/proxy-hls?url=' . urlencode($rawUrl) . '&key=' . urlencode($videoKey));
                                $qualityList[] = [
                                    'label' => $label,
                                    'url' => $videoUrlProxy,
                                    'isDefault' => empty($qualityList),
                                ];
                            }
                        }
                    }

                    if (empty($qualityList) && !empty($matchedEpisode['m3u8'])) {
                        $videoUrlProxy = url('/api/proxy-hls?url=' . urlencode($matchedEpisode['m3u8']) . '&key=' . urlencode($videoKey));
                        $qualityList[] = [
                            'label' => 'Auto',
                            'url' => $videoUrlProxy,
                            'isDefault' => true,
                        ];
                    }

                    if (empty($qualityList)) {
                        \Filament\Notifications\Notification::make()
                            ->title('URL video tidak tersedia untuk episode ini')
                            ->danger()
                            ->send();
                        return;
                    }

                    $videoUrl = $qualityList[0]['url'];
                    $this->streamData = [
                        'videoUrl' => $videoUrl,
                        'qualityList' => $qualityList,
                        'subtitles' => [],
                        'duration' => null,
                    ];

                    $this->dispatch('init-player',
                        videoUrl: $videoUrl,
                        qualityList: $qualityList,
                        episode: $ep,
                        nextEpisode: $this->findNextEpisode($ep),
                        subtitles: []
                    );

                    $this->saveWatchHistory($ep);
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal mendapatkan rawurl dari server Goodshort')
                        ->danger()
                        ->send();
                }
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Error memuat video Goodshort: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }

            return;
        }

        if ($this->networkSlug === 'raptdrama') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            $playUrl = 'https://raptdrama.goodbos.online/api/playurl'
                . '?id=' . urlencode($this->dramaId)
                . '&ep=' . $ep
                . '&lang=en'
                . '&code=' . urlencode(self::RAPTDRAMA_CODE);

            try {
                $playResponse = Http::timeout(15)->get($playUrl);

                if (
                    $playResponse->successful()
                    && (int) $playResponse->json('code') === 200
                    && is_string($playResponse->json('data.url'))
                    && $playResponse->json('data.url') !== ''
                ) {
                    $videoUrl = $playResponse->json('data.url');
                }
            } catch (\Throwable $exception) {
                // Gunakan signed URL dari allepisodes jika refresh playurl gagal.
            }

            if (!is_string($videoUrl) || !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                \Filament\Notifications\Notification::make()
                    ->title('URL video tidak tersedia untuk episode ini')
                    ->danger()
                    ->send();

                return;
            }

            $videoUrl = url('/api/proxy-hls?url=' . urlencode($videoUrl));

            $qualityList = [
                [
                    'label' => 'Auto',
                    'url' => $videoUrl,
                    'isDefault' => true,
                ],
            ];

            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => null,
            ];

            $this->dispatch(
                'init-player',
                videoUrl: $videoUrl,
                qualityList: $qualityList,
                episode: $ep,
                nextEpisode: $this->findNextEpisode($ep),
                subtitles: [],
            );

            $this->saveWatchHistory($ep);

            return;
        }

        // Shortmax: URL video sudah ada di $this->activeEpisode['video_urls'] (dari /alleps),
        // tidak perlu HTTP call tambahan. Bangun qualityList langsung lalu dispatch.
        if ($this->networkSlug === 'shortmax') {
            $videoUrls = $this->activeEpisode['video_urls'] ?? [];

            $qualityList = [];
            // Urutkan preferensi: 720p default, lalu 1080p, lalu 480p.
            $order = ['720' => '720p', '1080' => '1080p', '480' => '480p'];
            foreach ($order as $key => $label) {
                if (!empty($videoUrls[$key])) {
                    $qualityList[] = [
                        'label' => $label,
                        'url' => $videoUrls[$key],
                        'isDefault' => empty($qualityList),
                    ];
                }
            }

            if (empty($qualityList)) {
                \Filament\Notifications\Notification::make()
                    ->title('URL video tidak tersedia untuk episode ini')
                    ->danger()
                    ->send();
                return;
            }

            $videoUrl = $qualityList[0]['url'];
            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => $this->activeEpisode['duration'] ?? null,
            ];

            $nextEpisode = null;
            if (!empty($this->drama['videos'])) {
                $foundActive = false;
                foreach ($this->drama['videos'] as $v) {
                    if ($foundActive) {
                        $nextEpisode = (int) $v['episode'];
                        break;
                    }
                    if ((int) $v['episode'] === $ep) {
                        $foundActive = true;
                    }
                }
            }

            $this->dispatch(
                'init-player',
                videoUrl: $videoUrl,
                qualityList: $qualityList,
                episode: $ep,
                nextEpisode: $nextEpisode,
                subtitles: []
            );

            if (auth()->check()) {
                $history = auth()->user()->watchHistories()
                    ->where('item_id', $this->networkSlug . ':' . $this->dramaId)
                    ->where('item_type', 'dracin')
                    ->first();

                $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
                $epStr = (string) $ep;
                if (!in_array($epStr, $watchedList, true)) {
                    $watchedList[] = $epStr;
                }

                auth()->user()->watchHistories()->updateOrCreate(
                    [
                        'item_id' => $this->networkSlug . ':' . $this->dramaId,
                        'item_type' => 'dracin',
                    ],
                    [
                        'title' => $this->drama['title'] ?? $this->drama['name'] ?? 'Detail Drama',
                        'thumbnail' => $this->drama['cover'] ?? null,
                        'last_episode' => $epStr,
                        'watched_episodes' => $watchedList,
                        'url' => request()->url(),
                    ]
                );
                $this->lastWatchedEpisode = $epStr;
                $this->watchedEpisodes = $watchedList;
            }

            return;
        }

        if ($this->networkSlug === 'pinedrama') {
            $code = "5193CD21848193E43FC399BA4D73BB13";
            $videoUrl = "https://pinedrama.goodbos.online/episode?code={$code}&id={$this->dramaId}&ep={$ep}";
        } elseif ($this->networkSlug === 'netshort') {
            $videoUrl = "https://netshort.goodbos.online/api/watch/{$this->dramaId}/{$ep}?lang=in&code={$apiKey}";
        } elseif ($this->networkSlug === 'melolo') {
            $baseUrl = 'https://melolo.goodbos.online/api';
            $videoUrl = "{$baseUrl}/video?id={$this->dramaId}&ep={$ep}&code={$apiKey}";
        } else {
            $baseUrl = rtrim($this->network['api'], '/');
            $videoUrl = "{$baseUrl}/video?id={$this->dramaId}&ep={$ep}&code={$apiKey}";
        }

        try {
            $response = Http::timeout(10)->get($videoUrl);
            if ($response->successful()) {
                $data = $response->json();

                if ($this->networkSlug === 'pinedrama') {
                    $videoUrl = $data['best_url'] ?? null;
                    if ($videoUrl) {
                        $videoUrl = url('/api/proxy-stream?url=' . urlencode($videoUrl));
                    }
                    $quality = $data['quality'] ?? 'HD';
                    $qualityList = [
                        [
                            'label' => $quality,
                            'url' => $videoUrl,
                            'isDefault' => true
                        ]
                    ];
                    $videoData = [
                        'videoUrl' => $videoUrl,
                        'qualityList' => $qualityList,
                        'duration' => isset($data['duration_ms']) ? ($data['duration_ms'] / 1000) : null
                    ];
                } elseif ($this->networkSlug === 'netshort') {
                    $payload = $data['data'] ?? $data;
                    $rawVideoUrl = $payload['videoUrl'] ?? null;
                    $videoUrl = $rawVideoUrl ? url('/api/proxy-stream?url=' . urlencode($rawVideoUrl)) : null;

                    $qualityList = [];
                    if ($videoUrl) {
                        $qualityList = [
                            [
                                'label' => 'HD',
                                'url' => $videoUrl,
                                'isDefault' => true,
                            ]
                        ];
                    }

                    $subtitles = [];
                    foreach (($payload['subtitles'] ?? []) as $sub) {
                        if (!empty($sub['url'])) {
                            $subtitles[] = [
                                'lang' => $sub['lang'] ?? 'Subtitle',
                                'url' => url('/api/proxy-subtitle?url=' . urlencode($sub['url'])),
                            ];
                        }
                    }

                    $videoData = [
                        'videoUrl' => $videoUrl,
                        'qualityList' => $qualityList,
                        'subtitles' => $subtitles,
                        'duration' => null,
                    ];
                } else {
                    // Melolo or other APIs might wrap the payload in a 'data' envelope
                    $videoData = $data['data'] ?? $data;

                    $qualityList = $videoData['qualityList'] ?? [];
                    $videoUrl = $videoData['videoUrl'] ?? null;

                    if (!$videoUrl && !empty($qualityList)) {
                        // Extract the first quality or default quality URL
                        $defaultQuality = collect($qualityList)->firstWhere('isDefault', true) ?? collect($qualityList)->first();
                        $videoUrl = $defaultQuality['url'] ?? null;
                    }

                    if ($videoUrl) {
                        $videoData['videoUrl'] = $videoUrl; // Ensure it is set
                        $videoData['qualityList'] = $qualityList; // Ensure it is set
                    }
                }

                if ($videoUrl) {
                    $this->streamData = $videoData;

                    $nextEpisode = null;
                    if (!empty($this->drama['videos'])) {
                        $foundActive = false;
                        foreach ($this->drama['videos'] as $v) {
                            if ($foundActive) {
                                $nextEpisode = (int) $v['episode'];
                                break;
                            }
                            if ((int) $v['episode'] === $ep) {
                                $foundActive = true;
                            }
                        }
                    } else if (!empty($this->drama['episodes'])) {
                        if ($ep < (int) $this->drama['episodes']) {
                            $nextEpisode = $ep + 1;
                        }
                    }

                    // Use named arguments for Livewire 3 so e.detail.videoUrl works in JS!
                    $this->dispatch(
                        'init-player',
                        videoUrl: $videoUrl,
                        qualityList: $qualityList,
                        episode: $ep,
                        nextEpisode: $nextEpisode,
                        subtitles: $videoData['subtitles'] ?? []
                    );

                    if (auth()->check()) {
                        $history = auth()->user()->watchHistories()
                            ->where('item_id', $this->networkSlug . ':' . $this->dramaId)
                            ->where('item_type', 'dracin')
                            ->first();

                        $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
                        $epStr = (string) $ep;
                        if (!in_array($epStr, $watchedList, true)) {
                            $watchedList[] = $epStr;
                        }

                        auth()->user()->watchHistories()->updateOrCreate(
                            [
                                'item_id' => $this->networkSlug . ':' . $this->dramaId,
                                'item_type' => 'dracin',
                            ],
                            [
                                'title' => $this->drama['title'] ?? $this->drama['name'] ?? 'Detail Drama',
                                'thumbnail' => $this->drama['cover'] ?? null,
                                'last_episode' => $epStr,
                                'watched_episodes' => $watchedList,
                                'url' => request()->url(),
                            ]
                        );
                        $this->lastWatchedEpisode = $epStr;
                        $this->watchedEpisodes = $watchedList;
                    }
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('URL video tidak ditemukan dalam respon API')
                        ->danger()
                        ->send();
                }
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Gagal mendapatkan respon video dari server')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            // handle error if needed
            \Filament\Notifications\Notification::make()
                ->title('Gagal memuat video')
                ->danger()
                ->send();
        }
    }

    protected function findNextEpisode(int $episode): ?int
    {
        $videos = $this->drama['videos'] ?? [];

        foreach ($videos as $index => $video) {
            if ((int) ($video['episode'] ?? 0) !== $episode) {
                continue;
            }

            return isset($videos[$index + 1])
                ? (int) ($videos[$index + 1]['episode'] ?? 0) ?: null
                : null;
        }

        return null;
    }

    protected function saveWatchHistory(int $episode): void
    {
        if (!auth()->check()) {
            return;
        }

        $episodeId = (string) $episode;
        $watchedEpisodes = $this->watchedEpisodes;

        if (!in_array($episodeId, $watchedEpisodes, true)) {
            $watchedEpisodes[] = $episodeId;
        }

        auth()->user()->watchHistories()->updateOrCreate(
            [
                'item_id' => $this->networkSlug . ':' . $this->dramaId,
                'item_type' => 'dracin',
            ],
            [
                'title' => $this->drama['title'] ?? $this->drama['name'] ?? 'Detail Drama',
                'thumbnail' => $this->drama['cover'] ?? null,
                'last_episode' => $episodeId,
                'watched_episodes' => $watchedEpisodes,
                'url' => request()->url(),
            ],
        );

        $this->lastWatchedEpisode = $episodeId;
        $this->watchedEpisodes = $watchedEpisodes;
    }

    protected function getViewData(): array
    {
        return [
            'network' => $this->network,
            'drama' => $this->drama,
        ];
    }

    /**
     * Network short-drama yang sudah memakai Detail Dracin V2.
     * BasePage::render() tetap menangani layout Filament melalui getLayout().
     */
    public function getView(): string
    {
        $v2Networks = ['shortmax', 'netshort', 'pinedrama', 'melolo', 'raptdrama', 'goodshort'];

        return in_array($this->networkSlug ?? null, $v2Networks, true)
            ? 'filament.streaming.pages.dracin-detail-v2'
            : static::$view;
    }
}