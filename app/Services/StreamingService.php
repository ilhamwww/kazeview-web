<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\ScraperSetting;
use GuzzleHttp\Client;

class StreamingService
{
    /**
     * Fetch formatted homepage data from IDLIX API with caching.
     */
    public static function getHomepageData(): array
    {
        // Provider utama: Chitogenx (DutaMovie21).
        $chito = ChitogenxService::home();

        if (!empty($chito['heroes']) || !empty($chito['sections'])) {
            return $chito;
        }

        return Cache::remember('idlix_homepage', 1800, function () { // Cache for 30 minutes
            try {
                $data = self::fetchHomepageJson();
            } catch (\Exception $e) {
                Log::error('Failed to fetch IDLIX homepage: ' . $e->getMessage());
                // Fallback to empty structure
                $data = [
                    'heroes' => null,
                    'sections' => []
                ];
            }

            // Merge Rebahin movies & series ke section beranda secara netral
            try {
                $rbMovies = RebahinService::getMovies(1)['data'] ?? [];
                $rbSeries = RebahinService::getSeries(1)['data'] ?? [];

                if (!empty($data['sections'])) {
                    foreach ($data['sections'] as &$sec) {
                        $slug = $sec['slug'] ?? '';
                        if ($slug === 'latest-movies' && !empty($rbMovies)) {
                            $existing = $sec['items'] ?? [];
                            $seen = [];
                            foreach ($existing as $ex) {
                                $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $ex['title'] ?? ''));
                                if ($norm) $seen[$norm] = true;
                            }
                            $merged = $existing;
                            foreach (array_slice($rbMovies, 0, 8) as $rb) {
                                $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $rb['title'] ?? ''));
                                if (!$norm || !isset($seen[$norm])) {
                                    if ($norm) $seen[$norm] = true;
                                    $merged[] = $rb;
                                }
                            }
                            $sec['items'] = $merged;
                        }

                        if ($slug === 'latest-series' && !empty($rbSeries)) {
                            $existing = $sec['items'] ?? [];
                            $seen = [];
                            foreach ($existing as $ex) {
                                $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $ex['title'] ?? ''));
                                if ($norm) $seen[$norm] = true;
                            }
                            $merged = $existing;
                            foreach (array_slice($rbSeries, 0, 8) as $rb) {
                                $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $rb['title'] ?? ''));
                                if (!$norm || !isset($seen[$norm])) {
                                    if ($norm) $seen[$norm] = true;
                                    $merged[] = $rb;
                                }
                            }
                            $sec['items'] = $merged;
                        }
                    }
                }

                // Tambahkan kategori khusus Indonesia
                $rbIndo = RebahinService::getIndonesia(1)['data'] ?? [];
                if (!empty($rbIndo)) {
                    $data['sections'][] = [
                        'id' => 'indonesia',
                        'slug' => 'indonesia',
                        'title' => 'Film & Series Indonesia',
                        'items' => array_slice($rbIndo, 0, 18),
                    ];
                }

                // Ganti/tambahkan kategori Drakor (menggantikan Trending di Singapura)
                try {
                    $kdrama = self::getKoreanDrama(1, 18)['data'] ?? [];
                    if (!empty($kdrama)) {
                        if (!empty($data['sections'])) {
                            $data['sections'] = array_values(array_filter(
                                $data['sections'],
                                fn($s) => ($s['slug'] ?? '') !== 'trending-country'
                            ));
                        }
                        $data['sections'][] = [
                            'id' => 'drakor',
                            'slug' => 'drakor',
                            'title' => 'Drakor',
                            'items' => $kdrama,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Merge Drakor to home failed: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Log::warning('Merge Rebahin to home failed: ' . $e->getMessage());
            }

            return $data;
        });
    }

    /**
     * Search IDLIX API
     */
    public static function search(string $query, int $page = 1, int $limit = 8): array
    {
        $chito = ChitogenxService::search($query, $page, max($limit, 20));

        if (!empty($chito['data'])) {
            return $chito['data'];
        }

        $results = [];
        try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $searchPath = '/api/search?q=' . urlencode($query) . '&page=' . $page . '&limit=' . $limit;
            $url = rtrim($domain, '/') . $searchPath;

            // 1. Coba lewat fetch_cf.py (curl_cffi TLS Impersonate langsung dari VPS ~200ms tanpa NUC!)
            $body = null;
            try {
                $helperPath = app_path('Services/fetch_cf.py');
                if (file_exists($helperPath)) {
                    $cmd = '/usr/bin/python3.10 ' . escapeshellarg($helperPath) . ' ' . escapeshellarg($url);
                    $pyRes = shell_exec($cmd);
                    if (!empty($pyRes) && !str_contains($pyRes, 'Cloudflare') && !str_contains($pyRes, 'Just a moment...')) {
                        $body = (string) $pyRes;
                    }
                }
            } catch (\Throwable $pe) {}

            // 2. Fallback NUC Relay jika tersedia
            if (!$body) {
                try {
                    $nucRes = Http::withOptions([
                        'proxy' => 'socks5h://127.0.0.1:1055',
                        'timeout' => 3,
                    ])->get('http://100.110.54.105:8765/json?path=' . urlencode($searchPath));
                    if ($nucRes->successful() && !str_contains($nucRes->body(), 'Cloudflare') && !str_contains($nucRes->body(), 'Just a moment...')) {
                        $body = $nucRes->body();
                    }
                } catch (\Throwable $ne) {}
            }

            // 2. Fallback ke Direct Guzzle dengan timeout singkat (3 detik)
            if (!$body) {
                $client = new Client([
                    'timeout' => 3,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json, text/plain, */*',
                        'Referer' => $domain,
                        'Origin' => rtrim($domain, '/'),
                    ],
                    'verify' => false,
                ]);

                $response = $client->get($url);
                $body = (string) $response->getBody();

                if (str_contains($body, 'Just a moment...') || str_contains($body, 'Cloudflare')) {
                    throw new \Exception("Cloudflare detected");
                }
            }

            $data = json_decode($body, true);
            
            $items = $data['data'] ?? $data['results'] ?? $data ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $results[] = $mapped;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Search failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/search?q=' . urlencode($query) . '&page=' . $page . '&limit=' . $limit;
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $items = $data['data'] ?? $data['results'] ?? $data ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
            } catch (\Exception $pe) {
                Log::error('Search failed via Puppeteer: ' . $pe->getMessage());
            }
        }

        // Merge Rebahin search results secara transparan
        try {
            $rbSearch = RebahinService::search($query);
            if (!empty($rbSearch)) {
                $seen = [];
                foreach ($results as $r) {
                    $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $r['title'] ?? ''));
                    if ($norm) $seen[$norm] = true;
                }
                foreach ($rbSearch as $rb) {
                    $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $rb['title'] ?? ''));
                    if (!$norm || !isset($seen[$norm])) {
                        if ($norm) $seen[$norm] = true;
                        $results[] = $rb;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Merge Rebahin search failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Get IDLIX Movie List with Pagination
     */
    public static function getMovies(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $chito = ChitogenxService::listing('movie', $page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        $cacheKey = "idlix_movies_p{$page}_l{$limit}_s{$sort}";
        return Cache::remember($cacheKey, 1800, function () use ($page, $limit, $sort) {
            try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . '/api/movies?page=' . $page . '&limit=' . $limit . '&sort=' . $sort;

            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Referer' => $domain,
                    'Origin' => rtrim($domain, '/'),
                ],
                'verify' => false,
            ]);

            $response = $client->get($url);
            $body = (string) $response->getBody();

            if (str_contains($body, 'Just a moment...') || str_contains($body, 'Cloudflare')) {
                throw new \Exception("Cloudflare detected");
            }

            $data = json_decode($body, true);
            $results = [];
            
            $items = $data['data'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $results[] = $mapped;
                    }
                }
            }
            $results = self::mergeRebahinMedia($results, 'movie', $page);
            return [
                'data' => $results,
                'pagination' => $data['pagination'] ?? [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($results),
                    'totalPages' => 1
                ]
            ];
        } catch (\Exception $e) {
            Log::warning('Get movies failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/movies?page=' . $page . '&limit=' . $limit . '&sort=' . $sort;
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $results = [];
                $items = $data['data'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
                $results = self::mergeRebahinMedia($results, 'movie', $page);
                return [
                    'data' => $results,
                    'pagination' => $data['pagination'] ?? [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($results),
                        'totalPages' => 1
                    ]
                ];
            } catch (\Exception $pe) {
                Log::error('Get movies failed via Puppeteer: ' . $pe->getMessage());
                $results = self::mergeRebahinMedia([], 'movie', $page);
                return [
                    'data' => $results,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($results),
                        'totalPages' => count($results) > 0 ? 1 : 0
                    ]
                ];
            }
        }
        });
    }

    /**
     * Get IDLIX Trending Movies
     */
    public static function getTrendingMovies(int $limit = 5, string $period = '7d'): array
    {
        $chito = ChitogenxService::trending('movie', $limit);

        if (!empty($chito)) {
            return $chito;
        }

        $cacheKey = "idlix_trending_movies_l{$limit}_p{$period}";
        return Cache::remember($cacheKey, 1800, function () use ($limit, $period) {
            try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . '/api/trending/top?limit=' . $limit . '&period=' . $period . '&contentType=movie';

            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Referer' => $domain,
                    'Origin' => rtrim($domain, '/'),
                ],
                'verify' => false,
            ]);

            $response = $client->get($url);
            $body = (string) $response->getBody();

            if (str_contains($body, 'Just a moment...') || str_contains($body, 'Cloudflare')) {
                throw new \Exception("Cloudflare detected");
            }

            $data = json_decode($body, true);
            $results = [];
            
            $items = $data['data'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $results[] = $mapped;
                    }
                }
            }
            return $results;
        } catch (\Exception $e) {
            Log::warning('Get trending movies failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/trending/top?limit=' . $limit . '&period=' . $period . '&contentType=movie';
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $results = [];
                $items = $data['data'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
                return $results;
            } catch (\Exception $pe) {
                Log::error('Get trending movies failed via Puppeteer: ' . $pe->getMessage());
                return [];
            }
        }
        });
    }

    /**
     * Get IDLIX TV Series List with Pagination
     */
    public static function getSeries(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $chito = ChitogenxService::listing('series', $page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        $cacheKey = "idlix_series_list_p{$page}_l{$limit}_s{$sort}";
        return Cache::remember($cacheKey, 1800, function () use ($page, $limit, $sort) {
            try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . '/api/series?page=' . $page . '&limit=' . $limit . '&sort=' . $sort;

            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Referer' => $domain,
                    'Origin' => rtrim($domain, '/'),
                ],
                'verify' => false,
            ]);

            $response = $client->get($url);
            $body = (string) $response->getBody();

            if (str_contains($body, 'Just a moment...') || str_contains($body, 'Cloudflare')) {
                throw new \Exception("Cloudflare detected");
            }

            $data = json_decode($body, true);
            $results = [];
            
            $items = $data['data'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $results[] = $mapped;
                    }
                }
            }
            $results = self::mergeRebahinMedia($results, 'series', $page);
            return [
                'data' => $results,
                'pagination' => $data['pagination'] ?? [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($results),
                    'totalPages' => 1
                ]
            ];
        } catch (\Exception $e) {
            Log::warning('Get series failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/series?page=' . $page . '&limit=' . $limit . '&sort=' . $sort;
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $results = [];
                $items = $data['data'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
                $results = self::mergeRebahinMedia($results, 'series', $page);
                return [
                    'data' => $results,
                    'pagination' => $data['pagination'] ?? [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($results),
                        'totalPages' => 1
                    ]
                ];
            } catch (\Exception $pe) {
                Log::error('Get series failed via Puppeteer: ' . $pe->getMessage());
                $results = self::mergeRebahinMedia([], 'series', $page);
                return [
                    'data' => $results,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($results),
                        'totalPages' => count($results) > 0 ? 1 : 0
                    ]
                ];
            }
        }
        });
    }

    /**
     * Helper untuk merge media Rebahin secara netral & deduplikasi
     */
    private static function mergeRebahinMedia(array $results, string $type, int $page): array
    {
        try {
            $rbRes = $type === 'series' 
                ? RebahinService::getSeries($page) 
                : RebahinService::getMovies($page);

            $rbItems = $rbRes['data'] ?? [];
            if (!empty($rbItems)) {
                $seen = [];
                foreach ($results as $r) {
                    $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $r['title'] ?? ''));
                    if ($norm) $seen[$norm] = true;
                }
                foreach ($rbItems as $rb) {
                    $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $rb['title'] ?? ''));
                    if (!$norm || !isset($seen[$norm])) {
                        if ($norm) $seen[$norm] = true;
                        $results[] = $rb;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("mergeRebahinMedia {$type} failed: " . $e->getMessage());
        }
        return $results;
    }

    /**
     * Fetch Korean Drama list from IDLIX (/korean-drama -> /api/browse?genre=drama&country=KR&language=ko)
     */
    public static function getKoreanDrama(int $page = 1, int $limit = 36, string $sort = 'latest'): array
    {
        $chito = ChitogenxService::koreanDrama($page, $limit);

        if (!empty($chito['data'])) {
            return $chito;
        }

        $fetchLimit = ($page === 1 && $limit < 36) ? 36 : $limit;
        $baseCacheKey = "idlix_korean_drama_p{$page}_l{$fetchLimit}_s{$sort}";

        $result = Cache::remember($baseCacheKey, 1800, function () use ($page, $fetchLimit, $sort) {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . "/api/browse?page={$page}&limit={$fetchLimit}&sort={$sort}&genre=drama&country=KR&language=ko";

            try {
                $scriptPath = base_path('scraper_korean_drama.js');
                if (file_exists($scriptPath)) {
                    $cmd = 'node --dns-result-order=ipv4first ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg((string)$page) . ' ' . escapeshellarg((string)$fetchLimit) . ' ' . escapeshellarg($sort) . ' ' . escapeshellarg($domain) . ' 2>&1';
                    $output = [];
                    $returnVar = 0;
                    exec($cmd, $output, $returnVar);
                    if ($returnVar === 0) {
                        $json = implode("\n", $output);
                        $data = json_decode($json, true);
                        if (!empty($data) && isset($data['data'])) {
                            $results = [];
                            foreach ($data['data'] as $item) {
                                $mapped = self::mapMediaItem($item);
                                if ($mapped) {
                                    $results[] = $mapped;
                                }
                            }
                            return [
                                'data' => $results,
                                'pagination' => $data['pagination'] ?? [
                                    'page' => $page,
                                    'limit' => $fetchLimit,
                                    'total' => count($results),
                                    'totalPages' => $data['pagination']['totalPages'] ?? 1,
                                ]
                            ];
                        }
                    }
                }

                // Fallback to fetchJsonWithPuppeteer
                $json = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($json, true);
                $results = [];
                if (!empty($data['data'])) {
                    foreach ($data['data'] as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
                return [
                    'data' => $results,
                    'pagination' => $data['pagination'] ?? [
                        'page' => $page,
                        'limit' => $fetchLimit,
                        'total' => count($results),
                        'totalPages' => $data['pagination']['totalPages'] ?? 1,
                    ]
                ];
            } catch (\Exception $e) {
                Log::error('getKoreanDrama failed: ' . $e->getMessage());
                return [
                    'data' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $fetchLimit,
                        'total' => 0,
                        'totalPages' => 1,
                    ]
                ];
            }
        });

        if ($limit < $fetchLimit && !empty($result['data'])) {
            return [
                'data' => array_slice($result['data'], 0, $limit),
                'pagination' => array_merge($result['pagination'] ?? [], ['limit' => $limit]),
            ];
        }

        return $result;
    }

    /**
     * Get IDLIX Trending TV Series
     */
    public static function getTrendingSeries(int $limit = 5, string $period = '7d'): array
    {
        $chito = ChitogenxService::trending('series', $limit);

        if (!empty($chito)) {
            return $chito;
        }

        $cacheKey = "idlix_trending_series_l{$limit}_p{$period}";
        return Cache::remember($cacheKey, 1800, function () use ($limit, $period) {
            try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . '/api/trending/top?limit=' . $limit . '&period=' . $period . '&contentType=tv_series';

            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Referer' => $domain,
                    'Origin' => rtrim($domain, '/'),
                ],
                'verify' => false,
            ]);

            $response = $client->get($url);
            $body = (string) $response->getBody();

            if (str_contains($body, 'Just a moment...') || str_contains($body, 'Cloudflare')) {
                throw new \Exception("Cloudflare detected");
            }

            $data = json_decode($body, true);
            $results = [];
            
            $items = $data['data'] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $results[] = $mapped;
                    }
                }
            }
            return $results;
        } catch (\Exception $e) {
            Log::warning('Get trending series failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/trending/top?limit=' . $limit . '&period=' . $period . '&contentType=tv_series';
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $results = [];
                $items = $data['data'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $results[] = $mapped;
                        }
                    }
                }
                return $results;
            } catch (\Exception $pe) {
                Log::error('Get trending series failed via Puppeteer: ' . $pe->getMessage());
                return [];
            }
        }
        });
    }

    /**
     * Get IDLIX Collections List
     */
    public static function getCollections(): array
    {
        $chito = ChitogenxService::collections();

        if (!empty($chito)) {
            return $chito;
        }

        return Cache::remember('idlix_collections_list', 86400, function () {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/collections';

                try {
                    $client = new Client([
                        'timeout' => 10,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                            'Accept' => 'application/json, text/plain, */*',
                        ]
                    ]);
                    $res = $client->get($url);
                    if ($res->getStatusCode() === 200) {
                        $data = json_decode($res->getBody()->getContents(), true);
                        if (!empty($data['collections'])) {
                            return self::formatCollectionsList($data['collections']);
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to Puppeteer
                }

                $jsonText = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($jsonText, true);
                if (!empty($data['collections'])) {
                    return self::formatCollectionsList($data['collections']);
                }

                return [];
            } catch (\Exception $e) {
                Log::error('Error fetching collections list: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Format collections list with full TMDB image paths and year ranges
     */
    private static function formatCollectionsList(array $rawCollections): array
    {
        $result = [];
        foreach ($rawCollections as $col) {
            $firstYear = !empty($col['firstReleaseDate']) ? substr($col['firstReleaseDate'], 0, 4) : '';
            $lastYear = !empty($col['lastReleaseDate']) ? substr($col['lastReleaseDate'], 0, 4) : '';
            
            $yearRange = '';
            if ($firstYear && $lastYear) {
                $yearRange = ($firstYear === $lastYear) ? $firstYear : "{$firstYear} · {$lastYear}";
            } elseif ($firstYear) {
                $yearRange = $firstYear;
            } elseif ($lastYear) {
                $yearRange = $lastYear;
            }

            $result[] = [
                'id' => $col['id'] ?? '',
                'slug' => $col['slug'] ?? '',
                'title' => $col['title'] ?? '',
                'description' => $col['description'] ?? '',
                'kind' => $col['kind'] ?? 'franchise',
                'poster' => !empty($col['posterPath']) ? 'https://image.tmdb.org/t/p/w500' . $col['posterPath'] : null,
                'banner' => !empty($col['backdropPath']) ? 'https://image.tmdb.org/t/p/w1280' . $col['backdropPath'] : null,
                'itemCount' => (int)($col['itemCount'] ?? 0),
                'childCount' => (int)($col['childCount'] ?? 0),
                'yearRange' => $yearRange,
                'firstReleaseDate' => $col['firstReleaseDate'] ?? null,
                'lastReleaseDate' => $col['lastReleaseDate'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Get IDLIX Collection Detail by slug
     */
    public static function getCollectionDetail(string $slug): ?array
    {
        $chito = ChitogenxService::collectionDetail($slug);

        if ($chito) {
            return $chito;
        }

        return Cache::remember('idlix_collection_detail_' . $slug, 86400, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/collections/' . $slug;

                try {
                    $client = new Client([
                        'timeout' => 10,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                            'Accept' => 'application/json, text/plain, */*',
                        ]
                    ]);
                    $res = $client->get($url);
                    if ($res->getStatusCode() === 200) {
                        $data = json_decode($res->getBody()->getContents(), true);
                        if (!empty($data['collection'])) {
                            return self::formatCollectionDetailData($data);
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to Puppeteer
                }

                $jsonText = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($jsonText, true);
                if (!empty($data['collection'])) {
                    return self::formatCollectionDetailData($data);
                }

                return null;
            } catch (\Exception $e) {
                Log::error("Error fetching collection detail for {$slug}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Format raw collection detail data
     */
    private static function formatCollectionDetailData(array $raw): array
    {
        $col = $raw['collection'] ?? [];
        $firstYear = !empty($col['firstReleaseDate']) ? substr($col['firstReleaseDate'], 0, 4) : '';
        $lastYear = !empty($col['lastReleaseDate']) ? substr($col['lastReleaseDate'], 0, 4) : '';
        
        $yearRange = '';
        if ($firstYear && $lastYear) {
            $yearRange = ($firstYear === $lastYear) ? $firstYear : "{$firstYear} · {$lastYear}";
        } elseif ($firstYear) {
            $yearRange = $firstYear;
        }

        $formattedCol = [
            'id' => $col['id'] ?? '',
            'slug' => $col['slug'] ?? '',
            'title' => $col['title'] ?? '',
            'description' => $col['description'] ?? '',
            'kind' => $col['kind'] ?? 'franchise',
            'poster' => !empty($col['posterPath']) ? 'https://image.tmdb.org/t/p/w500' . $col['posterPath'] : null,
            'banner' => !empty($col['backdropPath']) ? 'https://image.tmdb.org/t/p/w1280' . $col['backdropPath'] : null,
            'itemCount' => (int)($col['itemCount'] ?? 0),
            'childCount' => (int)($col['childCount'] ?? 0),
            'yearRange' => $yearRange,
            'firstReleaseDate' => $col['firstReleaseDate'] ?? null,
            'lastReleaseDate' => $col['lastReleaseDate'] ?? null,
        ];

        $sections = [];
        foreach ($raw['sections'] ?? [] as $sec) {
            $items = [];
            foreach ($sec['items'] ?? [] as $item) {
                $isSeries = isset($item['contentType']) 
                    ? ($item['contentType'] === 'tv_series' || $item['contentType'] === 'tv')
                    : (!empty($item['numberOfSeasons']));
                
                $year = !empty($item['releaseDate']) ? substr($item['releaseDate'], 0, 4) : '';
                $rating = !empty($item['voteAverage']) && is_numeric($item['voteAverage']) ? number_format((float)$item['voteAverage'], 1) : null;
                $duration = !empty($item['runtime']) ? $item['runtime'] . 'm' : null;

                $detailUrl = '#';
                if (!empty($item['slug'])) {
                    $detailUrl = $isSeries 
                        ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                        : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
                }

                $items[] = [
                    'id' => $item['id'] ?? '',
                    'title' => $item['title'] ?? '',
                    'slug' => $item['slug'] ?? '',
                    'thumbnail' => !empty($item['posterPath']) ? 'https://image.tmdb.org/t/p/w500' . $item['posterPath'] : null,
                    'banner' => !empty($item['backdropPath']) ? 'https://image.tmdb.org/t/p/w1280' . $item['backdropPath'] : null,
                    'year' => $year,
                    'releaseDate' => $item['releaseDate'] ?? null,
                    'rating' => $rating,
                    'quality' => $item['quality'] ?? null,
                    'duration' => $duration,
                    'type' => $isSeries ? 'tv_series' : 'movie',
                    'url' => $detailUrl,
                ];
            }

            $sections[] = [
                'id' => $sec['id'] ?? '',
                'title' => $sec['title'] ?? '',
                'description' => $sec['description'] ?? '',
                'kind' => $sec['kind'] ?? '',
                'itemCount' => (int)($sec['itemCount'] ?? count($items)),
                'items' => $items,
            ];
        }

        return [
            'collection' => $formattedCol,
            'breadcrumbs' => $raw['breadcrumbs'] ?? [],
            'sections' => $sections,
        ];
    }

    /**
     * Fetch homepage JSON from IDLIX API, with fallback to Puppeteer if blocked.
     */
    private static function fetchHomepageJson(): array
    {
        $domain = ScraperSetting::getInstance()->idlix_domain;
        $url = rtrim($domain, '/') . '/api/homepage';

        $client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => $domain,
                'Origin' => rtrim($domain, '/'),
            ],
            'verify' => false,
        ]);

        try {
            $response = $client->get($url);
            $body = (string) $response->getBody();

            if (
                str_contains($body, 'Just a moment...') || 
                str_contains($body, 'Attention Required!') || 
                str_contains($body, 'Cloudflare') || 
                str_contains($body, 'enable JavaScript')
            ) {
                throw new \Exception("Cloudflare or anti-bot challenge page detected.");
            }

            $data = json_decode($body, true);
            if (empty($data)) {
                throw new \Exception("Empty or invalid JSON response from API.");
            }

            return self::formatHomepageData($data);
        } catch (\Exception $e) {
            Log::warning('Guzzle failed to fetch IDLIX homepage (' . $e->getMessage() . '). Falling back to Puppeteer.');
            
            try {
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                if (empty($data)) {
                    throw new \Exception("Puppeteer returned empty or invalid JSON: " . substr($body, 0, 200));
                }
                return self::formatHomepageData($data);
            } catch (\Exception $pe) {
                Log::error('Puppeteer also failed to fetch IDLIX homepage: ' . $pe->getMessage());
                throw $pe;
            }
        }
    }

    /**
     * Run Puppeteer json scraper script to fetch raw JSON text.
     */
    private static function fetchJsonWithPuppeteer(string $url): string
    {
        // 1. Coba lewat fetch_cf.py (curl_cffi TLS Chrome Impersonation langsung dari VPS — Super Cepat ~200ms & Bebas NUC!)
        try {
            $helperPath = app_path('Services/fetch_cf.py');
            if (file_exists($helperPath)) {
                $cmd = '/usr/bin/python3.10 ' . escapeshellarg($helperPath) . ' ' . escapeshellarg($url);
                $pyRes = shell_exec($cmd);
                if (!empty($pyRes) && !str_contains($pyRes, 'Just a moment...') && !str_contains($pyRes, 'Attention Required!')) {
                    return (string) $pyRes;
                }
            }
        } catch (\Throwable $e) {}

        // 2. Fallback NUC Resolver jika tersedia
        try {
            $parsed = parse_url($url);
            $path = ($parsed['path'] ?? '') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
            if (!empty($path)) {
                $nucUrl = 'http://100.110.54.105:8765/json?path=' . urlencode($path);
                $res = Http::withOptions([
                    'proxy' => 'socks5h://127.0.0.1:1055',
                    'timeout' => 5,
                ])->get($nucUrl);

                if ($res->successful()) {
                    return $res->body();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('NUC JSON resolver failed: ' . $e->getMessage());
        }

        $scriptPath = base_path('puppeteer_json_scraper.js');
        $executablePath = env('PUPPETEER_EXECUTABLE_PATH', file_exists('/opt/puppeteer-chrome/chrome') ? '/opt/puppeteer-chrome/chrome' : '/usr/bin/google-chrome');
        $cmd = 'node --dns-result-order=ipv4first ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($executablePath ?? '') . ' 2>&1';

        $output = [];
        $returnVar = 0;
        
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Puppeteer JSON script execution failed with exit code {$returnVar}. Output: " . implode("\n", $output));
        }

        return implode("\n", $output);
    }

    /**
     * Format raw IDLIX API response.
     */
    private static function formatHomepageData(array $apiResponse): array
    {
        $heroes = [];
        $sections = [];

        $above = $apiResponse['above'] ?? [];
        $below = $apiResponse['below'] ?? [];
        $allSections = array_merge($above, $below);

        foreach ($allSections as $section) {
            $type = $section['type'] ?? '';
            
            // Skip ads and unwanted sections
            if ($type === 'ads' || ($section['slug'] ?? '') === 'trending-country') {
                continue;
            }

            // Extract banner/featured hero
            if ($type === 'featured') {
                if (!empty($section['data']) && is_array($section['data'])) {
                    foreach ($section['data'] as $item) {
                        $mapped = self::mapMediaItem($item);
                        if ($mapped) {
                            $heroes[] = $mapped;
                        }
                    }
                }
                continue;
            }

            // Extract movie list sections
            if (!empty($section['data']) && is_array($section['data'])) {
                $items = [];
                foreach ($section['data'] as $item) {
                    $mapped = self::mapMediaItem($item);
                    if ($mapped) {
                        $items[] = $mapped;
                    }
                }

                if (!empty($items)) {
                    $sections[] = [
                        'title' => $section['title'] ?? 'Lainnya',
                        'slug' => $section['slug'] ?? 'section',
                        'items' => $items,
                    ];
                }
            }
        }

        return [
            'heroes' => $heroes,
            'sections' => $sections,
        ];
    }

    /**
     * Map individual media item from API format to a consistent internal format.
     */
    private static function mapMediaItem(array $item): ?array
    {
        // Items in sections may wrap contents in a nested "content" property.
        $content = $item['content'] ?? $item;

        $id = $content['id'] ?? $item['id'] ?? null;
        if (!$id) {
            return null;
        }

        $title = $content['title'] ?? $content['name'] ?? $item['title'] ?? 'No Title';
        $contentType = $item['contentType'] ?? $content['contentType'] ?? 'movie';

        // Build TMDB image URLs if paths are set
        $posterPath = $content['posterPath'] ?? null;
        $backdropPath = $content['backdropPath'] ?? null;
        
        $thumbnail = $posterPath ? 'https://image.tmdb.org/t/p/w500' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80';
        $banner = $backdropPath ? 'https://image.tmdb.org/t/p/original' . $backdropPath : ($posterPath ? 'https://image.tmdb.org/t/p/original' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1600&q=80');

        $rating = $content['voteAverage'] ?? '0.0';
        if (is_numeric($rating)) {
            $rating = number_format((float)$rating, 1);
        }

        $year = '';
        if (!empty($content['releaseDate'])) {
            $year = substr($content['releaseDate'], 0, 4);
        } elseif (!empty($content['firstAirDate'])) {
            $year = substr($content['firstAirDate'], 0, 4);
        }

        // Genres mapping
        $genres = [];
        if (!empty($content['genres']) && is_array($content['genres'])) {
            foreach ($content['genres'] as $genre) {
                if (isset($genre['name'])) {
                    $genres[] = $genre['name'];
                }
            }
        }
        $genreStr = implode(', ', $genres);

        return [
            'id' => $id,
            'title' => $title,
            'type' => $contentType,
            'quality' => $content['quality'] ?? 'WEB-DL',
            'year' => $year,
            'rating' => $rating,
            'comments' => $content['commentCount'] ?? 0,
            'duration' => isset($content['runtime']) ? $content['runtime'] . 'm' : '',
            'genre' => $genreStr,
            'thumbnail' => $thumbnail,
            'banner' => $banner,
            // Logo judul transparan dari TMDB (dipakai hero slider sebagai pengganti teks)
            'logo' => !empty($content['logoPath'] ?? $item['logoPath'] ?? null) ? 'https://image.tmdb.org/t/p/w500' . ($content['logoPath'] ?? $item['logoPath']) : null,
            'description' => $content['overview'] ?? '',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', // Default demo url
            'slug' => $content['slug'] ?? '',
        ];
    }

    /**
     * Fetch Series Detail from IDLIX / Rebahin
     */
    public static function getSeriesDetail(string $slug)
    {
        if (str_starts_with($slug, 'mb-')) {
            $mbDetail = NartoMovieBoxService::getSeriesDetail($slug);
            if ($mbDetail) {
                return $mbDetail;
            }
            // Seamless Failover: jika MovieBox series gagal / 404, coba cari padanannya di Rebahin
            try {
                $cleanTitle = str_replace('-', ' ', preg_replace('/^mb-/', '', $slug));
                $rbMatch = RebahinService::getIndonesiaCatalog('series', 1, $cleanTitle);
                if (!empty($rbMatch['data'][0]['raw_slug'])) {
                    $rbDetail = RebahinService::getSeriesDetail('rb-' . $rbMatch['data'][0]['raw_slug']);
                    if ($rbDetail) {
                        $rbDetail['id'] = $slug;
                        $rbDetail['slug'] = $slug;
                        return $rbDetail;
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        }

        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getSeriesDetail($slug);
        }

        // Provider utama: Chitogenx.
        $chito = ChitogenxService::detail($slug);

        if ($chito && ($chito['type'] ?? '') === 'series') {
            return $chito;
        }

        return Cache::remember('idlix_series_' . $slug, 3600, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/series/' . $slug;

                $jsonContent = self::fetchJsonWithPuppeteer($url);
                if ($jsonContent) {
                    $data = json_decode($jsonContent, true);
                    if ($data && is_array($data)) {
                        if (isset($data['posterPath'])) {
                            $data['posterPath'] = 'https://image.tmdb.org/t/p/w500' . $data['posterPath'];
                        }
                        if (isset($data['backdropPath'])) {
                            $data['backdropPath'] = 'https://image.tmdb.org/t/p/w1280' . $data['backdropPath'];
                        }
                        if (isset($data['defaultSeason']['episodes'])) {
                            foreach ($data['defaultSeason']['episodes'] as &$ep) {
                                if (isset($ep['stillPath'])) {
                                    $ep['stillPath'] = 'https://image.tmdb.org/t/p/w500' . $ep['stillPath'];
                                }
                            }
                        }
                        return $data;
                    }
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching series detail: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Fetch Series Season Detail from IDLIX / Rebahin
     */
    public static function getSeriesSeasonDetail(string $slug, int $seasonNumber)
    {
        if (str_starts_with($slug, 'mb-')) {
            $s = NartoMovieBoxService::getSeriesDetail($slug);
            return [
                'season' => $s['defaultSeason'] ?? ['seasonNumber' => $seasonNumber, 'episodes' => []]
            ];
        }

        if (str_starts_with($slug, 'rb-')) {
            $s = RebahinService::getSeriesDetail($slug);
            return [
                'season' => $s['defaultSeason'] ?? ['seasonNumber' => $seasonNumber, 'episodes' => []]
            ];
        }

        if (!str_starts_with($slug, 'mb-')) {
            $chitoSeason = ChitogenxService::season($slug, $seasonNumber);

            if ($chitoSeason && !empty($chitoSeason['season']['episodes'])) {
                return $chitoSeason;
            }
        }

        return Cache::remember('idlix_series_season_' . $slug . '_' . $seasonNumber, 3600, function () use ($slug, $seasonNumber) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/series/' . $slug . '/season/' . $seasonNumber;

                $jsonContent = self::fetchJsonWithPuppeteer($url);
                if ($jsonContent) {
                    $data = json_decode($jsonContent, true);
                    if ($data && is_array($data)) {
                        if (isset($data['season']['episodes'])) {
                            foreach ($data['season']['episodes'] as &$ep) {
                                if (isset($ep['stillPath'])) {
                                    $ep['stillPath'] = 'https://image.tmdb.org/t/p/w500' . $ep['stillPath'];
                                }
                            }
                        }
                        return $data;
                    }
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching series season detail: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Check if a stream URL contains an expiration timestamp that has already passed or is close to expiring.
     */
    public static function isStreamExpired(?array $stream): bool
    {
        if (!$stream || empty($stream['video_url'])) {
            return true;
        }

        $videoUrl = $stream['video_url'];
        if (preg_match('/\.(\d{10})-/', $videoUrl, $matches)) {
            $exp = (int) $matches[1];
            // If less than 5 minutes remaining or expired, treat as expired
            if (($exp - time()) < 300) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get non-blocking valid stream from cache if available and not expired.
     */
    public static function getValidCachedEpisodeStream(string $episodeId): ?array
    {
        // Chitogenx: URL bertanda tangan, selalu ambil baru.
        if (str_starts_with($episodeId, ChitogenxService::ID_PREFIX . ':')) {
            return null;
        }

        $cached = Cache::get('episode_stream_' . $episodeId);
        if ($cached && !self::isStreamExpired($cached)) {
            return $cached;
        }
        return null;
    }

    /**
     * Fetch Stream and Subtitles for a specific Episode ID
     */
    public static function getEpisodeStream(string $episodeId, bool $force = false)
    {
        if (str_starts_with($episodeId, 'mb-')) {
            if (preg_match('/^mb-(.*?)-ep-(\d+)$/', $episodeId, $m)) {
                return NartoMovieBoxService::resolveEpisodeStream('mb-' . $m[1], (int)$m[2]);
            }
        }

        if (str_starts_with($episodeId, 'rb-')) {
            return RebahinService::resolveStream($episodeId);
        }

        // Provider utama: Chitogenx. URL bertanda tangan -> tanpa cache.
        $chitoEpisode = ChitogenxService::parseEpisodeId($episodeId);

        if ($chitoEpisode) {
            $play = ChitogenxService::play(
                $chitoEpisode['slug'],
                $chitoEpisode['season'],
                $chitoEpisode['episode']
            );

            return $play ?: null;
        }

        $cacheKey = 'episode_stream_' . $episodeId;

        if (!$force) {
            $cached = Cache::get($cacheKey);
            if ($cached && !self::isStreamExpired($cached)) {
                return $cached;
            }
        }

        $lockKey = 'lock_stream_ep_' . $episodeId;
        $lock = Cache::lock($lockKey, 60);

        try {
            // Wait up to 45 seconds if another process is currently fetching
            $lock->block(45);

            if (!$force) {
                $cached = Cache::get($cacheKey);
                if ($cached && !self::isStreamExpired($cached)) {
                    return $cached;
                }
            }

            Cache::forget($cacheKey);

            $domain = ScraperSetting::getInstance()->idlix_domain;
            $playInfoPath = '/api/watch/play-info/episode/' . $episodeId;
            $playInfoUrl = rtrim($domain, '/') . $playInfoPath;
            
            // Fast check if CF is active
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])->timeout(10)->get($playInfoUrl);

            $streamResult = null;

            if (!$response->successful() && ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment'))) {
                // Try NUC Residential Resolver first (3s connect timeout if NUC offline, 25s read timeout)
                $streamResult = self::fetchStreamWithNucResolver('episode', $episodeId);
            } else {
                $playInfo = $response->json();
                $gateToken = $playInfo['gateToken'] ?? null;
                if (!$gateToken) {
                    return null;
                }

                // Wait for unlockAt in PHP if CF is disabled but unlock delay is active
                $serverNow = $playInfo['serverNow'] ?? time() * 1000;
                $unlockAt = $playInfo['unlockAt'] ?? $serverNow;
                $delayMs = $unlockAt - $serverNow;
                if ($delayMs > 0) {
                    usleep(($delayMs + 1000) * 1000); // add 1s buffer, convert ms to microseconds
                }

                // 2. Claim
                $claimUrl = rtrim($domain, '/') . '/api/watch/session/claim';
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Referer' => $domain,
                ])->timeout(15)->post($claimUrl, [
                    'gateToken' => $gateToken
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $claimData = $response->json();
                $claim = $claimData['claim'] ?? null;
                $redeemUrl = $claimData['redeemUrl'] ?? null;

                if (!$claim || !$redeemUrl) {
                    return null;
                }

                // 3. Redeem
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Referer' => $domain,
                ])->timeout(15)->post($redeemUrl, [
                    'claim' => $claim
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $redeemData = $response->json();
                
                // Fetch the JWPlayer config JSON to get the real source files
                $configUrl = $redeemData['url'] ?? null;
                $videoUrl = $configUrl;
                
                if ($configUrl && !str_contains($configUrl, '.m3u8')) {
                    try {
                        $configResponse = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'application/json',
                        ])->timeout(15)->get($configUrl);
                        
                        if ($configResponse->successful() && str_contains($configResponse->header('Content-Type', ''), 'application/json')) {
                            $configData = $configResponse->json();
                            if (isset($configData['playlist'][0]['sources'])) {
                                foreach ($configData['playlist'][0]['sources'] as $src) {
                                    if (isset($src['file'])) {
                                        $videoUrl = $src['file'];
                                        break;
                                    }
                                }
                            }
                            if (!$videoUrl && isset($configData['playlist'][0]['file'])) {
                                $videoUrl = $configData['playlist'][0]['file'];
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore and use configUrl
                    }
                }

                $streamResult = [
                    'video_url' => $videoUrl,
                    'subtitles' => $redeemData['subtitles'] ?? [],
                    'title' => $redeemData['title'] ?? '',
                ];
            }

            if ($streamResult && !empty($streamResult['video_url'])) {
                $ttl = 3600;
                if (preg_match('/\.(\d{10})-/', $streamResult['video_url'], $matches)) {
                    $exp = (int) $matches[1];
                    $diff = $exp - time() - 300;
                    if ($diff > 60) {
                        $ttl = min($diff, 7200);
                    }
                }
                Cache::put($cacheKey, $streamResult, $ttl);
            }

            return $streamResult;

        } catch (\Exception $e) {
            Log::error('Error fetching episode stream: ' . $e->getMessage());
            return null;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Run Puppeteer stream scraper script to fetch video URL and subtitles.
     */
    public static function fetchStreamWithNucResolver(string $type, string $id): ?array
    {
        try {
            $url = 'http://100.110.54.105:8765/stream?type=' . urlencode($type) . '&id=' . urlencode($id);
            $response = Http::withOptions([
                'proxy' => 'socks5h://127.0.0.1:1055',
                'connect_timeout' => 3,
                'timeout' => 45,
            ])->get($url);

            if ($response->successful()) {
                $json = $response->json();
                if (!empty($json['success']) && !empty($json['raw_url'])) {
                    $proxyHlsUrl = url('/api/proxy-hls?url=' . urlencode($json['raw_url']));
                    $subs = [];
                    foreach ($json['subtitles'] ?? [] as $s) {
                        $subUrl = $s['url'] ?? ($s['path'] ?? '');
                        if (!empty($subUrl)) {
                            $subs[] = [
                                'lang' => $s['lang'] ?? 'id',
                                'label' => $s['name'] ?? ($s['label'] ?? 'Indonesian'),
                                'url' => $subUrl,
                                'path' => $subUrl,
                                'default' => !empty($s['default']),
                            ];
                        }
                    }
                    return [
                        'video_url' => $proxyHlsUrl,
                        'type' => 'm3u8',
                        'subtitles' => $subs,
                        'source' => 'idlix_nuc_relay',
                        'ttl' => $json['ttlSeconds'] ?? 21600,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('NUC Stream Resolver failed: ' . $e->getMessage());
        }
        return null;
    }

    private static function fetchStreamWithPuppeteer(string $domain, string $playInfoPath): ?array
    {
        $scriptPath = base_path('puppeteer_stream_scraper.js');
        $executablePath = env('PUPPETEER_EXECUTABLE_PATH', file_exists('/opt/puppeteer-chrome/chrome') ? '/opt/puppeteer-chrome/chrome' : '/usr/bin/google-chrome');
        $cmd = 'cd ' . escapeshellarg(base_path()) . ' && node --dns-result-order=ipv4first ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($domain) . ' ' . escapeshellarg($playInfoPath) . ' ' . escapeshellarg($executablePath) . ' 2>&1';

        $output = [];
        $returnVar = 0;
        
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("Puppeteer STREAM script failed. Code: {$returnVar}, Output: " . implode("\n", $output));
            return null;
        }

        $jsonStr = implode("\n", $output);
        $data = json_decode($jsonStr, true);

        if (isset($data['error'])) {
            Log::error("Puppeteer STREAM script returned error: " . $data['error']);
            return null;
        }

        return $data;
    }

    /**
     * Fetch Movie Detail from IDLIX / Rebahin
     */
    public static function getMovieDetail(string $slug)
    {
        if (str_starts_with($slug, 'mb-')) {
            $mbDetail = NartoMovieBoxService::getMovieDetail($slug);
            if ($mbDetail) {
                return $mbDetail;
            }
            // Seamless Failover: jika MovieBox gagal / 404, coba cari padanannya di Rebahin
            try {
                $cleanTitle = str_replace('-', ' ', preg_replace('/^mb-/', '', $slug));
                $rbMatch = RebahinService::getIndonesiaCatalog('movie', 1, $cleanTitle);
                if (!empty($rbMatch['data'][0]['raw_slug'])) {
                    $rbDetail = RebahinService::getMovieDetail('rb-' . $rbMatch['data'][0]['raw_slug']);
                    if ($rbDetail) {
                        $rbDetail['id'] = $slug;
                        $rbDetail['slug'] = $slug;
                        return $rbDetail;
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        }

        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getMovieDetail($slug);
        }

        if (str_starts_with($slug, 'ix-')) {
            return IndoxxiTechService::getMovieDetail($slug);
        }

        // Provider utama: Chitogenx.
        $chito = ChitogenxService::detail($slug);

        if ($chito && ($chito['type'] ?? '') === 'movie') {
            return $chito;
        }

        return Cache::remember('idlix_movie_' . $slug, 3600, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/movies/' . $slug;

                $formatted = null;
                $jsonContent = self::fetchJsonWithPuppeteer($url);
                if ($jsonContent) {
                    $data = json_decode($jsonContent, true);
                    if ($data && is_array($data)) {
                        $formatted = self::formatMovieData($data);
                    }
                }

                if ($formatted) {
                    if (!empty($formatted['id'])) {
                        Cache::put('movie_meta_by_id_' . $formatted['id'], [
                            'title' => $formatted['title'] ?? '',
                            'year' => $formatted['year'] ?? null,
                            'tmdbId' => $formatted['tmdb_id'] ?? null,
                        ], 86400);
                    }
                    return $formatted;
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching movie detail: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Format raw movie data to internal mapping
     */
    private static function formatMovieData(array $data): array
    {
        // Build TMDB image URLs if paths are set
        $posterPath = $data['posterPath'] ?? null;
        $backdropPath = $data['backdropPath'] ?? null;
        
        $thumbnail = $posterPath ? 'https://image.tmdb.org/t/p/w500' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80';
        $banner = $backdropPath ? 'https://image.tmdb.org/t/p/w1280' . $backdropPath : ($posterPath ? 'https://image.tmdb.org/t/p/w1280' . $posterPath : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1600&q=80');

        $rating = $data['voteAverage'] ?? '0.0';
        if (is_numeric($rating)) {
            $rating = number_format((float)$rating, 1);
        }

        $year = '';
        if (!empty($data['releaseDate'])) {
            $year = substr($data['releaseDate'], 0, 4);
        }

        // Genres mapping
        $genres = [];
        if (!empty($data['genres']) && is_array($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                if (isset($genre['name'])) {
                    $genres[] = $genre['name'];
                }
            }
        }
        $genreStr = implode(', ', $genres);

        return [
            'id' => $data['id'] ?? '',
            'title' => $data['title'] ?? 'No Title',
            'type' => 'movie',
            'quality' => $data['quality'] ?? 'WEB-DL',
            'year' => $year,
            'rating' => $rating,
            'duration' => isset($data['runtime']) ? $data['runtime'] . 'm' : '',
            'genre' => $genreStr,
            'thumbnail' => $thumbnail,
            'banner' => $banner,
            'description' => $data['overview'] ?? '',
            'video_url' => '', // Will be filled by getMovieStream
            'slug' => $data['slug'] ?? '',
            'tmdb_id' => $data['tmdbId'] ?? null,
            'imdb_id' => $data['imdbId'] ?? null,
            'raw_detail' => $data,
        ];
    }

    /**
     * Get non-blocking valid stream from cache if available and not expired.
     */
    public static function getValidCachedMovieStream(string $movieId): ?array
    {
        // Chitogenx: URL bertanda tangan, selalu ambil baru.
        if (str_starts_with($movieId, ChitogenxService::ID_PREFIX . ':')) {
            return null;
        }

        $cached = Cache::get('movie_stream_' . $movieId);
        if ($cached && !self::isStreamExpired($cached)) {
            return $cached;
        }
        return null;
    }

    /**
     * Find matching IDLIX movie by title and optional release year.
     */
    public static function findIdlixMovieByTitle(string $title, ?string $year = null): ?array
    {
        $cleanTitle = trim(preg_replace('/\s*\(\d{4}\)$/', '', $title));
        $cleanTitle = trim(preg_replace('/^(Nonton\s+(Film)?\s*)/i', '', $cleanTitle));
        $cleanTitle = trim(preg_replace('/\s*(Sub\s+Indo)?(\|\s*REBAHIN)?$/i', '', $cleanTitle));

        if (empty($cleanTitle)) {
            return null;
        }

        $cacheKey = 'rb_to_idlix_m_' . md5(strtolower($cleanTitle . '_' . ($year ?? '')));
        return Cache::remember($cacheKey, 86400 * 7, function () use ($cleanTitle, $year) {
            // 1. Direct slug candidates
            $baseSlug = \Illuminate\Support\Str::slug($cleanTitle);
            $slugCandidates = [];
            if ($year) {
                $slugCandidates[] = $baseSlug . '-' . $year;
            }
            $slugCandidates[] = $baseSlug;

            foreach ($slugCandidates as $cand) {
                $detail = self::getMovieDetail($cand);
                if ($detail && !empty($detail['id']) && !str_starts_with($detail['id'], 'rb-')) {
                    return $detail;
                }
            }

            // 2. Search IDLIX via API / Puppeteer
            try {
                $setting = ScraperSetting::getInstance();
                $domain = rtrim($setting->idlix_domain ?: 'https://z2.idlixku.com', '/');
                $searchUrl = $domain . '/api/search?q=' . urlencode($cleanTitle);

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(5)->get($searchUrl);

                $items = [];
                if ($response->successful()) {
                    $json = $response->json() ?: [];
                    $items = $json['data'] ?? $json['results'] ?? $json;
                }

                if (empty($items)) {
                    $rawJson = self::fetchJsonWithPuppeteer($searchUrl);
                    if ($rawJson) {
                        $parsed = json_decode($rawJson, true);
                        $items = $parsed['data'] ?? $parsed['results'] ?? $parsed ?? [];
                    }
                }

                if (is_array($items) && !empty($items)) {
                    $normSearch = strtolower(preg_replace('/[^a-z0-9]/', '', $cleanTitle));
                    foreach ($items as $it) {
                        if (($it['contentType'] ?? $it['type'] ?? 'movie') !== 'movie') continue;
                        $itTitle = $it['title'] ?? $it['name'] ?? '';
                        $normIt = strtolower(preg_replace('/[^a-z0-9]/', '', $itTitle));

                        if ($normSearch === $normIt || str_contains($normIt, $normSearch) || str_contains($normSearch, $normIt)) {
                            $slug = $it['slug'] ?? '';
                            if ($slug) {
                                $detail = self::getMovieDetail($slug);
                                if ($detail && !empty($detail['id']) && !str_starts_with($detail['id'], 'rb-')) {
                                    return $detail;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('findIdlixMovieByTitle search failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Find matching IDLIX series by title and optional release year.
     */
    public static function findIdlixSeriesByTitle(string $title, ?string $year = null): ?array
    {
        $cleanTitle = trim(preg_replace('/\s*:\s*Season\s*\d+.*$/i', '', $title));
        $cleanTitle = trim(preg_replace('/\s*\(\d{4}\)$/', '', $cleanTitle));
        $cleanTitle = trim(preg_replace('/^(Nonton\s+(Series|Film)?\s*)/i', '', $cleanTitle));
        $cleanTitle = trim(preg_replace('/\s*(Sub\s+Indo)?(\|\s*REBAHIN)?$/i', '', $cleanTitle));

        if (empty($cleanTitle)) {
            return null;
        }

        $cacheKey = 'rb_to_idlix_s_' . md5(strtolower($cleanTitle . '_' . ($year ?? '')));
        return Cache::remember($cacheKey, 86400 * 7, function () use ($cleanTitle, $year) {
            $baseSlug = \Illuminate\Support\Str::slug($cleanTitle);
            $slugCandidates = [];
            if ($year) {
                $slugCandidates[] = $baseSlug . '-' . $year;
            }
            $slugCandidates[] = $baseSlug;

            foreach ($slugCandidates as $cand) {
                $detail = self::getSeriesDetail($cand);
                if ($detail && !empty($detail['id']) && !str_starts_with($detail['id'], 'rb-')) {
                    return $detail;
                }
            }

            try {
                $setting = ScraperSetting::getInstance();
                $domain = rtrim($setting->idlix_domain ?: 'https://z2.idlixku.com', '/');
                $searchUrl = $domain . '/api/search?q=' . urlencode($cleanTitle);

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(5)->get($searchUrl);

                $items = [];
                if ($response->successful()) {
                    $json = $response->json() ?: [];
                    $items = $json['data'] ?? $json['results'] ?? $json;
                }

                if (empty($items)) {
                    $rawJson = self::fetchJsonWithPuppeteer($searchUrl);
                    if ($rawJson) {
                        $parsed = json_decode($rawJson, true);
                        $items = $parsed['data'] ?? $parsed['results'] ?? $parsed ?? [];
                    }
                }

                if (is_array($items) && !empty($items)) {
                    $normSearch = strtolower(preg_replace('/[^a-z0-9]/', '', $cleanTitle));
                    foreach ($items as $it) {
                        if (($it['contentType'] ?? $it['type'] ?? '') !== 'tv_series') continue;
                        $itTitle = $it['title'] ?? $it['name'] ?? '';
                        $normIt = strtolower(preg_replace('/[^a-z0-9]/', '', $itTitle));

                        if ($normSearch === $normIt || str_contains($normIt, $normSearch) || str_contains($normSearch, $normIt)) {
                            $slug = $it['slug'] ?? '';
                            if ($slug) {
                                $detail = self::getSeriesDetail($slug);
                                if ($detail && !empty($detail['id']) && !str_starts_with($detail['id'], 'rb-')) {
                                    return $detail;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('findIdlixSeriesByTitle search failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Fetch Stream and Subtitles for a specific Movie ID
     */
    public static function getMovieStream(string $movieId, bool $force = false)
    {
        // Provider utama: Chitogenx. URL bertanda tangan -> tanpa cache.
        $chitoMovie = ChitogenxService::parseEpisodeId($movieId);

        if ($chitoMovie && $chitoMovie['season'] === null) {
            $play = ChitogenxService::play($chitoMovie['slug']);

            return $play ?: null;
        }

        if (str_starts_with($movieId, 'mb-')) {
            $detail = NartoMovieBoxService::getMovieDetail($movieId);
            if ($detail && !empty($detail['video_url'])) {
                return [
                    'video_url' => $detail['video_url'],
                    'subtitles' => $detail['subtitles'] ?? [],
                ];
            }
        }

        if (str_starts_with($movieId, 'rb-')) {
            $rbStream = RebahinService::resolveStream($movieId);
            if ($rbStream && !empty($rbStream['video_url'])) {
                return $rbStream;
            }

            // Fallback: Check if matching IDLIX movie exists and has a valid stream
            try {
                $rbDetail = RebahinService::getMovieDetail($movieId);
                if ($rbDetail) {
                    $idlixMovie = self::findIdlixMovieByTitle($rbDetail['title'] ?? '', $rbDetail['year'] ?? null);
                    if ($idlixMovie && !empty($idlixMovie['id'])) {
                        return self::getMovieStream($idlixMovie['id'], $force);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('getMovieStream rb fallback failed: ' . $e->getMessage());
            }

            return null;
        }

        $cacheKey = 'movie_stream_' . $movieId;

        if (!$force) {
            $cached = Cache::get($cacheKey);
            if ($cached && !self::isStreamExpired($cached)) {
                return $cached;
            }
        }

        $lockKey = 'lock_stream_movie_' . $movieId;
        $lock = Cache::lock($lockKey, 60);

        try {
            $lock->block(45);

            if (!$force) {
                $cached = Cache::get($cacheKey);
                if ($cached && !self::isStreamExpired($cached)) {
                    return $cached;
                }
            }

            Cache::forget($cacheKey);

            $domain = ScraperSetting::getInstance()->idlix_domain;
            $playInfoPath = '/api/watch/play-info/movie/' . $movieId;
            $playInfoUrl = rtrim($domain, '/') . $playInfoPath;
            
            // Fast check if CF is active
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])->timeout(10)->get($playInfoUrl);

            $streamResult = null;

            if (!$response->successful() && ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment'))) {
                // Try NUC Residential Resolver first (fast 3s connect timeout when NUC is offline)
                $streamResult = self::fetchStreamWithNucResolver('movie', $movieId);
            } else {
                $playInfo = $response->json();
                $gateToken = $playInfo['gateToken'] ?? null;
                if (!$gateToken) {
                    return null;
                }

                // Wait for unlockAt in PHP if CF is disabled but unlock delay is active
                $serverNow = $playInfo['serverNow'] ?? time() * 1000;
                $unlockAt = $playInfo['unlockAt'] ?? $serverNow;
                $delayMs = $unlockAt - $serverNow;
                if ($delayMs > 0) {
                    usleep(($delayMs + 1000) * 1000); // add 1s buffer, convert ms to microseconds
                }

                // 2. Claim
                $claimUrl = rtrim($domain, '/') . '/api/watch/session/claim';
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Referer' => $domain,
                ])->timeout(15)->post($claimUrl, [
                    'gateToken' => $gateToken
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $claimData = $response->json();
                $claim = $claimData['claim'] ?? null;
                $redeemUrl = $claimData['redeemUrl'] ?? null;

                if (!$claim || !$redeemUrl) {
                    return null;
                }

                // 3. Redeem
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Referer' => $domain,
                ])->timeout(15)->post($redeemUrl, [
                    'claim' => $claim
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $redeemData = $response->json();
                
                $configUrl = $redeemData['url'] ?? null;
                $videoUrl = $configUrl;
                
                if ($configUrl && !str_contains($configUrl, '.m3u8')) {
                    try {
                        $configResponse = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'application/json',
                        ])->timeout(15)->get($configUrl);
                        
                        if ($configResponse->successful() && str_contains($configResponse->header('Content-Type', ''), 'application/json')) {
                            $configData = $configResponse->json();
                            if (isset($configData['playlist'][0]['sources'])) {
                                foreach ($configData['playlist'][0]['sources'] as $src) {
                                    if (isset($src['file'])) {
                                        $videoUrl = $src['file'];
                                        break;
                                    }
                                }
                            }
                            if (!$videoUrl && isset($configData['playlist'][0]['file'])) {
                                $videoUrl = $configData['playlist'][0]['file'];
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore and use configUrl
                    }
                }

                $streamResult = [
                    'video_url' => $videoUrl,
                    'subtitles' => $redeemData['subtitles'] ?? [],
                    'title' => $redeemData['title'] ?? '',
                ];
            }

            if ($streamResult && !empty($streamResult['video_url'])) {
                $ttl = 3600;
                if (preg_match('/\.(\d{10})-/', $streamResult['video_url'], $matches)) {
                    $exp = (int) $matches[1];
                    $diff = $exp - time() - 300;
                    if ($diff > 60) {
                        $ttl = min($diff, 7200);
                    }
                }
                Cache::put($cacheKey, $streamResult, $ttl);
            }

            // Fallback ke DirectStreamResolverService jika IDLIX gagal/down
            if (!$streamResult || empty($streamResult['video_url'])) {
                try {
                    $meta = Cache::get('movie_meta_by_id_' . $movieId);
                    $title = $meta['title'] ?? null;
                    $year = $meta['year'] ?? null;
                    if (!$title) {
                        $movieDetail = Cache::get('idlix_movie_' . $movieId);
                        $title = $movieDetail['title'] ?? null;
                        $year = $movieDetail['year'] ?? null;
                    }
                    if ($title) {
                        $resolved = DirectStreamResolverService::resolveMovieStream($title, $year);
                        if ($resolved && !empty($resolved['video_url'])) {
                            $streamResult = $resolved;
                            Cache::put($cacheKey, $streamResult, 3600);
                        }
                    }
                } catch (\Throwable $te) {
                    Log::warning('DirectStreamResolver fallback failed: ' . $te->getMessage());
                }
            }

            return $streamResult;

        } catch (\Exception $e) {
            Log::error('Error fetching movie stream: ' . $e->getMessage());
            return null;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Legacy helper to retrieve dummy data for WatchMovie fallback.
     */
    public static function getDummyData(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Carolina Caroline',
                'slug' => 'carolina-caroline',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '6.7',
                'comments' => 0,
                'duration' => '2h 10m',
                'genre' => 'Drama, Romance',
                'thumbnail' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A beautifully shot romantic drama detailing the life of Carolina and her journey through modern love, heartbreak, and self-discovery.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
            [
                'id' => 2,
                'title' => 'Tuner',
                'slug' => 'tuner',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.0',
                'comments' => 0,
                'duration' => '1h 45m',
                'genre' => 'Action, Thriller',
                'thumbnail' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A high-octane thriller about an expert mechanic drawn into the underground crime syndicate as a getaway driver.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ],
            [
                'id' => 3,
                'title' => 'Blue Heron',
                'slug' => 'blue-heron',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.5',
                'comments' => 0,
                'duration' => '1h 55m',
                'genre' => 'Drama, Mystery',
                'thumbnail' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=1600&q=80',
                'description' => 'An investigative drama set in the calm waters of a lakeside town, where a local legend hides a darker secret.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            ],
            [
                'id' => 4,
                'title' => 'Chapter 51',
                'slug' => 'chapter-51',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '8.1',
                'comments' => 0,
                'duration' => '2h 15m',
                'genre' => 'Sci-Fi, Adventure',
                'thumbnail' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1600&q=80',
                'description' => 'The ultimate chapter of a legendary saga. Earth discovers a wormhole in Sector 51, leading to an unknown civilization.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            ],
            [
                'id' => 5,
                'title' => 'ChaO',
                'slug' => 'chao',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2025',
                'rating' => '8.2',
                'comments' => 0,
                'duration' => '1h 50m',
                'genre' => 'Animation, Fantasy',
                'thumbnail' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=1600&q=80',
                'description' => 'An artistic anime movie following the surreal adventures of a girl trapped in a kaleidoscope world of colors and sounds.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            ],
            [
                'id' => 6,
                'title' => 'Billie Eilish - Hit Me Hard and Soft: The Tour Live in 3D',
                'slug' => 'billie-eilish-hit-me-hard',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2024',
                'rating' => '7.5',
                'comments' => 0,
                'duration' => '1h 30m',
                'genre' => 'Documentary, Music',
                'thumbnail' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Experience the immersive concert experience of Billie Eilish\'s worldwide tour, filmed live in stereoscopic 3D.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            ],
            [
                'id' => 7,
                'title' => 'Pegasus 2',
                'slug' => 'pegasus-2',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2024',
                'rating' => '7.2',
                'comments' => 0,
                'duration' => '2h 00m',
                'genre' => 'Action, Comedy',
                'thumbnail' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=1600&q=80',
                'description' => 'The return of the legendary rally driver as he mentors a new generation of racers in the ultimate desert speed duel.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
            [
                'id' => 8,
                'title' => 'Palm Trees in the Snow',
                'slug' => 'palm-trees-in-the-snow',
                'type' => 'Movie',
                'quality' => 'BLU-RAY',
                'year' => '2015',
                'rating' => '7.4',
                'comments' => 0,
                'duration' => '2h 43m',
                'genre' => 'Drama, History',
                'thumbnail' => 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A discovery of a long-lost letter prompts a journey back to the equatorial colonies, revealing an epic story of forbidden love.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ],
            [
                'id' => 9,
                'title' => 'Goal III: Taking on the World',
                'slug' => 'goal-iii',
                'type' => 'Movie',
                'quality' => 'BLU-RAY',
                'year' => '2009',
                'rating' => '4.0',
                'comments' => 0,
                'duration' => '1h 36m',
                'genre' => 'Drama, Sports',
                'thumbnail' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Two young football players get selected to play for their respective national teams at the biggest sporting event on Earth.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            ],
            [
                'id' => 10,
                'title' => 'Goal II: Living the Dream',
                'slug' => 'goal-ii',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2007',
                'rating' => '6.0',
                'comments' => 0,
                'duration' => '1h 55m',
                'genre' => 'Drama, Sports',
                'thumbnail' => 'https://images.unsplash.com/photo-1518063319789-7217e6706b04?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1518063319789-7217e6706b04?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Santiago Munez secures a high-profile transfer to Real Madrid, but fame and success begin to impact his personal life.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            ],
            [
                'id' => 11,
                'title' => 'The Iron Giant',
                'slug' => 'the-iron-giant',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '1999',
                'rating' => '8.0',
                'comments' => 2,
                'duration' => '1h 26m',
                'genre' => 'Animation, Sci-Fi',
                'thumbnail' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A young boy befriends a giant alien robot that a paranoid government agent wants to destroy.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            ],
            [
                'id' => 12,
                'title' => 'Husbands in Action',
                'slug' => 'husbands-in-action',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '7.8',
                'comments' => 15,
                'duration' => '1h 48m',
                'genre' => 'Comedy',
                'thumbnail' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80',
                'description' => 'Three middle-aged husbands hatch a hilarious plan to reclaim their youth, leading to comedic errors and chaotic adventures.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            ],
            [
                'id' => 13,
                'title' => 'Voicemails for Isabelle',
                'slug' => 'voicemails-for-isabelle',
                'type' => 'Movie',
                'quality' => 'WEB-DL',
                'year' => '2026',
                'rating' => '6.6',
                'comments' => 30,
                'duration' => '1h 52m',
                'genre' => 'Drama, Romance',
                'thumbnail' => 'https://images.unsplash.com/photo-1514565131-fce0801e5785?auto=format&fit=crop&w=600&q=80',
                'banner' => 'https://images.unsplash.com/photo-1514565131-fce0801e5785?auto=format&fit=crop&w=1600&q=80',
                'description' => 'A romantic story told through a series of voice messages left on an answering machine over the course of a year.',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
        ];
    }
}