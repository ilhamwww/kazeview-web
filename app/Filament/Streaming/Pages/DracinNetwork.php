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
        $allowed = ['popular', 'new', 'hot', 'vip'];
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
        $this->network = collect($networks)->first(function ($net) use ($slug) {
            return $net['id'] === $slug;
        });

        if (!$this->network) {
            abort(404, 'Network not found or inactive');
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
            $url = "https://raptdrama.goodbos.online/api/home?page={$page}&lang=en";

            try {
                $response = Http::timeout(15)->get($url);

                if (!$response->successful() || (int) $response->json('code') !== 200) {
                    $this->hasMore = false;

                    return;
                }

                $data = $response->json('data') ?? [];
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];

                if ($isSearch) {
                    $needle = mb_strtolower(trim($this->search));
                    $items = array_values(array_filter(
                        $items,
                        fn (array $item): bool => str_contains(
                            mb_strtolower((string) ($item['title'] ?? '')),
                            $needle,
                        ),
                    ));
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
                        'episodes' => isset($item['tstype']) ? (int) $item['tstype'] : null,
                        'intro' => $description !== '' ? $description : $classify,
                        'is_hot' => false,
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

                $this->dramas = array_merge($this->dramas, $mapped);
                $this->offset++;
                $this->hasMore = !$isSearch && (bool) ($data['hasMore'] ?? false);
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
                            return [
                                'id' => $item['collection_id'],
                                'name' => $item['title'],
                                'cover' => $item['cover'],
                                'episodes' => $item['total_episodes'],
                                'intro' => $item['description'] ?? implode(', ', $item['tags'] ?? []),
                                'is_hot' => !empty($item['label_hot']),
                                'is_new' => !empty($item['label_new']),
                            ];
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
        return [
            'network' => $this->network,
        ];
    }
}
