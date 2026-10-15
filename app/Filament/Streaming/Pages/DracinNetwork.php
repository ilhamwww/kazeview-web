<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\Network;

use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DracinNetwork extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationLabel = 'Dracin Network';
    protected static ?string $title = 'Drama China - Network';
    protected static ?string $slug = 'dracin/{slug}';
    protected static string $view = 'filament.streaming.pages.dracin-network';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $slugVal;
    public ?array $network = null;
    public array $dramas = [];
    public int $offset = 0;
    public bool $hasMore = true;
    public string $search = '';

    /**
     * Tab aktif untuk network yang menggunakan multi-feed (mis. shortmax: popular/new/hot/vip).
     * Diabaikan oleh network lain.
     */
    public string $tab = 'popular';

    /**
     * Bahasa terpilih untuk goodshort. Default 'id'.
     */
    public string $lang = 'id';

    public function updatedLang(): void
    {
        $this->dramas = [];
        $this->offset = 0;
        $this->hasMore = true;
        $this->loadDramas();
    }

    public function changeLang(string $lang): void
    {
        $this->lang = $lang;
        $this->updatedLang();
    }

    public function updatedSearch(): void
    {
        $this->dramas = [];
        $this->offset = 0;
        $this->hasMore = true;
        $this->loadDramas();
    }

    public function updatedTab(): void
    {
        $this->dramas = [];
        $this->offset = 0;
        $this->hasMore = true;
        $this->loadDramas();
    }

    public function setTab(string $tab): void
    {
        $allowed = ['popular', 'new', 'hot', 'vip', 'trending', 'latest', 'foryou', 'dubbed'];
        if (!in_array($tab, $allowed, true)) {
            return;
        }
        if ($this->tab === $tab) {
            return;
        }
        $this->tab = $tab;
        $this->updatedTab();
    }

    public function mount(string $slug): void
    {
        $this->slugVal = $slug;

        // Fetch networks from cache or API
        $networks = Cache::remember('dramabuzz_networks', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                $apiUrl = $setting->dramabuzz_api_url;
                $apiKey = $setting->dramabuzz_api_key;

                if (!$apiUrl) return [];

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

            return $activePlatforms;
        });

        // Find the network by slug
        $this->network = collect($networks)->first(function ($net) use ($slug) {
            return $net['id'] === $slug || ($slug === 'serealplus' && $net['id'] === 'serialplus') || ($slug === 'serialplus' && $net['id'] === 'serealplus');
        });

        if (!$this->network) {
            abort(404, 'Network not found or inactive');
        }

        $nameMap = [
            'serealplus' => 'Sereal+',
            'serialplus' => 'Sereal+',
            'stardusttv' => 'StardustTV',
            'vibeshort' => 'VibeShort',
            'storyreel' => 'StoryReel',
            'reelshort' => 'ReelShort',
            'moboreels' => 'MoboReels',
            'shortmax' => 'ShortMax',
            'goodshort' => 'GoodShort',
            'dramabox' => 'DramaBox',
            'pinedrama' => 'PineDrama',
            'raptdrama' => 'RaptDrama',
            'reelbuzz' => 'ReelBuzz',
            'melolo' => 'Melolo',
        ];
        if (isset($nameMap[$slug])) {
            $this->network['name'] = $nameMap[$slug];
        }

        $setting = ScraperSetting::getInstance();
        $onlineNetworks = $setting->dramabuzz_network_statuses;
        if (!is_null($onlineNetworks) && !in_array($slug, $onlineNetworks)) {
            \Filament\Notifications\Notification::make()
                ->title('Network ' . $this->network['name'] . ' Sedang Offline')
                ->warning()
                ->send();
            $this->redirect(\App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming'));
            return;
        }

        // Set default lang ke 'id' jika slug goodshort (sesuai 'id' di instruksi)
        if ($this->slugVal === 'goodshort') {
            $this->lang = 'id';
        }

        // Set default tab untuk dramabox
        if ($this->slugVal === 'dramabox') {
            $this->tab = 'trending';
        }

        if ($this->slugVal === 'reelshort') {
            $this->tab = 'POPULER';
        }
        if ($this->slugVal === 'vibeshort') {
            $this->tab = '337';
        }
        if ($this->slugVal === 'storyreel') {
            $this->tab = '337';
        }
        if ($this->slugVal === 'moboreels') {
            $this->lang = 'in';
        }
        if ($this->slugVal === 'raptdrama') {
            $this->tab = 'trending';
        }
        if ($this->slugVal === 'stardusttv') {
            $this->lang = 'id';
        }
        if ($this->slugVal === 'serealplus' || $this->slugVal === 'serialplus') {
            $this->lang = 'id';
            $this->tab = 'popular';
        }

        static::$title = 'Drama China - ' . $this->network['name'];

        // Load initial dramas
        $this->loadDramas();
    }

    public function loadDramas(): void
    {
        if (!$this->hasMore) {
            return;
        }

        $setting = ScraperSetting::getInstance();
        $apiKey = $setting->dramabuzz_api_key;
        $isSearch = !empty($this->search);

        if ($this->slugVal === 'serealplus' || $this->slugVal === 'serialplus') {
            $page = $this->offset + 1;
            $lang = $this->lang ?: 'id';

            if ($isSearch) {
                $url = "https://serealplus.goodbos.online/api/search?q=" . urlencode($this->search) . "&lang={$lang}&page={$page}&code={$apiKey}";
            } else {
                $endpoint = ($this->tab === 'latest') ? 'latest' : 'home';
                $url = "https://serealplus.goodbos.online/api/{$endpoint}?lang={$lang}&page={$page}&code={$apiKey}";
            }

            try {
                $response = Http::withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    'timeout' => 15,
                ])->get($url);

                if ($response->successful()) {
                    $json = $response->json() ?? [];
                    $items = $json['data']['list'] ?? [];

                    $mapped = array_map(function ($it) {
                        $dramaId = (string) ($it['contentId'] ?? $it['id'] ?? '');
                        $drama = [
                            'id' => $dramaId,
                            'name' => $it['contentName'] ?? $it['name'] ?? '',
                            'cover' => $it['contentCoverUrl'] ?? $it['cover'] ?? null,
                            'episodes' => (int) ($it['chapterNumber'] ?? $it['chapterNum'] ?? 0),
                            'intro' => $it['introduce'] ?? $it['shortIntroduce'] ?? '',
                            'is_hot' => ($it['tag'] ?? 0) === 3,
                            'is_new' => !empty($it['isNewVideo']),
                        ];

                        if ($drama['id'] !== '') {
                            Cache::put('serealplus_drama_' . $drama['id'], $drama, now()->addHours(6));
                        }

                        return $drama;
                    }, $items);

                    $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                    $this->dramas = array_merge($this->dramas, $valid);
                    $this->offset++;
                    $this->hasMore = !empty($json['data']['hasMore']) && count($valid) > 0;
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'dramabox') {
            $base = 'https://dramabox.goodbos.online/api/dramabox';
            $page = $this->offset + 1;

            if ($isSearch) {
                $url = "{$base}/search?query=" . urlencode($this->search) . "&lang=in";
            } else {
                $tab = in_array($this->tab, ['trending', 'latest', 'foryou', 'dubbed'], true) ? $this->tab : 'trending';
                if ($tab === 'dubbed') {
                    $url = "{$base}/dubbed?classify=terpopuler&page={$page}&lang=in";
                } elseif ($page > 1) {
                    $url = "{$base}/browse?page={$page}&lang=in";
                } else {
                    $url = "{$base}/{$tab}?lang=in";
                }
            }

            try {
                $response = Http::withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    'timeout' => 15,
                ])->withHeaders([
                    'x-token' => $apiKey,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])->get($url);

                if ($response->successful()) {
                    $rawJson = $response->json() ?? [];
                    $items = [];
                    if (isset($rawJson['items']) && is_array($rawJson['items'])) {
                        $items = $rawJson['items'];
                    } elseif (isset($rawJson['data']) && is_array($rawJson['data'])) {
                        $items = $rawJson['data'];
                    } elseif (isset($rawJson['results']) && is_array($rawJson['results'])) {
                        $items = $rawJson['results'];
                    } elseif (is_array($rawJson) && array_is_list($rawJson)) {
                        $items = $rawJson;
                    } elseif (is_array($rawJson)) {
                        foreach ($rawJson as $val) {
                            if (is_array($val) && (!empty($val['bookId']) || !empty($val['id']))) {
                                $items[] = $val;
                            }
                        }
                    }

                    // Only keep valid drama objects that have bookId or id
                    $validItems = array_values(array_filter($items, function ($it) {
                        return is_array($it) && (!empty($it['bookId']) || !empty($it['id']));
                    }));

                    if (count($validItems) > 0) {
                        $mapped = array_map(function ($item) {
                            $tags = $item['tags'] ?? $item['tagNames'] ?? [];
                            $intro = !empty($item['introduction']) 
                                ? $item['introduction'] 
                                : (is_array($tags) ? implode(', ', $tags) : '');

                            $dramaId = (string) ($item['bookId'] ?? $item['id'] ?? '');

                            $drama = [
                                'id' => $dramaId,
                                'name' => $item['bookName'] ?? $item['name'] ?? '',
                                'cover' => $item['coverWap'] ?? $item['cover'] ?? null,
                                'episodes' => isset($item['chapterCount']) ? (int) $item['chapterCount'] : (isset($item['episodes']) ? (int) $item['episodes'] : null),
                                'intro' => $intro,
                                'is_hot' => !empty($item['corner']) && ($item['corner']['name'] ?? '') === 'Terpopuler',
                                'is_new' => false,
                            ];

                            if ($drama['id'] !== '') {
                                Cache::put('dramabox_drama_' . $drama['id'], $drama, now()->addHours(6));
                            }

                            return $drama;
                        }, $validItems);

                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        $this->hasMore = !$isSearch && (isset($rawJson['hasNext']) ? (bool)$rawJson['hasNext'] : count($validItems) >= 4);
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'reelbuzz') {
            $base = 'https://reelbuzz.goodbos.online/api';
            $limit = 12;
            $offset = $this->offset * $limit;

            if ($isSearch) {
                $url = "{$base}/search?q=" . urlencode($this->search) . "&code={$apiKey}";
            } else {
                $url = "{$base}/dramas?offset={$offset}&limit={$limit}&code={$apiKey}";
            }

            try {
                $response = Http::withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    'timeout' => 15,
                ])->get($url);

                if ($response->successful()) {
                    $json = $response->json() ?? [];
                    $items = $isSearch ? ($json['results'] ?? []) : ($json['dramas'] ?? []);

                    if (is_array($items) && count($items) > 0) {
                        $mapped = array_map(function ($item) {
                            $dramaId = (string) ($item['drama_id'] ?? $item['id'] ?? '');
                            $drama = [
                                'id' => $dramaId,
                                'name' => $item['name'] ?? '',
                                'cover' => $item['cover_url'] ?? $item['cover'] ?? null,
                                'episodes' => isset($item['episodes']) ? (int) $item['episodes'] : null,
                                'intro' => $item['description'] ?? $item['cat'] ?? '',
                                'is_hot' => false,
                                'is_new' => false,
                            ];

                            if ($drama['id'] !== '') {
                                Cache::put('reelbuzz_drama_' . $drama['id'], $drama, now()->addHours(6));
                            }

                            return $drama;
                        }, $items);

                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        $this->hasMore = !$isSearch && count($items) >= $limit;
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'goodshort') {
            $page = $this->offset + 1;
            // Map 'id' internal ke 'in' di API Goodshort sesuai data URL
            $apiLang = $this->lang === 'id' ? 'in' : $this->lang;

            if ($isSearch) {
                // search drama GET URL = https://goodshort.goodbos.online/search?lang=in&q=cinta%20x&page=1&size=15&code=5193CD21848193E43FC399BA4D73BB13
                $url = "https://goodshort.goodbos.online/search?lang={$apiLang}&q=" . urlencode($this->search) . "&page={$page}&size=15&code={$apiKey}";
            } else {
                // Drama Home GET URL = https://goodshort.goodbos.online/home?lang=in&channel=-1&page=1&size=20
                $url = "https://goodshort.goodbos.online/home?lang={$apiLang}&channel=-1&page={$page}&size=20";
            }

            try {
                $response = Http::timeout(15)->get($url);

                if ($response->successful()) {
                    if ($isSearch) {
                        $records = $response->json('data.searchResult.records') ?: [];
                        $pages = (int) ($response->json('data.searchResult.pages') ?: 1);
                    } else {
                        // records dari banner list / items. Home Goodshort mengembalikan daftar records berisi channel/column dengan items didalamnya.
                        $rawRecords = $response->json('data.records') ?: [];
                        $records = [];
                        foreach ($rawRecords as $rec) {
                            if (!empty($rec['items'])) {
                                foreach ($rec['items'] as $item) {
                                    // Cegah duplikat id buku
                                    $bookId = (string) ($item['bookId'] ?? '');
                                    if ($bookId !== '' && !collect($records)->contains('bookId', $bookId)) {
                                        $records[] = $item;
                                    }
                                }
                            }
                        }
                        $pages = (int) ($response->json('data.pages') ?: 15);
                    }

                    if (count($records) > 0) {
                        $mapped = array_map(function (array $item): array {
                            $labels = $item['labels'] ?? [];
                            $drama = [
                                'id' => (string) ($item['bookId'] ?? ''),
                                'name' => (string) ($item['bookName'] ?? ''),
                                'cover' => $item['cover'] ?? $item['image'] ?? null,
                                'episodes' => isset($item['chapterCount']) ? (int) $item['chapterCount'] : null,
                                'intro' => $item['introduction'] ?? (is_array($labels) ? implode(', ', $labels) : ''),
                                'is_hot' => false,
                                'is_new' => false,
                            ];

                            if ($drama['id'] !== '') {
                                Cache::put(
                                    'goodshort_drama_' . $drama['id'],
                                    $drama,
                                    now()->addHours(6),
                                );
                            }

                            return $drama;
                        }, $records);

                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        $this->hasMore = $page < $pages;
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Throwable $exception) {
                $this->hasMore = false;
            }

            return;
        }

        if ($this->slugVal === 'raptdrama') {
            $page = $this->offset + 1;
            if ($isSearch) {
                $url = "https://raptdrama.goodbos.online/api/raptdrama/search?query=" . urlencode($this->search) . "&page={$page}";
            } else {
                $url = "https://raptdrama.goodbos.online/api/raptdrama/home";
            }

            try {
                $response = Http::timeout(15)->withHeaders(['x-token' => $apiKey])->get($url);

                if (!$response->successful()) {
                    $this->hasMore = false;
                    return;
                }

                $raw = $response->json('data') ?? [];
                if ($isSearch) {
                    $items = $raw['items'] ?? (is_array($raw) ? $raw : []);
                } else {
                    $tabKey = in_array($this->tab, ['trending', 'new_releases', 'featured', 'recommend'], true) ? $this->tab : 'trending';
                    $items = $raw[$tabKey] ?? ($raw['trending'] ?? []);
                    if (!empty($items) && isset($items[0]['video'])) {
                        $items = array_map(fn($it) => $it['video'] ?? $it, $items);
                    }
                }

                $mapped = array_map(function (array $item): array {
                    $classify = is_array($item['classify'] ?? null)
                        ? implode(', ', $item['classify'])
                        : '';

                    $description = html_entity_decode(
                        strip_tags((string) ($item['desc'] ?? '')),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    );

                    $drama = [
                        'id' => (string) ($item['id'] ?? ''),
                        'name' => (string) ($item['title'] ?? ''),
                        'cover' => $item['image'] ?? null,
                        'episodes' => isset($item['tstype']) ? (int) $item['tstype'] : (isset($item['total_episodes']) ? (int) $item['total_episodes'] : null),
                        'intro' => $description !== '' ? $description : $classify,
                        'is_hot' => ($item['score'] ?? '') >= 9.0,
                        'is_new' => false,
                    ];

                    if ($drama['id'] !== '') {
                        Cache::put(
                            'raptdrama_drama_' . $drama['id'],
                            $drama,
                            now()->addHours(6),
                        );
                    }

                    return $drama;
                }, $items);

                $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                $this->dramas = array_merge($this->dramas, $valid);
                $this->offset++;
                $this->hasMore = false;
            } catch (\Throwable $exception) {
                $this->hasMore = false;
            }

            return;
        }
        
        if ($this->slugVal === 'pinedrama') {
            $code = "5193CD21848193E43FC399BA4D73BB13";
            $page = $this->offset + 1;
            
            if ($isSearch) {
                $url = "https://pinedrama.goodbos.online/search?code={$code}&q=" . urlencode($this->search) . "&count=20&page={$page}";
            } else {
                $url = "https://pinedrama.goodbos.online/home?code={$code}&scene=1&count=15&page={$page}";
            }

            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $items = $isSearch ? $response->json('results') : $response->json('collections');
                    if (is_array($items) && count($items) > 0) {
                        $mapped = array_map(function ($item) {
                            $d = [
                                'id' => (string) $item['collection_id'],
                                'name' => (string) $item['title'],
                                'cover' => $item['cover'],
                                'episodes' => (int) ($item['total_episodes'] ?? 30),
                                'intro' => $item['description'] ?? implode(', ', $item['tags'] ?? []),
                                'is_hot' => !empty($item['label_hot']),
                                'is_new' => !empty($item['label_new']),
                            ];
                            Cache::put('pinedrama_drama_' . $d['id'], $d, now()->addHours(6));
                            return $d;
                        }, $items);
                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        $this->hasMore = (bool)$response->json('has_more');
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'netshort') {
            $page = $this->offset + 1;

            if ($isSearch) {
                $searchUrl = "https://netshort.goodbos.online/api/search?lang=in&q=" . urlencode($this->search) . "&page={$page}";

                try {
                    $response = Http::timeout(10)->get($searchUrl);
                    if ($response->successful()) {
                        $data = $response->json('data') ?? [];
                        $items = $data['searchCodeSearchResult'] ?? [];
                        $total = (int) ($data['total'] ?? 0);

                        if (is_array($items) && count($items) > 0) {
                            $mapped = array_map(function ($item) {
                                $labels = $item['labelNameList'] ?? [];
                                $cleanLabels = is_array($labels)
                                    ? array_map(fn ($l) => trim(strip_tags((string) $l)), $labels)
                                    : [];
                                return [
                                    'id' => (string) ($item['shortPlayId'] ?? ''),
                                    // shortPlayName mengandung tag <em> highlight, bersihkan.
                                    'name' => trim(strip_tags((string) ($item['shortPlayName'] ?? ''))),
                                    'cover' => $item['shortPlayCover'] ?? null,
                                    'episodes' => null,
                                    'intro' => implode(', ', $cleanLabels),
                                    'is_hot' => false,
                                    'is_new' => false,
                                ];
                            }, $items);
                            $this->dramas = array_merge($this->dramas, $mapped);
                            $this->offset++;
                            // API mengembalikan 20 hasil per halaman.
                            $this->hasMore = ($this->offset * 20) < $total;
                        } else {
                            $this->hasMore = false;
                        }
                    } else {
                        $this->hasMore = false;
                    }
                } catch (\Exception $e) {
                    $this->hasMore = false;
                }
                return;
            }

            if ($this->tab && $this->tab !== 'all') {
                $catUrl = "https://netshort.goodbos.online/api/list?category=" . urlencode($this->tab) . "&lang=in&page={$page}";
                try {
                    $response = Http::timeout(10)->get($catUrl);
                    if ($response->successful()) {
                        $items = $response->json('data.dataList') ?? [];
                        if (is_array($items) && count($items) > 0) {
                            $mapped = array_map(function ($item) {
                                $labels = $item['labelArray'] ?? [];
                                return [
                                    'id' => (string) ($item['shortPlayLibraryId'] ?? $item['shortPlayId'] ?? ''),
                                    'name' => trim(strip_tags((string) ($item['shortPlayName'] ?? ''))),
                                    'cover' => $item['shortPlayCover'] ?? null,
                                    'episodes' => null,
                                    'intro' => is_array($labels) ? implode(', ', $labels) : '',
                                    'is_hot' => ($item['scriptName'] ?? '') === 'Populer',
                                    'is_new' => !empty($item['isNewLabel']),
                                ];
                            }, $items);
                            $this->dramas = array_merge($this->dramas, $mapped);
                            $this->offset++;
                            $this->hasMore = count($mapped) >= 15;
                        } else {
                            $this->hasMore = false;
                        }
                    } else {
                        $this->hasMore = false;
                    }
                } catch (\Exception $e) {
                    $this->hasMore = false;
                }
                return;
            }

            $url = "https://netshort.goodbos.online/api/home/{$page}?lang=in";

            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $data = $response->json('data') ?? [];
                    $items = $data['contentInfos'] ?? [];
                    $maxOffset = (int) ($data['maxOffset'] ?? 20);

                    if (is_array($items) && count($items) > 0) {
                        $mapped = array_map(function ($item) {
                            $labels = $item['labelArray'] ?? [];
                            return [
                                'id' => (string) ($item['shortPlayId'] ?? ''),
                                'name' => $item['shortPlayName'] ?? '',
                                'cover' => $item['shortPlayCover'] ?? null,
                                'episodes' => null,
                                'intro' => is_array($labels) ? implode(', ', $labels) : '',
                                'is_hot' => false,
                                'is_new' => !empty($item['isNewLabel']),
                            ];
                        }, $items);
                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        $this->hasMore = $this->offset < $maxOffset;
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'shortmax') {
            $page = $this->offset + 1;

            if ($isSearch) {
                // Shortmax tidak menyediakan endpoint search publik, disable pencarian.
                $this->hasMore = false;
                return;
            }

            $allowedTabs = ['popular', 'new', 'hot', 'vip'];
            $tab = in_array($this->tab, $allowedTabs, true) ? $this->tab : 'popular';

            $url = "https://shortmax.goodbos.online/api/v1/{$tab}?lang=id&page={$page}&code=" . urlencode($apiKey);

            try {
                // Shortmax API kadang lambat merespons (upstream cold start), pakai timeout lebih longgar.
                $response = Http::timeout(20)->get($url);
                if ($response->successful()) {
                    $items = $response->json('data') ?? [];

                    if (is_array($items) && count($items) > 0) {
                        $mapped = array_map(function ($item) {
                            $tags = $item['tags'] ?? [];
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

                            $intro = !empty($item['summary'])
                                ? $item['summary']
                                : implode(', ', $tagNames);

                            return [
                                // Identifier utama shortmax adalah `code` (dipakai di URL detail/alleps).
                                'id' => (string) ($item['code'] ?? $item['id'] ?? ''),
                                'name' => $item['name'] ?? '',
                                'cover' => $item['cover'] ?? null,
                                'episodes' => isset($item['episodes']) ? (int) $item['episodes'] : null,
                                'intro' => $intro,
                                'is_hot' => false,
                                'is_new' => false,
                            ];
                        }, $items);
                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                        // API tidak memberikan total; anggap ada halaman berikutnya selama respons tidak kosong.
                        $this->hasMore = count($items) > 0;
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'melolo') {
            $baseUrl = 'https://melolo.goodbos.online/api';
            if ($isSearch) {
                $url = "{$baseUrl}/search?lang=id&q=" . urlencode($this->search) . "&offset={$this->offset}&code={$apiKey}";
            } else {
                $url = "{$baseUrl}/home?lang=id&offset={$this->offset}&code={$apiKey}";
            }

            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $data = $response->json('data');
                    if (is_array($data) && count($data) > 0) {
                        $mapped = array_map(function ($d) {
                            $cover = $d['cover'] ?? '';
                            if (preg_match('/novel-images-[a-z]+(?:%2F|\/)([0-9a-f]{32})/i', $cover, $matches)) {
                                $d['cover'] = "https://p16-novel-sg.ibyteimg.com/img/novel-images-sg/{$matches[1]}~tplv-resize:570:810.jpg";
                            }
                            return $d;
                        }, $data);
                        $this->dramas = array_merge($this->dramas, $mapped);
                        $this->offset++;
                    } else {
                        $this->hasMore = false;
                    }
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'reelshort') {
            $page = $this->offset + 1;
            if ($isSearch) {
                $url = "https://reelshort.goodbos.online/search?q=" . urlencode($this->search) . "&code={$apiKey}&lang=in";
            } elseif ($page > 1) {
                $url = "https://reelshort.goodbos.online/shelf/2009269?page={$page}&limit=24&lang=in&code={$apiKey}";
            } else {
                $tabName = $this->tab ?: 'POPULER';
                $url = "https://reelshort.goodbos.online/home?code={$apiKey}&lang=in&tab=" . urlencode($tabName);
            }

            try {
                $response = Http::timeout(12)->get($url);
                if ($response->successful()) {
                    $books = $response->json('books') ?: $response->json('data') ?: [];
                    $mapped = array_map(function ($b) {
                        return [
                            'id' => (string) ($b['id'] ?? ''),
                            'name' => $b['title'] ?? 'ReelShort Drama',
                            'cover' => $b['pic'] ?? $b['cover'] ?? '',
                            'intro' => $b['desc'] ?? '',
                            'episodes' => $b['chapters'] ?? null,
                            'is_hot' => !empty($b['is_hot']),
                            'is_new' => !empty($b['is_new']),
                        ];
                    }, $books);

                    $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                    $this->dramas = array_merge($this->dramas, $valid);
                    $this->offset++;
                    $this->hasMore = count($valid) >= 12;
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'vibeshort' || $this->slugVal === 'storyreel') {
            $host = $this->slugVal === 'vibeshort' ? 'vibeshort.goodbos.online' : 'storyreel.goodbos.online';
            if ($isSearch) {
                $url = "https://{$host}/api/v1/search?q=" . urlencode($this->search) . "&lang=id";
            } else {
                $tabId = $this->tab ?: '337';
                $url = "https://{$host}/api/v1/home?lang=id&tab=" . urlencode($tabId);
            }

            try {
                $response = Http::timeout(12)->withHeaders(['x-token' => $apiKey])->get($url);
                if ($response->successful()) {
                    $items = $response->json('data') ?: [];
                    if (isset($items['items'])) {
                        $items = $items['items'];
                    }
                    $mapped = array_map(function ($b) {
                        return [
                            'id' => (string) ($b['id'] ?? $b['bookId'] ?? ''),
                            'name' => $b['name'] ?? $b['bookName'] ?? 'Drama',
                            'cover' => $b['cover'] ?? '',
                            'intro' => $b['summary'] ?? '',
                            'episodes' => $b['episodes'] ?? null,
                            'is_hot' => false,
                            'is_new' => false,
                        ];
                    }, $items);

                    $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                    $this->dramas = array_merge($this->dramas, $valid);
                    $this->offset++;
                    $this->hasMore = false;
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'moboreels') {
            $lang = $this->lang ?: 'in';
            if ($isSearch) {
                $url = "https://moboreels.goodbos.online/api/v1/search?q=" . urlencode($this->search) . "&lang={$lang}&code={$apiKey}";
            } else {
                $url = "https://moboreels.goodbos.online/api/v1/series?lang={$lang}&code={$apiKey}";
            }

            try {
                $response = Http::timeout(12)->get($url);
                if ($response->successful()) {
                    $sList = $response->json('data.seriesList') ?: $response->json('data') ?: [];
                    $mapped = array_map(function ($b) {
                        return [
                            'id' => (string) ($b['seriesId'] ?? $b['id'] ?? ''),
                            'name' => $b['seriesName'] ?? $b['title'] ?? 'MoboReels Drama',
                            'cover' => $b['coverUrl'] ?? $b['cover'] ?? '',
                            'intro' => $b['summary'] ?? $b['introduction'] ?? '',
                            'episodes' => $b['totalEpisodes'] ?? null,
                            'is_hot' => !empty($b['heat']),
                            'is_new' => false,
                        ];
                    }, $sList);

                    $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                    $this->dramas = array_merge($this->dramas, $valid);
                    $this->offset++;
                    $this->hasMore = false;
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if ($this->slugVal === 'stardusttv') {
            $page = $this->offset + 1;
            if ($isSearch) {
                $url = "https://stardusttv.goodbos.online/v1/find?q=" . urlencode($this->search) . "&lang=id&code={$apiKey}";
            } else {
                $url = "https://stardusttv.goodbos.online/v1/list?page={$page}&lang=id&code={$apiKey}";
            }

            try {
                $response = Http::timeout(12)->get($url);
                if ($response->successful()) {
                    $data = $response->json('data') ?: [];
                    $mapped = array_map(function ($b) {
                        return [
                            'id' => (string) ($b['id'] ?? ''),
                            'slug' => $b['slug'] ?? 'drama',
                            'name' => $b['title'] ?? 'Stardust Drama',
                            'cover' => $b['poster'] ?? $b['cover'] ?? '',
                            'intro' => $b['description'] ?? '',
                            'episodes' => $b['episode'] ?? $b['episodes'] ?? null,
                            'is_hot' => false,
                            'is_new' => false,
                        ];
                    }, $data);

                    $valid = array_values(array_filter($mapped, fn($d) => !empty($d['id'])));
                    $this->dramas = array_merge($this->dramas, $valid);
                    $this->offset++;
                    $this->hasMore = count($valid) >= 20;
                } else {
                    $this->hasMore = false;
                }
            } catch (\Exception $e) {
                $this->hasMore = false;
            }
            return;
        }

        if (empty($this->network['api'])) {
            return;
        }

        $baseUrl = rtrim($this->network['api'], '/');
        if ($isSearch) {
            $url = "{$baseUrl}/search?lang=id&q=" . urlencode($this->search) . "&offset={$this->offset}&code={$apiKey}";
        } else {
            $url = "{$baseUrl}/home?lang=id&offset={$this->offset}&code={$apiKey}";
        }

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $data = $response->json('data');
                if (is_array($data) && count($data) > 0) {
                    $this->dramas = array_merge($this->dramas, $data);
                    $this->offset++;
                } else {
                    $this->hasMore = false;
                }
            } else {
                $this->hasMore = false;
            }
        } catch (\Exception $e) {
            $this->hasMore = false;
        }
    }

    protected function getViewData(): array
    {
        // Riwayat tonton user khusus network ini (untuk slider "Lanjutkan Nonton")
        $history = [];
        if (auth()->check() && !empty($this->slugVal)) {
            $prefix = $this->slugVal . ':';
            $history = auth()->user()->watchHistories()
                ->where('item_type', 'dracin')
                ->where('item_id', 'like', $prefix . '%')
                ->latest('updated_at')
                ->take(12)
                ->get()
                ->map(fn ($h) => [
                    'id' => substr($h->item_id, strlen($prefix)),
                    'title' => $h->title,
                    'cover' => $h->thumbnail,
                    'last_episode' => $h->last_episode,
                    'watched_count' => is_array($h->watched_episodes) ? count($h->watched_episodes) : 0,
                ])
                ->filter(fn ($h) => $h['id'] !== '')
                ->values()
                ->all();
        }

        return [
            'network' => $this->network,
            'history' => $history,
        ];
    }
}
