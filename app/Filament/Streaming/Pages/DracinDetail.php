<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use function Livewire\store;

class DracinDetail extends Page
{
    private const RAPTDRAMA_CODE = '5193CD21848193E43FC399BA4D73BB13';

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Dracin Detail';
    protected static ?string $title = 'Drama Detail';
    protected static ?string $slug = 'dracin/{network_slug}/drama/{id}';
    protected static string $view = 'filament.streaming.pages.dracin-detail-v2';
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

    /** True selama prefetchEpisode() berjalan: blokir efek samping (riwayat tonton). */
    protected bool $isPrefetching = false;

    public function mount(string $network_slug, string $id): void
    {
        $this->networkSlug = $network_slug;
        $this->dramaId = $id;

        // Fetch networks from cache or API
        $networks = Cache::remember('dramabuzz_networks', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                $apiUrl = $setting->dramabuzz_api_url;

                if (!$apiUrl)
                    return [];

                $response = \App\Services\DramabuzzService::executeWithFailover(function ($key) use ($apiUrl) {
                    return Http::get($apiUrl, [
                        'key' => $key
                    ]);
                });

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

            // Ensure Melolo is included
            if (!collect($activePlatforms)->contains('id', 'melolo')) {
                $activePlatforms[] = [
                    'id' => 'melolo',
                    'name' => 'Melolo',
                    'logo' => 'https://api.dramabuzz.sbs/logos/melolo.svg',
                    'api' => 'https://melolo.goodbos.online',
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

            // Ensure DramaBox is included
            if (!collect($activePlatforms)->contains('id', 'dramabox')) {
                $activePlatforms[] = [
                    'id' => 'dramabox',
                    'name' => 'DramaBox',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dramabox.svg',
                    'api' => 'https://dramabox.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ReelBuzz is included
            if (!collect($activePlatforms)->contains('id', 'reelbuzz')) {
                $activePlatforms[] = [
                    'id' => 'reelbuzz',
                    'name' => 'ReelBuzz',
                    'logo' => 'https://api.dramabuzz.sbs/logos/reelbuzz.png',
                    'api' => 'https://reelbuzz.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ReelShort is included
            if (!collect($activePlatforms)->contains('id', 'reelshort')) {
                $activePlatforms[] = [
                    'id' => 'reelshort',
                    'name' => 'ReelShort',
                    'logo' => 'https://api.dramabuzz.sbs/logos/reelshort.svg',
                    'api' => 'https://reelshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure VibeShort is included
            if (!collect($activePlatforms)->contains('id', 'vibeshort')) {
                $activePlatforms[] = [
                    'id' => 'vibeshort',
                    'name' => 'VibeShort',
                    'logo' => null,
                    'api' => 'https://vibeshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StoryReel is included
            if (!collect($activePlatforms)->contains('id', 'storyreel')) {
                $activePlatforms[] = [
                    'id' => 'storyreel',
                    'name' => 'StoryReel',
                    'logo' => 'https://api.dramabuzz.sbs/logos/storyreel.png',
                    'api' => 'https://storyreel.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure MoboReels is included
            if (!collect($activePlatforms)->contains('id', 'moboreels')) {
                $activePlatforms[] = [
                    'id' => 'moboreels',
                    'name' => 'MoboReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/moboreels.svg',
                    'api' => 'https://moboreels.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StardustTV is included
            if (!collect($activePlatforms)->contains('id', 'stardusttv')) {
                $activePlatforms[] = [
                    'id' => 'stardusttv',
                    'name' => 'StardustTV',
                    'logo' => 'https://api.dramabuzz.sbs/logos/stardusttv.png',
                    'api' => 'https://stardusttv.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Normalize serialplus from API to serealplus
            $activePlatforms = collect($activePlatforms)->map(function ($p) {
                if ($p['id'] === 'serialplus') {
                    $p['id'] = 'serealplus';
                    $p['name'] = 'Sereal+';
                    $p['logo'] = 'https://api.dramabuzz.sbs/logos/serialplus.png';
                    $p['api'] = 'https://serealplus.goodbos.online';
                }
                return $p;
            })->unique('id')->values()->toArray();

            // Ensure Sereal+ is included
            if (!collect($activePlatforms)->contains('id', 'serealplus')) {
                $activePlatforms[] = [
                    'id' => 'serealplus',
                    'name' => 'Sereal+',
                    'logo' => 'https://api.dramabuzz.sbs/logos/serialplus.png',
                    'api' => 'https://serealplus.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure FlexTV is included
            if (!collect($activePlatforms)->contains('id', 'flextv')) {
                $activePlatforms[] = [
                    'id' => 'flextv',
                    'name' => 'FlexTV',
                    'logo' => 'https://api.dramabuzz.sbs/logos/flextv.svg',
                    'api' => 'https://flextv.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ShortsWave is included
            if (!collect($activePlatforms)->contains('id', 'shortswave')) {
                $activePlatforms[] = [
                    'id' => 'shortswave',
                    'name' => 'ShortsWave',
                    'logo' => 'https://api.dramabuzz.sbs/logos/shortwave.png',
                    'api' => 'https://shortwave.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure FreeReels is included
            if (!collect($activePlatforms)->contains('id', 'freereels')) {
                $activePlatforms[] = [
                    'id' => 'freereels',
                    'name' => 'FreeReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/freereels.svg',
                    'api' => 'https://drakula.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure FlickReels is included
            if (!collect($activePlatforms)->contains('id', 'flickreels')) {
                $activePlatforms[] = [
                    'id' => 'flickreels',
                    'name' => 'FlickReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/flickreels.svg',
                    'api' => 'https://flickreels.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StarShort is included
            if (!collect($activePlatforms)->contains('id', 'starshort')) {
                $activePlatforms[] = [
                    'id' => 'starshort',
                    'name' => 'StarShort',
                    'logo' => 'https://api.dramabuzz.sbs/logos/starshort.png',
                    'api' => 'https://drakula.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure DotDrama is included
            if (!collect($activePlatforms)->contains('id', 'dotdrama')) {
                $activePlatforms[] = [
                    'id' => 'dotdrama',
                    'name' => 'DotDrama',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dotdrama.svg',
                    'api' => 'https://dotdrama.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure DramaWave is included
            if (!collect($activePlatforms)->contains('id', 'dramawave')) {
                $activePlatforms[] = [
                    'id' => 'dramawave',
                    'name' => 'DramaWave',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dramawave.svg',
                    'api' => 'https://dramawave.goodbos.online',
                    'status' => 'active',
                ];
            }

            return $activePlatforms;
        });

        // Find the network by slug
        $this->network = collect($networks)->first(function ($net) use ($network_slug) {
            return $net['id'] === $network_slug || ($network_slug === 'serealplus' && $net['id'] === 'serialplus') || ($network_slug === 'serialplus' && $net['id'] === 'serealplus');
        });

        if (!$this->network) {
            $knownApis = [
                'melolo' => 'https://melolo.goodbos.online',
                'pinedrama' => 'https://pinedrama.goodbos.online',
                'shortmax' => 'https://shortmax.goodbos.online',
                'goodshort' => 'https://goodshort.goodbos.online',
                'dramabox' => 'https://dramabox.goodbos.online',
                'reelbuzz' => 'https://reelbuzz.goodbos.online',
                'reelshort' => 'https://reelshort.goodbos.online',
                'vibeshort' => 'https://vibeshort.goodbos.online',
                'storyreel' => 'https://storyreel.goodbos.online',
                'moboreels' => 'https://moboreels.goodbos.online',
                'stardusttv' => 'https://stardusttv.goodbos.online',
                'serealplus' => 'https://serealplus.goodbos.online',
                'serialplus' => 'https://serealplus.goodbos.online',
                'flextv' => 'https://flextv.goodbos.online',
                'shortswave' => 'https://shortwave.goodbos.online',
                'freereels' => 'https://drakula.goodbos.online',
                'flickreels' => 'https://flickreels.goodbos.online',
                'starshort' => 'https://drakula.goodbos.online',
                'dotdrama' => 'https://dotdrama.goodbos.online',
                'dramawave' => 'https://dramawave.goodbos.online',
            ];
            if (isset($knownApis[$network_slug])) {
                $this->network = [
                    'id' => $network_slug,
                    'name' => ucfirst($network_slug),
                    'api' => $knownApis[$network_slug],
                    'status' => 'active',
                ];
            }
        }

        if (!$this->network || empty($this->network['api'])) {
            abort(404, 'Network API not found or inactive');
        }

        $setting = ScraperSetting::getInstance();
        $apiKey = $setting->dramabuzz_api_key;

        if ($network_slug === 'serealplus' || $network_slug === 'serialplus') {
            try {
                $lang = request()->query('lang', 'id');
                $detailRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 15])
                    ->get("https://serealplus.goodbos.online/api/detail?id={$id}&lang={$lang}&code={$apiKey}");
                $detailJson = $detailRes->successful() ? ($detailRes->json() ?: []) : [];
                $rawDetail = $detailJson['data'] ?? [];

                $episodesRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 15])
                    ->get("https://serealplus.goodbos.online/api/episodes?id={$id}&lang={$lang}&code={$apiKey}");
                $episodesJson = $episodesRes->successful() ? ($episodesRes->json() ?: []) : [];
                $rawEpisodes = $episodesJson['episodes'] ?? [];

                $catalogDrama = Cache::get('serealplus_drama_' . $this->dramaId, []);

                $videos = array_map(function ($ep, $index) {
                    $epNum = (int) ($ep['seqNo'] ?? ($index + 1));
                    return [
                        'episode' => $epNum,
                        'video_id' => (string) ($ep['id'] ?? $epNum),
                        'title' => "Episode {$epNum}",
                        'is_lock' => !empty($ep['lock']),
                        'duration' => $ep['duration'] ?? null,
                        'video_url' => $ep['videoUrl'] ?? null,
                    ];
                }, $rawEpisodes, array_keys($rawEpisodes));

                $intro = !empty($rawDetail['introduce'])
                    ? $rawDetail['introduce']
                    : ($rawDetail['shortIntroduce'] ?? ($catalogDrama['intro'] ?? ''));

                $this->drama = [
                    'title' => $rawDetail['contentName'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'name' => $rawDetail['contentName'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'cover' => $rawDetail['contentCoverUrl'] ?? $catalogDrama['cover'] ?? null,
                    'intro' => $intro,
                    'episodes' => (int) ($rawDetail['chapterNum'] ?? count($videos)),
                    'videos' => $videos,
                ];

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

                return;
            } catch (\Exception $e) {
                \Log::error('Sereal+ detail error: ' . $e->getMessage());
                abort(500, 'Gagal memuat detail drama dari Sereal+');
            }
        } elseif ($network_slug === 'dramabox') {
            try {
                $detailRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 15])
                    ->withHeaders(['x-token' => $apiKey])
                    ->get("https://dramabox.goodbos.online/api/dramabox/detail?bookId={$id}&lang=in");
                $rawDetail = $detailRes->successful() ? ($detailRes->json() ?: []) : [];

                $rawEpisodes = [];
                try {
                    $resEp = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 15])
                        ->withHeaders(['x-token' => $apiKey])
                        ->get("https://dramabox.goodbos.online/api/dramabox/allepisode?bookId={$id}");
                    if ($resEp->successful()) {
                        $rawEpisodes = $resEp->json() ?: [];
                    }
                } catch (\Exception $e) {}

                $catalogDrama = Cache::get('dramabox_drama_' . $this->dramaId, []);

                $videos = array_map(function ($ep, $index) {
                    $epNum = (int) ($ep['chapterIndex'] ?? $index) + 1;
                    return [
                        'episode' => $epNum,
                        'video_id' => (string) ($ep['chapterId'] ?? $epNum),
                        'title' => $ep['chapterName'] ?? "Episode {$epNum}",
                        'is_lock' => !empty($ep['isLocked']),
                        'video_url' => $ep['videoUrl1080'] ?? $ep['videoUrl720'] ?? $ep['videoUrl'] ?? null,
                        'videoUrl1080' => $ep['videoUrl1080'] ?? null,
                        'videoUrl720' => $ep['videoUrl720'] ?? null,
                        'videoUrl540' => $ep['videoUrl540'] ?? null,
                    ];
                }, $rawEpisodes, array_keys($rawEpisodes));

                $this->drama = [
                    'title' => $rawDetail['bookName'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'name' => $rawDetail['bookName'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'cover' => $rawDetail['coverWap'] ?? $rawDetail['cover'] ?? $catalogDrama['cover'] ?? null,
                    'intro' => $rawDetail['introduction'] ?? $catalogDrama['intro'] ?? '',
                    'episodes' => (int) ($rawDetail['chapterCount'] ?? count($videos)),
                    'videos' => $videos,
                ];

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

                return;
            } catch (\Exception $e) {
                \Log::error('DramaBox detail error: ' . $e->getMessage());
                abort(500, 'Gagal memuat detail drama dari DramaBox');
            }
        } elseif ($network_slug === 'reelbuzz') {
            try {
                $detailRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 15])
                    ->get("https://reelbuzz.goodbos.online/api/drama/detail?id={$id}&code={$apiKey}");
                $json = $detailRes->successful() ? ($detailRes->json() ?: []) : [];
                $rawDetail = $json['rows'] ?? $json['data'] ?? $json;
                $rawEpisodes = $rawDetail['episodeList'] ?? [];

                $catalogDrama = Cache::get('reelbuzz_drama_' . $this->dramaId, []);

                $videos = array_map(function ($ep, $index) {
                    $epNum = (int) ($ep['episodeNo'] ?? ($index + 1));
                    $playUrl = $ep['fileUrl'] ?? $ep['playURL'] ?? '';
                    $directUrl = $playUrl;
                    if (str_contains($playUrl, '?url=')) {
                        parse_str(parse_url($playUrl, PHP_URL_QUERY), $queryParts);
                        if (!empty($queryParts['url'])) {
                            $directUrl = $queryParts['url'];
                        }
                    }

                    return [
                        'episode' => $epNum,
                        'video_id' => (string) ($ep['id'] ?? $epNum),
                        'title' => "Episode {$epNum}",
                        'is_lock' => !empty($ep['locked']),
                        'video_url' => $directUrl,
                    ];
                }, $rawEpisodes, array_keys($rawEpisodes));

                $this->drama = [
                    'title' => $rawDetail['name'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'name' => $rawDetail['name'] ?? $catalogDrama['name'] ?? 'Detail Drama',
                    'cover' => $rawDetail['coverUrl'] ?? $catalogDrama['cover'] ?? null,
                    'intro' => $rawDetail['description'] ?? $catalogDrama['intro'] ?? '',
                    'episodes' => (int) ($rawDetail['episodes'] ?? count($videos)),
                    'videos' => $videos,
                ];

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

                return;
            } catch (\Exception $e) {
                \Log::error('ReelBuzz detail error: ' . $e->getMessage());
                abort(500, 'Gagal memuat detail drama dari ReelBuzz');
            }
        } elseif ($network_slug === 'goodshort') {
            $lang = request()->query('lang', 'id');
            $apiLang = $lang === 'id' ? 'in' : $lang;
            // API Goodshort Chapters/Detail URL: https://goodshort.goodbos.online/chapters/{bookId}?lang=in&code=5193CD21848193E43FC399BA4D73BB13
            $detailUrl = "https://goodshort.goodbos.online/chapters/{$id}?lang={$apiLang}&code={$apiKey}";
        } elseif ($network_slug === 'raptdrama') {
            $detailUrl = "https://raptdrama.goodbos.online/api/raptdrama/episodes?id={$id}";
        } elseif ($network_slug === 'pinedrama') {
            $code = $apiKey;
            $baseUrl = 'https://pinedrama.goodbos.online';
            $detailUrl = "{$baseUrl}/detail?code={$code}&id={$id}";
        } elseif ($network_slug === 'netshort') {
            $detailUrl = "https://netshort.goodbos.online/api/drama/{$id}?lang=in";
        } elseif ($network_slug === 'shortmax') {
            $detailUrl = "https://shortmax.goodbos.online/api/v1/detail/{$id}?lang=id&code=" . urlencode($apiKey);
        } elseif ($network_slug === 'melolo') {
            $baseUrl = 'https://melolo.goodbos.online/api';
            $detailUrl = "{$baseUrl}/detail/{$id}?lang=id&code={$apiKey}";
        } elseif ($network_slug === 'reelshort') {
            $lang = request()->query('lang', 'in');
            $detailUrl = "https://reelshort.goodbos.online/detail/{$id}?lang={$lang}";
        } elseif ($network_slug === 'vibeshort') {
            $detailUrl = "https://vibeshort.goodbos.online/api/v1/detail/{$id}?lang=id";
        } elseif ($network_slug === 'storyreel') {
            $detailUrl = "https://storyreel.goodbos.online/api/v1/detail/{$id}?lang=id";
        } elseif ($network_slug === 'moboreels') {
            $detailUrl = "https://moboreels.goodbos.online/api/v1/series/{$id}?lang=in&code={$apiKey}";
        } elseif ($network_slug === 'stardusttv') {
            $detailUrl = "https://stardusttv.goodbos.online/v1/detail/drama/{$id}?code={$apiKey}";
        } elseif ($network_slug === 'flextv') {
            $detailUrl = "https://flextv.goodbos.online/api/allepisodes?id={$id}&code={$apiKey}";
        } elseif ($network_slug === 'shortswave') {
            $detailUrl = "https://shortwave.goodbos.online/api/drama/{$id}/episodes?lang=in&code={$apiKey}";
        } elseif ($network_slug === 'freereels') {
            $detailUrl = "https://drakula.goodbos.online/api/freereels/drama/{$id}?lang=in&code={$apiKey}";
        } elseif ($network_slug === 'flickreels') {
            $detailUrl = "https://flickreels.goodbos.online/batchload/{$id}?lang=in&code={$apiKey}";
        } elseif ($network_slug === 'starshort') {
            $detailUrl = "https://drakula.goodbos.online/api/starshort/show/{$id}?locale=id&code={$apiKey}";
        } elseif ($network_slug === 'dotdrama') {
            $detailUrl = "https://dotdrama.goodbos.online/api/drama/{$id}";
        } elseif ($network_slug === 'dramawave') {
            $detailUrl = "https://dramawave.goodbos.online/api/drama/{$id}?lang=in&code={$apiKey}";
        } else {
            $baseUrl = rtrim($this->network['api'], '/');
            $detailUrl = "{$baseUrl}/detail/{$id}?lang=id&code={$apiKey}";
        }

        try {
            // Shortmax API kadang lambat merespons; timeout longgar untuknya.
            $detailTimeout = $network_slug === 'shortmax' ? 25 : 12;
            $req = Http::timeout($detailTimeout);
            if ($network_slug === 'vibeshort' || $network_slug === 'storyreel' || $network_slug === 'raptdrama') {
                $req = $req->withHeaders(['x-token' => $apiKey]);
            }
            $response = $req->get($detailUrl);
            if ($response->successful()) {
                $this->drama = $response->json();

                if ($network_slug === 'melolo') {
                    if (!empty($this->drama['cover']) && preg_match('/novel-images-[a-z]+(?:%2F|\/)([0-9a-f]{32})/i', $this->drama['cover'], $matches)) {
                        $this->drama['cover'] = "https://p16-novel-sg.ibyteimg.com/img/novel-images-sg/{$matches[1]}~tplv-resize:570:810.jpg";
                    }
                }

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
                    $catalogDrama = Cache::get('raptdrama_drama_' . $this->dramaId, []);
                    $rawEpisodes = is_array($data['episodes'] ?? null) ? $data['episodes'] : [];

                    $videos = array_map(function (array $ep): array {
                        $episodeNumber = (int) ($ep['idx'] ?? 1);
                        $videoUrl = $ep['src'] ?? null;
                        return [
                            'episode' => $episodeNumber,
                            'video_id' => (string) ($ep['cid'] ?? ''),
                            'title' => $ep['msg'] ?? "Episode {$episodeNumber}",
                            'is_lock' => !empty($ep['fufei']),
                            'video_url' => $videoUrl,
                        ];
                    }, $rawEpisodes);

                    usort($videos, fn(array $a, array $b): int => $a['episode'] <=> $b['episode']);

                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? 'RaptDrama',
                        'name' => $catalogDrama['name'] ?? 'RaptDrama',
                        'cover' => $catalogDrama['cover'] ?? null,
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => (int) ($data['total'] ?? count($videos)),
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
                } elseif ($network_slug === 'reelshort') {
                    $lang = request()->query('lang', 'in');
                    $allepsUrl = "https://reelshort.goodbos.online/allepisodes/{$id}?code={$apiKey}&lang={$lang}";
                    $eps = [];
                    try {
                        $allepsRes = Http::timeout(12)->get($allepsUrl);
                        if ($allepsRes->successful()) {
                            $eps = $allepsRes->json('episodes') ?: $allepsRes->json('chapters') ?: [];
                        }
                    } catch (\Exception $e) {}

                    $this->drama = [
                        'title' => $this->drama['title'] ?? 'ReelShort Drama',
                        'name' => $this->drama['title'] ?? 'ReelShort Drama',
                        'cover' => $this->drama['pic'] ?? $this->drama['cover'] ?? '',
                        'intro' => $this->drama['desc'] ?? '',
                        'episodes' => count($eps) ?: (int)($this->drama['chapters'] ?? 0),
                        'videos' => array_map(function ($ep) {
                            $streams = $ep['streams'] ?? [];
                            // Utamakan stream H.264 universal (bukan HEVC/H.265 yang stall di browser tanpa hardware decode)
                            $bestStream = collect($streams)->first(function ($s) {
                                $u = $s['url'] ?? '';
                                $q = $s['quality'] ?? '';
                                if ($q === '0p' || str_contains($u, '-video-') || str_contains($u, '/video/')) {
                                    return false;
                                }
                                return str_contains($u, 'vod-') || str_contains($u, '-ld.m3u8') || $q === '540p' || $q === '720p';
                            })
                            ?? collect($streams)->first(fn($s) => ($s['quality'] ?? '') === '720p')
                            ?? ($streams[0] ?? null);
                            $url = $bestStream['url'] ?? '';
                            return [
                                'episode' => (int) ($ep['episode'] ?? 1),
                                'video_id' => (string) ($ep['id'] ?? ''),
                                'title' => $ep['name'] ?? ('Episode ' . ($ep['episode'] ?? 1)),
                                'is_lock' => false,
                                'duration' => $ep['duration'] ?? null,
                                'streams' => $streams,
                                'video_url' => $url,
                            ];
                        }, $eps),
                    ];
                } elseif ($network_slug === 'vibeshort' || $network_slug === 'storyreel') {
                    $host = $network_slug === 'vibeshort' ? 'vibeshort.goodbos.online' : 'storyreel.goodbos.online';
                    $allepsUrl = "https://{$host}/api/v1/alleps/{$id}?lang=id";
                    $rawEps = [];
                    try {
                        $allepsRes = Http::timeout(12)->withHeaders(['x-token' => $apiKey])->get($allepsUrl);
                        if ($allepsRes->successful()) {
                            $rawEps = $allepsRes->json('data.episodes') ?: $allepsRes->json('data.chapterList') ?: [];
                        }
                    } catch (\Exception $e) {}

                    $this->drama = [
                        'title' => $this->drama['name'] ?? 'Detail Drama',
                        'name' => $this->drama['name'] ?? 'Detail Drama',
                        'cover' => $this->drama['cover'] ?? '',
                        'intro' => $this->drama['summary'] ?? '',
                        'episodes' => count($rawEps) ?: (int)($this->drama['episodes'] ?? 0),
                        'videos' => array_map(function ($ch) {
                            $videoMap = $ch['video'] ?? [];
                            $videoUrls = [];
                            foreach (['480', '720', '1080'] as $q) {
                                $k = 'video_' . $q;
                                if (!empty($videoMap[$k])) {
                                    $videoUrls[$q] = url('/api/proxy-hls?url=' . urlencode($videoMap[$k]));
                                }
                            }
                            $singleUrl = $videoUrls['720'] ?? ($videoUrls['1080'] ?? ($videoUrls['480'] ?? ($ch['videoUrl'] ?? '')));
                            return [
                                'episode' => (int) ($ch['episode'] ?? $ch['chapterNo'] ?? 1),
                                'video_id' => (string) ($ch['id'] ?? $ch['chapterId'] ?? ''),
                                'title' => 'Episode ' . ($ch['episode'] ?? $ch['chapterNo'] ?? 1),
                                'is_lock' => false,
                                'duration' => $ch['duration'] ?? null,
                                'video_url' => $singleUrl,
                                'video_urls' => $videoUrls,
                            ];
                        }, $rawEps),
                    ];
                } elseif ($network_slug === 'moboreels') {
                    $sData = $this->drama['data'] ?? [];
                    $episodes = $sData['episodes'] ?? [];
                    $catalogDrama = Cache::get('moboreels_drama_' . $this->dramaId, []);
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? ($sData['seriesName'] ?? 'MoboReels Drama'),
                        'name' => $catalogDrama['name'] ?? ($sData['seriesName'] ?? 'MoboReels Drama'),
                        'cover' => $catalogDrama['cover'] ?? '',
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => (int)($sData['totalEpisodes'] ?? count($episodes)),
                        'videos' => array_map(function ($ep) {
                            return [
                                'episode' => (int) ($ep['episNum'] ?? 1),
                                'video_id' => (string) ($ep['episId'] ?? ''),
                                'title' => 'Episode ' . ($ep['episNum'] ?? 1),
                                'is_lock' => !empty($ep['isLock']),
                                'duration' => $ep['duration'] ?? null,
                                'video_url' => $ep['mediaUrl'] ?? '',
                                'subtitles' => $ep['subtitles'] ?? [],
                            ];
                        }, $episodes),
                    ];
                } elseif ($network_slug === 'stardusttv') {
                    $sData = $this->drama['data'] ?? [];
                    $rawEps = $sData['episodes'] ?? [];
                    $videos = [];
                    foreach ($rawEps as $num => $epObj) {
                        $vUrl = $epObj['h264'] ?? $epObj['h265'] ?? '';
                        $videos[] = [
                            'episode' => (int) $num,
                            'video_id' => (string) $num,
                            'title' => 'Episode ' . $num,
                            'is_lock' => false,
                            'duration' => null,
                            'video_url' => $vUrl,
                        ];
                    }
                    usort($videos, fn($a, $b) => $a['episode'] <=> $b['episode']);
                    $this->drama = [
                        'title' => $sData['title'] ?? 'Stardust Drama',
                        'name' => $sData['title'] ?? 'Stardust Drama',
                        'cover' => $sData['poster'] ?? $sData['cover'] ?? '',
                        'intro' => $sData['description'] ?? '',
                        'episodes' => (int)($sData['totalEpisodes'] ?? count($videos)),
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'flextv') {
                    $catalogDrama = Cache::get('flextv_drama_' . $this->dramaId, []);
                    $rawEps = $this->drama['data']['episodes'] ?? [];
                    $videos = array_map(function ($ep) {
                        return [
                            'episode' => (int) ($ep['no'] ?? 1),
                            'video_id' => (string) ($ep['no'] ?? 1),
                            'title' => $ep['title'] ?? ("Episode " . ($ep['no'] ?? 1)),
                            'is_lock' => empty($ep['unlocked']) && empty($ep['video_url']),
                            'duration' => $ep['duration'] ?? null,
                            'video_url' => $ep['video_url'] ?? null,
                        ];
                    }, $rawEps);
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? 'FlexTV Drama',
                        'name' => $catalogDrama['name'] ?? 'FlexTV Drama',
                        'cover' => $catalogDrama['cover'] ?? null,
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => count($videos) ?: ($catalogDrama['episodes'] ?? 40),
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'shortswave') {
                    $catalogDrama = Cache::get('shortswave_drama_' . $this->dramaId, []);
                    $rawEps = $this->drama['data'] ?? [];
                    $videos = array_map(function ($ep, $idx) {
                        return [
                            'episode' => (int) ($ep['chapter_index'] ?? ($idx + 1)),
                            'video_id' => (string) ($ep['chapter_id'] ?? ''),
                            'title' => $ep['chapter_name'] ?? ("Episode " . ($idx + 1)),
                            'is_lock' => false,
                            'duration' => null,
                            'video_url' => null,
                            'cover' => $ep['first_frame'] ?? null,
                        ];
                    }, $rawEps, array_keys($rawEps));
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? 'ShortsWave Drama',
                        'name' => $catalogDrama['name'] ?? 'ShortsWave Drama',
                        'cover' => $catalogDrama['cover'] ?? null,
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => count($videos) ?: ($catalogDrama['episodes'] ?? 50),
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'freereels') {
                    $catalogDrama = Cache::get('freereels_drama_' . $this->dramaId, []);
                    $info = $this->drama['data']['data']['info'] ?? [];
                    $totalEps = (int) ($info['episode_count'] ?? $catalogDrama['episodes'] ?? 30);
                    if ($totalEps <= 0) $totalEps = 30;
                    $videos = [];
                    for ($i = 1; $i <= $totalEps; $i++) {
                        $videos[] = [
                            'episode' => $i,
                            'video_id' => (string) $i,
                            'title' => "Episode {$i}",
                            'is_lock' => false,
                            'duration' => null,
                            'video_url' => null,
                        ];
                    }
                    $this->drama = [
                        'title' => $info['name'] ?? ($catalogDrama['name'] ?? 'FreeReels Drama'),
                        'name' => $info['name'] ?? ($catalogDrama['name'] ?? 'FreeReels Drama'),
                        'cover' => $info['cover'] ?? ($catalogDrama['cover'] ?? null),
                        'intro' => $info['desc'] ?? ($catalogDrama['intro'] ?? ''),
                        'episodes' => $totalEps,
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'flickreels') {
                    $catalogDrama = Cache::get('flickreels_drama_' . $this->dramaId, []);
                    $rawEps = $this->drama['data']['list'] ?? [];
                    $videos = array_map(function ($ep, $idx) {
                        return [
                            'episode' => (int) ($ep['chapter_num'] ?? ($idx + 1)),
                            'video_id' => (string) ($ep['chapter_id'] ?? ''),
                            'title' => $ep['chapter_title'] ?? ("Episode " . ($idx + 1)),
                            'is_lock' => false,
                            'duration' => $ep['duration'] ?? null,
                            'video_url' => $ep['hls_url'] ?? null,
                        ];
                    }, $rawEps, array_keys($rawEps));
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? ($this->drama['data']['title'] ?? 'FlickReels Drama'),
                        'name' => $catalogDrama['name'] ?? ($this->drama['data']['title'] ?? 'FlickReels Drama'),
                        'cover' => $catalogDrama['cover'] ?? ($this->drama['data']['cover'] ?? null),
                        'intro' => $catalogDrama['intro'] ?? ($this->drama['data']['introduce'] ?? ''),
                        'episodes' => count($videos) ?: ($catalogDrama['episodes'] ?? 30),
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'starshort') {
                    $catalogDrama = Cache::get('starshort_drama_' . $this->dramaId, []);
                    $info = $this->drama['data'] ?? [];
                    $totalEps = (int) ($info['total_episodes'] ?? $catalogDrama['episodes'] ?? 40);
                    if ($totalEps <= 0) $totalEps = 40;
                    $videos = [];
                    for ($i = 1; $i <= $totalEps; $i++) {
                        $videos[] = [
                            'episode' => $i,
                            'video_id' => (string) $i,
                            'title' => "Episode {$i}",
                            'is_lock' => false,
                            'duration' => null,
                            'video_url' => null,
                        ];
                    }
                    $this->drama = [
                        'title' => $info['title'] ?? ($catalogDrama['name'] ?? 'StarShort Drama'),
                        'name' => $info['title'] ?? ($catalogDrama['name'] ?? 'StarShort Drama'),
                        'cover' => $info['cover'] ?? ($catalogDrama['cover'] ?? null),
                        'intro' => $info['description'] ?? ($catalogDrama['intro'] ?? ''),
                        'episodes' => $totalEps,
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'dotdrama') {
                    $catalogDrama = Cache::get('dotdrama_drama_' . $this->dramaId, []);
                    $bswitc = $this->drama['dgiv']['bswitc'] ?? [];
                    $ebeer = $this->drama['dgiv']['ebeer'] ?? [];
                    $totalEps = (int) ($bswitc['ewood'] ?? (count($ebeer) ?: ($catalogDrama['episodes'] ?? 50)));
                    if ($totalEps <= 0) $totalEps = 50;
                    $videos = [];
                    if (!empty($ebeer)) {
                        foreach ($ebeer as $idx => $epObj) {
                            $epNum = (int) ($epObj['ewheel'] ?? ($idx + 1));
                            $pphys = $epObj['pphys'] ?? [];
                            $bestStream = collect($pphys)->firstWhere('Dbag', '720P') ?? ($pphys[0] ?? null);
                            $vUrl = $bestStream['Bcold'] ?? ($bestStream['Mopp'] ?? null);
                            $videos[] = [
                                'episode' => $epNum,
                                'video_id' => (string) $epNum,
                                'title' => "Episode {$epNum}",
                                'is_lock' => false,
                                'duration' => (float) ($bestStream['Dissue'] ?? 0),
                                'video_url' => $vUrl,
                            ];
                        }
                    } else {
                        for ($i = 1; $i <= $totalEps; $i++) {
                            $videos[] = [
                                'episode' => $i,
                                'video_id' => (string) $i,
                                'title' => "Episode {$i}",
                                'is_lock' => false,
                                'duration' => null,
                                'video_url' => null,
                            ];
                        }
                    }
                    $this->drama = [
                        'title' => $bswitc['nseri'] ?? ($catalogDrama['name'] ?? 'DotDrama'),
                        'name' => $bswitc['nseri'] ?? ($catalogDrama['name'] ?? 'DotDrama'),
                        'cover' => $bswitc['pday'] ?? ($catalogDrama['cover'] ?? null),
                        'intro' => $bswitc['dwill'] ?? ($catalogDrama['intro'] ?? ''),
                        'episodes' => $totalEps,
                        'videos' => $videos,
                    ];
                } elseif ($network_slug === 'dramawave') {
                    $catalogDrama = Cache::get('dramawave_drama_' . $this->dramaId, []);
                    $data = $this->drama['data'] ?? [];
                    $rawEps = $data['items'] ?? [];
                    $totalEps = (int) ($data['episode_count'] ?? (count($rawEps) ?: ($catalogDrama['episodes'] ?? 50)));
                    $videos = array_map(function ($ep, $idx) {
                        $epNum = (int) ($ep['serial_number'] ?? ($idx + 1));
                        return [
                            'episode' => $epNum,
                            'video_id' => (string) ($ep['id'] ?? $epNum),
                            'title' => $ep['name'] ?? ("Episode " . $epNum),
                            'is_lock' => false,
                            'duration' => null,
                            'cover' => $ep['cover'] ?? null,
                            'm3u8_path' => $ep['m3u8_path'] ?? ($ep['1080p_mp4'] ?? ($ep['720p_mp4'] ?? null)),
                            'subtitle_list' => $ep['subtitle_list'] ?? [],
                        ];
                    }, $rawEps, array_keys($rawEps));
                    $this->drama = [
                        'title' => $catalogDrama['name'] ?? ($videos[0]['title'] ?? 'DramaWave Drama'),
                        'name' => $catalogDrama['name'] ?? ($videos[0]['title'] ?? 'DramaWave Drama'),
                        'cover' => $data['cover'] ?? ($catalogDrama['cover'] ?? null),
                        'intro' => $catalogDrama['intro'] ?? '',
                        'episodes' => $totalEps,
                        'videos' => $videos,
                    ];
                }
            } else {
                $cachedDrama = Cache::get($network_slug . '_drama_' . $this->dramaId, []);
                if (!empty($cachedDrama)) {
                    $totalEps = (int) ($cachedDrama['episodes'] ?? 30);
                    if ($totalEps <= 0) $totalEps = 30;
                    $videos = [];
                    for ($i = 1; $i <= $totalEps; $i++) {
                        $videos[] = [
                            'episode' => $i,
                            'video_id' => (string) $i,
                            'title' => "Episode {$i}",
                            'is_lock' => false,
                            'duration' => null,
                            'video_url' => '',
                        ];
                    }
                    $this->drama = [
                        'title' => $cachedDrama['name'] ?? 'Detail Drama',
                        'name' => $cachedDrama['name'] ?? 'Detail Drama',
                        'cover' => $cachedDrama['cover'] ?? '',
                        'intro' => $cachedDrama['intro'] ?? '',
                        'episodes' => $totalEps,
                        'videos' => $videos,
                    ];
                } else {
                    abort(404, 'Drama not found');
                }
            }
        } catch (\Throwable $e) {
            $cachedDrama = Cache::get($network_slug . '_drama_' . $this->dramaId, []);
            $totalEps = (int) ($cachedDrama['episodes'] ?? 30);
            if ($totalEps <= 0) $totalEps = 30;
            $videos = [];
            for ($i = 1; $i <= $totalEps; $i++) {
                $videos[] = [
                    'episode' => $i,
                    'video_id' => (string) $i,
                    'title' => "Episode {$i}",
                    'is_lock' => false,
                    'duration' => null,
                    'video_url' => '',
                ];
            }
            $this->drama = [
                'title' => $cachedDrama['name'] ?? 'Detail Drama',
                'name' => $cachedDrama['name'] ?? 'Detail Drama',
                'cover' => $cachedDrama['cover'] ?? '',
                'intro' => $cachedDrama['intro'] ?? '',
                'episodes' => $totalEps,
                'videos' => $videos,
            ];
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
        // Standar Dracin V2: Semua network otomatis play saat dibuka
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

        if ($this->networkSlug === 'serealplus' || $this->networkSlug === 'serialplus') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            if (!$activeEp) {
                $activeEp = ($this->drama['videos'] ?? [])[$ep - 1] ?? null;
            }

            if (!$activeEp) {
                \Filament\Notifications\Notification::make()
                    ->title('Episode tidak ditemukan')
                    ->danger()
                    ->send();
                return;
            }

            $videoUrl = $activeEp['video_url'] ?? null;

            if (!$videoUrl) {
                try {
                    $epRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 10])
                        ->get("https://serealplus.goodbos.online/api/episodes?id={$this->dramaId}&lang=id&code={$apiKey}");
                    if ($epRes->successful()) {
                        $epJson = $epRes->json();
                        $epList = $epJson['episodes'] ?? [];
                        $targetEp = collect($epList)->firstWhere('seqNo', $ep) ?? ($epList[$ep - 1] ?? null);
                        if ($targetEp && !empty($targetEp['videoUrl'])) {
                            $videoUrl = $targetEp['videoUrl'];
                        }
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()
                    ->title('Video episode tidak ditemukan')
                    ->danger()
                    ->send();
                return;
            }

            $qualityList = [
                [
                    'label' => 'HD',
                    'url' => $videoUrl,
                    'isDefault' => true,
                ]
            ];

            $videoData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => $activeEp['duration'] ?? null,
            ];
            $this->streamData = $videoData;
            $nextEpisode = null;
            if (!empty($this->drama['videos']) && $ep < count($this->drama['videos'])) {
                $nextEpisode = $ep + 1;
            }

            $this->saveWatchHistory($ep);

            $this->dispatch(
                'init-player',
                videoUrl: $videoUrl,
                qualityList: $qualityList,
                episode: $ep,
                nextEpisode: $nextEpisode,
                subtitles: [],
            );
            return;
        }

        if ($this->networkSlug === 'dramabox') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            if (!$activeEp) {
                $activeEp = ($this->drama['videos'] ?? [])[$ep - 1] ?? null;
            }

            if (!$activeEp) {
                \Filament\Notifications\Notification::make()
                    ->title('Episode tidak ditemukan')
                    ->danger()
                    ->send();
                return;
            }

            $qualityList = [];
            if (!empty($activeEp['videoUrl1080'])) {
                $qualityList[] = [
                    'label' => '1080p FHD',
                    'url' => $activeEp['videoUrl1080'],
                    'isDefault' => true,
                ];
            }
            if (!empty($activeEp['videoUrl720'])) {
                $qualityList[] = [
                    'label' => '720p HD',
                    'url' => $activeEp['videoUrl720'],
                    'isDefault' => empty($qualityList),
                ];
            }
            if (!empty($activeEp['videoUrl540'])) {
                $qualityList[] = [
                    'label' => '540p SD',
                    'url' => $activeEp['videoUrl540'],
                    'isDefault' => empty($qualityList),
                ];
            }
            if (empty($qualityList) && !empty($activeEp['video_url'])) {
                $qualityList[] = [
                    'label' => 'HD',
                    'url' => $activeEp['video_url'],
                    'isDefault' => true,
                ];
            }

            $videoUrl = !empty($qualityList) ? $qualityList[0]['url'] : null;

            if ($videoUrl) {
                $videoData = [
                    'videoUrl' => $videoUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => [],
                    'duration' => null,
                ];
                $this->streamData = $videoData;
                $nextEpisode = null;
                if (!empty($this->drama['videos']) && $ep < count($this->drama['videos'])) {
                    $nextEpisode = $ep + 1;
                }

                $this->saveWatchHistory($ep);

                $this->dispatch(
                    'init-player',
                    videoUrl: $videoUrl,
                    qualityList: $qualityList,
                    episode: $ep,
                    nextEpisode: $nextEpisode,
                    subtitles: []
                );
            }
            return;
        }

        if ($this->networkSlug === 'reelbuzz') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            if (!$activeEp) {
                $activeEp = ($this->drama['videos'] ?? [])[$ep - 1] ?? null;
            }

            $videoUrl = $activeEp['video_url'] ?? null;

            if (!$videoUrl) {
                try {
                    $playRes = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4], 'timeout' => 10])
                        ->get("https://reelbuzz.goodbos.online/api/drama/play?dramaId={$this->dramaId}&ep={$ep}&code={$apiKey}");
                    if ($playRes->successful()) {
                        $playJson = $playRes->json();
                        $isEncrypted = $playJson['encrypted'] ?? false;
                        $playUrl = $playJson['playURL'] ?? $playJson['proxyURL'] ?? '';

                        // Jangan gunakan scheme internal aplikasi (txvod://)
                        if (str_starts_with($playUrl, 'txvod://') || empty($playUrl)) {
                            $videoUrl = null;
                        } elseif ($isEncrypted) {
                            // Untuk video terenkripsi, gunakan worker decrypt upstream langsung
                            $videoUrl = $playUrl;
                        } elseif (str_contains($playUrl, '?url=')) {
                            parse_str(parse_url($playUrl, PHP_URL_QUERY), $queryParts);
                            $videoUrl = $queryParts['url'] ?? $playUrl;
                        } else {
                            $videoUrl = $playUrl;
                        }
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()
                    ->title('Video episode tidak dapat diputar')
                    ->body('Episode ini menggunakan proteksi DRM khusus atau scheme eksklusif aplikasi.')
                    ->danger()
                    ->send();
                return;
            }

            $qualityList = [
                [
                    'label' => '720p HD',
                    'url' => $videoUrl,
                    'isDefault' => true,
                ]
            ];

            $videoData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => null,
            ];
            $this->streamData = $videoData;
            $nextEpisode = null;
            if (!empty($this->drama['videos']) && $ep < count($this->drama['videos'])) {
                $nextEpisode = $ep + 1;
            }

            $this->saveWatchHistory($ep);

            $this->dispatch(
                'init-player',
                videoUrl: $videoUrl,
                qualityList: $qualityList,
                episode: $ep,
                nextEpisode: $nextEpisode,
                subtitles: []
            );
            return;
        }

        if ($this->networkSlug === 'goodshort') {
            $chapterId = $this->activeEpisode['video_id'] ?? null;
            $lang = request()->query('lang', 'id');
            $apiLang = $lang === 'id' ? 'in' : $lang;
            $batchUrlEndpoint = "https://goodshort.goodbos.online/batchload/{$this->dramaId}?lang={$apiLang}&code={$apiKey}";
            
            try {
                $batchResponse = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                    ->timeout(12)
                    ->get($batchUrlEndpoint);

                if ($batchResponse->successful() && !empty($batchResponse->json('data.episodes'))) {
                    $episodesList = $batchResponse->json('data.episodes');
                    $matchedEpisode = null;
                    if ($chapterId) {
                        $matchedEpisode = collect($episodesList)->first(function ($item) use ($chapterId) {
                            return (string)($item['id'] ?? '') === (string)$chapterId;
                        });
                    }
                    if (!$matchedEpisode) {
                        $matchedEpisode = $episodesList[$ep - 1] ?? ($episodesList[0] ?? null);
                    }

                    if ($matchedEpisode) {
                        $qualityList = [];
                        $videos = $matchedEpisode['videos'] ?? [];
                        if (!empty($videos) && is_array($videos)) {
                            // Urutkan resolusi 1080p, 720p, 540p
                            $sortedVideos = collect($videos)->sortByDesc(function ($v) {
                                $type = strtolower($v['type'] ?? '');
                                if (str_contains($type, '1080')) return 1080;
                                if (str_contains($type, '720')) return 720;
                                if (str_contains($type, '540')) return 540;
                                return 0;
                            })->values()->toArray();

                            foreach ($sortedVideos as $v) {
                                $label = $v['type'] ?? '720p';
                                $filePath = $v['filePath'] ?? '';
                                if ($filePath) {
                                    $videoUrlProxy = url('/api/proxy-hls?url=' . urlencode($filePath));
                                    $qualityList[] = [
                                        'label' => $label,
                                        'url' => $videoUrlProxy,
                                        'isDefault' => empty($qualityList),
                                    ];
                                }
                            }
                        }

                        if (!empty($qualityList)) {
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
                            return;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback to activeEpisode
            }

            // Fallback to activeEpisode if batchload endpoint failed
            $qualityList = [];
            $multiVideos = $this->activeEpisode['multi_videos'] ?? [];
            if (!empty($multiVideos) && is_array($multiVideos)) {
                $sortedVideos = collect($multiVideos)->sortByDesc(function ($v) {
                    $type = strtolower($v['type'] ?? '');
                    if (str_contains($type, '1080')) return 1080;
                    if (str_contains($type, '720')) return 720;
                    if (str_contains($type, '540')) return 540;
                    return 0;
                })->values()->toArray();

                foreach ($sortedVideos as $v) {
                    $label = $v['type'] ?? '720p';
                    $rawUrl = $v['filePath'] ?? ($v['cdnList'][0]['videoPath'] ?? '');
                    if ($rawUrl) {
                        $qualityList[] = [
                            'label' => $label,
                            'url' => url('/api/proxy-hls?url=' . urlencode($rawUrl)),
                            'isDefault' => empty($qualityList),
                        ];
                    }
                }
            }

            if (empty($qualityList) && !empty($this->activeEpisode['video_url'])) {
                $qualityList[] = [
                    'label' => '720p HD',
                    'url' => url('/api/proxy-hls?url=' . urlencode($this->activeEpisode['video_url'])),
                    'isDefault' => true,
                ];
            }

            if (!empty($qualityList)) {
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
                return;
            }

            \Filament\Notifications\Notification::make()
                ->title('URL video Goodshort tidak tersedia')
                ->danger()
                ->send();
            return;
        }

        if ($this->networkSlug === 'raptdrama') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            $playUrl = 'https://raptdrama.goodbos.online/api/playurl'
                . '?id=' . urlencode($this->dramaId)
                . '&ep=' . $ep
                . '&lang=en'
                . '&code=' . urlencode($apiKey);

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

            if (auth()->check() && !$this->isPrefetching) {
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

        if ($this->networkSlug === 'reelshort') {
            $streams = $this->activeEpisode['streams'] ?? [];
            $qualityList = [];
            foreach ($streams as $s) {
                if (!empty($s['url'])) {
                    $u = $s['url'];
                    $q = $s['quality'] ?? '';
                    $qLabel = $q === '0p' ? 'Auto' : ($q ?: 'HD');
                    $isH264 = !str_contains($u, '-video-') && !str_contains($u, '/video/') && $q !== '0p'
                        && (str_contains($u, 'vod-') || str_contains($u, '-ld.m3u8') || $q === '540p' || $q === '720p');
                    $priority = $isH264 ? 1 : (($q === '0p' || str_contains($u, '-video-') || str_contains($u, '/video/')) ? 3 : 2);
                    $qualityList[] = [
                        'label' => $qLabel . ($isH264 ? ' (Universal)' : ''),
                        'url' => $u,
                        'isDefault' => $isH264,
                        'priority' => $priority,
                    ];
                }
            }
            // Urutkan agar stream H.264 kompatibel di depan
            usort($qualityList, fn($a, $b) => ($a['priority'] ?? 9) <=> ($b['priority'] ?? 9));
            if (!empty($qualityList)) {
                $qualityList[0]['isDefault'] = true;
            }
            if (empty($qualityList) && !empty($this->activeEpisode['video_url'])) {
                $qualityList[] = [
                    'label' => '720p HD',
                    'url' => $this->activeEpisode['video_url'],
                    'isDefault' => true,
                ];
            }
            if (empty($qualityList)) {
                try {
                    $lang = request()->query('lang', 'in');
                    $allepsUrl = "https://reelshort.goodbos.online/allepisodes/{$this->dramaId}?code={$apiKey}&lang={$lang}";
                    $res = Http::timeout(10)->get($allepsUrl);
                    if ($res->successful()) {
                        $allEps = $res->json('episodes') ?? [];
                        foreach ($allEps as $e) {
                            if ((int)($e['episode'] ?? 0) === (int)$ep) {
                                foreach ($e['streams'] ?? [] as $s) {
                                    if (!empty($s['url'])) {
                                        $u = $s['url'];
                                        $q = $s['quality'] ?? '';
                                        $qLabel = $q === '0p' ? 'Auto' : ($q ?: 'HD');
                                        $isH264 = !str_contains($u, '-video-') && !str_contains($u, '/video/') && $q !== '0p'
                                            && (str_contains($u, 'vod-') || str_contains($u, '-ld.m3u8') || $q === '540p' || $q === '720p');
                                        $priority = $isH264 ? 1 : (($q === '0p' || str_contains($u, '-video-') || str_contains($u, '/video/')) ? 3 : 2);
                                        $qualityList[] = [
                                            'label' => $qLabel . ($isH264 ? ' (Universal)' : ''),
                                            'url' => $u,
                                            'isDefault' => $isH264,
                                            'priority' => $priority,
                                        ];
                                    }
                                }
                                // Urutkan agar stream H.264 universal berada di paling atas
                                usort($qualityList, fn($a, $b) => ($a['priority'] ?? 9) <=> ($b['priority'] ?? 9));
                                if (!empty($qualityList)) {
                                    $qualityList[0]['isDefault'] = true;
                                }
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }
            if (!empty($qualityList)) {
                $videoUrl = $qualityList[0]['url'];
                $this->streamData = [
                    'videoUrl' => $videoUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => [],
                    'duration' => $this->activeEpisode['duration'] ?? null,
                ];
                $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
                $this->saveWatchHistory($ep);
                return;
            }
        }

        if ($this->networkSlug === 'vibeshort' || $this->networkSlug === 'storyreel') {
            $videoUrls = $this->activeEpisode['video_urls'] ?? [];
            $qualityList = [];
            $order = ['720' => '720p', '1080' => '1080p', '480' => '480p'];
            foreach ($order as $k => $label) {
                if (!empty($videoUrls[$k])) {
                    $qualityList[] = [
                        'label' => $label,
                        'url' => $videoUrls[$k],
                        'isDefault' => empty($qualityList),
                    ];
                }
            }
            if (empty($qualityList) && !empty($this->activeEpisode['video_url'])) {
                $qualityList[] = [
                    'label' => '720p HD',
                    'url' => url('/api/proxy-hls?url=' . urlencode($this->activeEpisode['video_url'])),
                    'isDefault' => true,
                ];
            }
            if (!empty($qualityList)) {
                $videoUrl = $qualityList[0]['url'];
                $this->streamData = [
                    'videoUrl' => $videoUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => [],
                    'duration' => $this->activeEpisode['duration'] ?? null,
                ];
                $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
                $this->saveWatchHistory($ep);
                return;
            }
        }

        if ($this->networkSlug === 'moboreels') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            if ($videoUrl) {
                $streamUrl = url('/api/proxy-stream?url=' . urlencode($videoUrl));
                $qualityList = [['label' => 'HD 720p', 'url' => $streamUrl, 'isDefault' => true]];
                $subtitles = [];
                $rawSub = $this->activeEpisode['subtitles'] ?? null;
                if (is_array($rawSub)) {
                    foreach ($rawSub as $sub) {
                        if (!empty($sub['subtitleUrl'])) {
                            $subtitles[] = [
                                'lang' => $sub['languageName'] ?? 'Subtitle',
                                'url' => url('/api/proxy-subtitle?url=' . urlencode($sub['subtitleUrl'])),
                            ];
                        }
                    }
                } elseif (is_string($rawSub) && !empty($rawSub)) {
                    $subtitles[] = [
                        'lang' => 'Indonesia',
                        'url' => url('/api/proxy-subtitle?url=' . urlencode($rawSub)),
                    ];
                }

                $this->streamData = [
                    'videoUrl' => $streamUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => $subtitles,
                    'duration' => $this->activeEpisode['duration'] ?? null,
                ];
                $this->dispatch('init-player', videoUrl: $streamUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: $subtitles);
                $this->saveWatchHistory($ep);
                return;
            }
        }

        if ($this->networkSlug === 'stardusttv') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            if ($videoUrl) {
                $streamUrl = url('/api/proxy-hls?url=' . urlencode($videoUrl));
                $qualityList = [['label' => '720p HD', 'url' => $streamUrl, 'isDefault' => true]];
                $this->streamData = [
                    'videoUrl' => $streamUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => [],
                    'duration' => null,
                ];
                $this->dispatch('init-player', videoUrl: $streamUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
                $this->saveWatchHistory($ep);
                return;
            }
        }

        if ($this->networkSlug === 'raptdrama') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            if ($videoUrl) {
                $streamUrl = url('/api/proxy-hls?url=' . urlencode($videoUrl));
                $qualityList = [['label' => '720p HD', 'url' => $streamUrl, 'isDefault' => true]];
                $this->streamData = [
                    'videoUrl' => $streamUrl,
                    'qualityList' => $qualityList,
                    'subtitles' => [],
                    'duration' => null,
                ];
                $this->dispatch('init-player', videoUrl: $streamUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
                $this->saveWatchHistory($ep);
                return;
            }
        }

        // FlexTV Playback
        if ($this->networkSlug === 'flextv') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            $videoUrl = $activeEp['video_url'] ?? null;
            if (!$videoUrl) {
                try {
                    $allEpsUrl = "https://flextv.goodbos.online/api/allepisodes?id={$this->dramaId}&code={$apiKey}";
                    $res = Http::timeout(15)->get($allEpsUrl);
                    if ($res->successful()) {
                        $rawEps = $res->json('data.episodes') ?? [];
                        $found = collect($rawEps)->firstWhere('no', $ep);
                        $videoUrl = $found['video_url'] ?? null;
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $qualityList = [['label' => 'HD', 'url' => $videoUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => $activeEp['duration'] ?? null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
            return;
        }

        // ShortsWave Playback
        if ($this->networkSlug === 'shortswave') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            $chapterId = $activeEp['video_id'] ?? null;
            if (!$chapterId) {
                try {
                    $epsUrl = "https://shortwave.goodbos.online/api/drama/{$this->dramaId}/episodes?lang=in&code={$apiKey}";
                    $epsRes = Http::timeout(12)->get($epsUrl);
                    if ($epsRes->successful()) {
                        $rawEps = $epsRes->json('data') ?? [];
                        $found = collect($rawEps)->firstWhere('chapter_index', $ep) ?? ($rawEps[$ep - 1] ?? null);
                        $chapterId = $found['chapter_id'] ?? null;
                    }
                } catch (\Exception $e) {}
            }

            $videoUrl = null;
            $subtitles = [];
            if ($chapterId) {
                try {
                    $unlockUrl = "https://shortwave.goodbos.online/api/unlock?drama_id={$this->dramaId}&chapter_id={$chapterId}&lang=in&code={$apiKey}";
                    $uRes = Http::timeout(15)->get($unlockUrl);
                    if ($uRes->successful()) {
                        $videoUrl = $uRes->json('data.play_url');
                        $subList = $uRes->json('data.sublist') ?? [];
                        foreach ($subList as $s) {
                            if (!empty($s['url'])) {
                                $subtitles[] = [
                                    'lang' => $s['language'] ?? 'in',
                                    'label' => $s['language'] ?? 'Sub',
                                    'url' => $s['url'],
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $qualityList = [['label' => 'HD', 'url' => $videoUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => $subtitles,
                'duration' => null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: $subtitles);
            return;
        }

        // FreeReels Playback
        if ($this->networkSlug === 'freereels') {
            $videoUrl = null;
            $subtitles = [];
            try {
                $playUrl = "https://drakula.goodbos.online/api/freereels/drama/{$this->dramaId}/play/{$ep}?lang=in&code={$apiKey}";
                $pRes = Http::timeout(15)->get($playUrl);
                if ($pRes->successful()) {
                    $videoUrl = $pRes->json('data.data.m3u8_url') ?? $pRes->json('data.data.external_audio_h264_m3u8');
                }
            } catch (\Exception $e) {}

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $proxyVideoUrl = url('/api/proxy-hls?url=' . urlencode($videoUrl));
            $qualityList = [['label' => 'HD', 'url' => $proxyVideoUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $proxyVideoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $proxyVideoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
            return;
        }

        // FlickReels Playback
        if ($this->networkSlug === 'flickreels') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            $videoUrl = $activeEp['video_url'] ?? null;
            if (!$videoUrl) {
                try {
                    $bUrl = "https://flickreels.goodbos.online/batchload/{$this->dramaId}?lang=in&code={$apiKey}";
                    $bRes = Http::timeout(15)->get($bUrl);
                    if ($bRes->successful()) {
                        $rawEps = $bRes->json('data.list') ?? [];
                        $found = collect($rawEps)->firstWhere('chapter_num', $ep);
                        $videoUrl = $found['hls_url'] ?? null;
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $qualityList = [['label' => 'HD', 'url' => $videoUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => $activeEp['duration'] ?? null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
            return;
        }

        // StarShort Playback
        if ($this->networkSlug === 'starshort') {
            $videoUrl = null;
            try {
                $wUrl = "https://drakula.goodbos.online/api/starshort/watch/{$this->dramaId}/{$ep}?locale=id&code={$apiKey}";
                $wRes = Http::timeout(15)->get($wUrl);
                if ($wRes->successful()) {
                    $videoUrl = $wRes->json('data.video_url');
                }
            } catch (\Exception $e) {}

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $qualityList = [['label' => 'HD', 'url' => $videoUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $videoUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $videoUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
            return;
        }

        // DotDrama Playback
        if ($this->networkSlug === 'dotdrama') {
            $videoUrl = $this->activeEpisode['video_url'] ?? null;
            if (!$videoUrl) {
                try {
                    $dUrl = "https://dotdrama.goodbos.online/api/drama/{$this->dramaId}/{$ep}?code={$apiKey}";
                    $dRes = Http::timeout(15)->get($dUrl);
                    if ($dRes->successful()) {
                        $videoUrl = $dRes->json('video_hd') ?? $dRes->json('video_url');
                    }
                } catch (\Exception $e) {}
            }

            if (!$videoUrl) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            $proxyStreamUrl = url('/api/proxy-stream?url=' . urlencode($videoUrl));
            $qualityList = [['label' => 'HD', 'url' => $proxyStreamUrl, 'isDefault' => true]];
            $this->streamData = [
                'videoUrl' => $proxyStreamUrl,
                'qualityList' => $qualityList,
                'subtitles' => [],
                'duration' => null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $proxyStreamUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: []);
            return;
        }

        // DramaWave Playback
        if ($this->networkSlug === 'dramawave') {
            $activeEp = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep);
            $rawM3u8 = $activeEp['m3u8_path'] ?? null;
            $rawSubs = $activeEp['subtitle_list'] ?? [];

            if (!$rawM3u8) {
                try {
                    $dUrl = "https://dramawave.goodbos.online/api/drama/{$this->dramaId}?lang=in&code={$apiKey}";
                    $dRes = Http::timeout(15)->get($dUrl);
                    if ($dRes->successful()) {
                        $rawItems = $dRes->json('data.items') ?? [];
                        $found = collect($rawItems)->firstWhere('serial_number', $ep);
                        $rawM3u8 = $found['m3u8_path'] ?? ($found['1080p_mp4'] ?? ($found['720p_mp4'] ?? null));
                        $rawSubs = $found['subtitle_list'] ?? [];
                    }
                } catch (\Exception $e) {}
            }

            if (!$rawM3u8) {
                \Filament\Notifications\Notification::make()->title('Video episode tidak ditemukan')->danger()->send();
                return;
            }

            // Wrap HLS master playlist with proxy-hls
            $proxyHlsUrl = url('/api/proxy-hls?url=' . urlencode($rawM3u8));
            $qualityList = [['label' => 'HD', 'url' => $proxyHlsUrl, 'isDefault' => true]];

            // Subtitles
            $subtitles = [];
            if (!empty($rawSubs) && is_array($rawSubs)) {
                foreach ($rawSubs as $sub) {
                    $subUrl = $sub['subtitle'] ?? '';
                    if (empty($subUrl)) continue;
                    $langCode = strtolower(substr($sub['language'] ?? '', 0, 2));
                    $isIndo = $langCode === 'id' || str_contains(strtolower($sub['display_name'] ?? ''), 'indonesia');
                    $subtitles[] = [
                        'url' => url('/api/proxy-subtitle?url=' . urlencode($subUrl)),
                        'name' => $sub['display_name'] ?? ($sub['language'] ?? 'Subtitle'),
                        'lang' => $isIndo ? 'id' : ($langCode ?: 'en'),
                        'default' => $isIndo,
                    ];
                }
                usort($subtitles, fn($a, $b) => ($b['default'] ? 1 : 0) <=> ($a['default'] ? 1 : 0));
            }

            $this->streamData = [
                'videoUrl' => $proxyHlsUrl,
                'qualityList' => $qualityList,
                'subtitles' => $subtitles,
                'duration' => null,
            ];
            $this->saveWatchHistory($ep);
            $this->dispatch('init-player', videoUrl: $proxyHlsUrl, qualityList: $qualityList, episode: $ep, nextEpisode: $this->findNextEpisode($ep), subtitles: $subtitles);
            return;
        }

        if ($this->networkSlug === 'pinedrama') {
            $code = $apiKey;
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

                    if (auth()->check() && !$this->isPrefetching) {
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

    /**
     * Resolve stream episode untuk buffer 3-slot (prev/current/next) tanpa efek samping.
     * Memakai logika playEpisode() yang sama, lalu menangkap payload 'init-player'
     * dan membuang event, notifikasi, serta perubahan state yang ditimbulkannya.
     */
    public function prefetchEpisode(int $ep): ?array
    {
        $this->skipRender();

        if ($ep < 1) {
            return null;
        }

        $snapshot = [
            'activeEpisode' => $this->activeEpisode,
            'streamData' => $this->streamData,
            'lastWatchedEpisode' => $this->lastWatchedEpisode,
            'watchedEpisodes' => $this->watchedEpisodes,
        ];
        $hadNotifications = session()->has('filament.notifications');
        $savedNotifications = session()->get('filament.notifications');
        $dispatchedBefore = store($this)->get('dispatched', []);

        $this->isPrefetching = true;

        try {
            $this->playEpisode($ep);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Dracin prefetch gagal: ' . $e->getMessage(), ['ep' => $ep]);
        } finally {
            $this->isPrefetching = false;
        }

        $dispatched = store($this)->get('dispatched', []);
        $newEvents = array_slice($dispatched, count($dispatchedBefore));
        store($this)->set('dispatched', $dispatchedBefore);

        foreach ($snapshot as $property => $value) {
            $this->{$property} = $value;
        }

        if ($hadNotifications) {
            session()->put('filament.notifications', $savedNotifications);
        } else {
            session()->forget('filament.notifications');
        }

        foreach ($newEvents as $event) {
            $serialized = $event->serialize();
            if (($serialized['name'] ?? null) === 'init-player') {
                $payload = $serialized['params'] ?? [];

                return !empty($payload['videoUrl']) ? $payload : null;
            }
        }

        return null;
    }

    /**
     * Dipanggil client setelah episode dari buffer diputar instan (tanpa round-trip playEpisode).
     */
    public function markEpisodeWatched(int $ep): void
    {
        if ($ep < 1 || (auth()->check() && auth()->user()->isSubscriptionExpired())) {
            $this->skipRender();

            return;
        }

        $this->activeEpisode = collect($this->drama['videos'] ?? [])->firstWhere('episode', $ep) ?? ['episode' => $ep];
        $this->saveWatchHistory($ep);
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
        if (!auth()->check() || $this->isPrefetching) {
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
     * Network short-drama wajib memakai Detail Dracin V2 (Standar NetShort: Fullscreen 9:16 + Autoplay).
     */
    public function getView(): string
    {
        return 'filament.streaming.pages.dracin-detail-v2';
    }
}