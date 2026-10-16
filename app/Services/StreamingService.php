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
        $results = [];
        try {
            $domain = ScraperSetting::getInstance()->idlix_domain;
            $url = rtrim($domain, '/') . '/api/search?q=' . urlencode($query) . '&page=' . $page . '&limit=' . $limit;

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
     * Get IDLIX Streaming Networks List
     */
    public static function getNetworksList(): array
    {
        return [
            [
                'slug' => 'netflix',
                'name' => 'Netflix',
                'title' => 'Netflix Movies & Series',
                'description' => 'Film dan serial Netflix, mulai dari Netflix Original sampai rilisan terbaru yang lagi trending.',
                'color' => '#E50914',
                'accent' => 'from-red-600/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-red-600/50',
                'badge' => '1.385+ Judul',
                'logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.76 5.11 12.48 3.77" class="h-8 sm:h-9 max-w-[150px] w-auto" role="img" aria-label="Netflix" fill="currentColor"><title>Netflix</title><path fill="currentColor" d="m 11.922451,5.4384 c 0,0.044 0.05878,0.382 0.127347,0.7444 l 0.127347,0.6613 -0.127347,0.7885 c -0.07347,0.436 -0.127347,0.818 -0.127347,0.8474 0,0.039 0.06367,0.064 0.161633,0.064 l 0.161632,0 0.06367,-0.5339 c 0.03429,-0.2939 0.07837,-0.5976 0.09796,-0.6759 l 0.03918,-0.1372 0.02939,0.1225 c 0.01469,0.069 0.05388,0.3771 0.08816,0.6906 l 0.05878,0.573 0.171429,0.029 c 0.09796,0.015 0.181224,0.02 0.186122,0.015 0.0098,0 -0.04898,-0.4065 -0.122449,-0.8865 L 12.725713,6.8688 12.857958,6.1782 c 0.07837,-0.3821 0.137143,-0.7249 0.142041,-0.7592 0,-0.034 -0.06367,-0.059 -0.166531,-0.059 -0.19102,0 -0.186122,-0.015 -0.279182,0.671 -0.03429,0.2498 -0.07837,0.4555 -0.09796,0.4555 -0.01959,0 -0.06367,-0.1616 -0.09306,-0.3575 -0.03428,-0.1911 -0.07347,-0.4409 -0.09306,-0.5486 -0.02939,-0.1763 -0.04898,-0.1959 -0.19102,-0.2106 -0.122449,-0.015 -0.156735,0 -0.156735,0.069 z m -1.420408,1.4351 0,1.5232 0.146939,0 0.146938,0 -0.0098,-1.5086 -0.01469,-1.5036 -0.132245,-0.015 -0.137143,-0.015 0,1.5184 z M 8.8220425,5.3894 c -0.01959,0.02 -0.03429,0.6808 -0.03429,1.4645 l 0,1.4302 0.151837,0.034 c 0.08326,0.015 0.249796,0.029 0.367347,0.029 0.205714,0 0.21551,0 0.21551,-0.142 0,-0.1371 -0.01469,-0.1469 -0.205714,-0.1616 l -0.210613,-0.015 -0.0098,-1.3225 -0.0098,-1.3224 -0.117551,-0.015 c -0.05878,-0.01 -0.127347,0 -0.146939,0.02 z m -1.797551,0 c 0,0.015 0,0.671 -0.0049,1.4547 l 0,1.4351 0.151837,0.015 0.146938,0.015 0,-0.6123 0,-0.6122 0.210613,-0.015 c 0.186122,-0.015 0.205714,-0.029 0.220408,-0.1519 0.01469,-0.1273 0.0098,-0.1322 -0.205715,-0.1322 l -0.225306,0 0,-0.5339 0,-0.5388 0.210613,-0.015 c 0.19102,-0.015 0.205714,-0.024 0.205714,-0.1617 l 0,-0.1469 -0.352653,-0.015 c -0.195919,-0.01 -0.357551,0 -0.357551,0.015 z m -2.022857,0 c -0.01959,0.02 -0.03429,0.098 -0.03429,0.1763 0,0.1224 0.01469,0.1372 0.171429,0.1372 l 0.171428,0 0,1.2881 c 0,0.7102 0.0098,1.3029 0.02449,1.3176 0.01469,0.01 0.08816,0.015 0.171428,0 l 0.146939,-0.02 0,-1.2931 0,-1.293 0.176327,0 c 0.16653,0 0.171428,0 0.156734,-0.1617 l -0.01469,-0.1567 -0.465307,-0.015 c -0.259591,0 -0.484898,0 -0.504489,0.02 z m -1.601633,1.5183 c 0,1.3911 0.0098,1.5478 0.07837,1.5184 0.04408,-0.015 0.205715,-0.029 0.367347,-0.029 0.28898,0 0.28898,0 0.28898,-0.1469 0,-0.1421 -0.0098,-0.1469 -0.220408,-0.1469 l -0.220408,0 0,-0.5143 0,-0.5143 0.220408,0 c 0.210612,0 0.220408,0 0.220408,-0.147 0,-0.142 -0.0098,-0.1469 -0.220408,-0.1469 l -0.220408,0 0,-0.5339 0,-0.5388 0.210612,-0.015 c 0.19102,-0.015 0.205714,-0.024 0.205714,-0.1617 l 0,-0.1469 -0.352653,-0.015 -0.357551,-0.015 0,1.5526 z M 1.0342875,5.3943 c -0.019592,0.015 -0.034286,0.7543 -0.034286,1.6408 l 0,1.6114 0.156735,-0.015 0.161632,-0.015 -0.01959,-1.1167 c -0.0098,-0.7984 0,-1.1118 0.03918,-1.1118 0.02939,0 0.05388,0.015 0.05388,0.034 0,0.059 0.288979,1.9346 0.318367,2.0473 0.01959,0.088 0.05388,0.098 0.293878,0.083 l 0.269387,-0.02 0,-1.5919 0,-1.5869 -0.151836,0.015 -0.146939,0.015 0.0049,1.1853 c 0,0.7886 -0.01469,1.1902 -0.04898,1.1902 -0.02449,0 -0.04898,-0.015 -0.04898,-0.029 0,-0.1029 -0.347755,-2.2922 -0.372245,-2.3265 -0.02939,-0.049 -0.426123,-0.059 -0.475102,-0.01 z"></path></svg>',
            ],
            [
                'slug' => 'hbo',
                'name' => 'HBO',
                'title' => 'HBO & HBO Max Movies & Series',
                'description' => 'Serial drama ikonik, dokumenter, dan film blockbuster dari HBO dan HBO Max.',
                'color' => '#9933FF',
                'accent' => 'from-purple-600/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-purple-600/50',
                'badge' => '189+ Judul',
                'logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="16.96 41.77 158.08 108.46" class="h-8 sm:h-9 max-w-[150px] w-auto" role="img" aria-label="HBO" fill="currentColor"><title>HBO</title><circle cx="137.82" cy="71.24" r="9.4" fill="currentColor"></circle><path fill="currentColor" d="M137.82 56.81c7.96 0 14.43 6.48 14.43 14.43s-6.48 14.43-14.43 14.43-14.43-6.48-14.43-14.43 6.48-14.43 14.43-14.43m0-12c-14.6 0-26.43 11.83-26.43 26.43s11.83 26.43 26.43 26.43 26.43-11.83 26.43-26.43-11.83-26.43-26.43-26.43Z"></path><path fill="none" stroke="currentColor" stroke-width="12" stroke-linejoin="round" stroke-linecap="round" d="M43.23 51.86v38.77m23.34-38.77v38.77M43.52 71.24h23.05"></path><path fill="none" stroke="currentColor" stroke-width="12" stroke-linejoin="round" style="stroke-linecap:round" d="M82.91 51.86v38.77h16.55c4.41-.36 7.91-4.45 8.06-9.4.15-4.88-2.99-9.19-7.29-9.99 4.3-.79 7.44-5.1 7.29-9.99-.14-4.84-3.47-8.88-7.77-9.4H82.9Z"></path><path fill="none" stroke="currentColor" stroke-width="12" stroke-linejoin="round" stroke-linecap="round" d="M82.91 71.24h17.33"></path><path fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" stroke-linecap="round" d="M47.68 116.06c-8.48-13.02-15.72-12.3-20.67-8.71-4.5 3.27-7.01 8.82-7.01 14.58v24.68m27.68-.01v-30.54"></path><path fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" stroke-linecap="round" d="M75.36 146.6v-23.47c0-6.09-2.65-11.94-7.38-15.45-12.13-9-20.31 8.38-20.31 8.38m76.49-10.97v42.1"></path><ellipse cx="105.28" cy="126.63" rx="18.4" ry="20.56" fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" stroke-linecap="round"></ellipse><path fill="none" stroke="currentColor" stroke-width="8" stroke-linejoin="round" stroke-linecap="round" d="M135.76 104.9 172 147.19m0-42.29-36.24 42.29"></path></svg>',
            ],
            [
                'slug' => 'prime-video',
                'name' => 'Prime Video',
                'title' => 'Prime Video Movies & Series',
                'description' => 'Amazon Originals eksklusif, film hits, dan serial unggulan dari Prime Video.',
                'color' => '#00A8E1',
                'accent' => 'from-sky-600/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-sky-500/50',
                'badge' => '380+ Judul',
                'logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.4 10.8 31.2 10.41" class="h-8 sm:h-9 max-w-[150px] w-auto" role="img" aria-label="Prime Video" fill="currentColor"><title>Prime Video</title><path fill="currentColor" d="M8.281 17.253c0.009-0.065 0.064-0.114 0.13-0.114 0.001 0 0.002 0 0.003 0h-0c0.041 0.002 0.080 0.014 0.112 0.036l-0.001-0.001c2.199 1.386 4.873 2.21 7.739 2.214h0.001c2.144-0.014 4.173-0.492 5.995-1.341l-0.089 0.037c0.036-0.022 0.080-0.035 0.127-0.035 0.138 0 0.25 0.112 0.25 0.25 0 0.093-0.051 0.174-0.126 0.217l-0.001 0.001c-1.748 1.303-3.95 2.087-6.336 2.087h-0c-3.017-0.002-5.75-1.217-7.738-3.184l0.001 0.001c-0.034-0.031-0.058-0.073-0.067-0.12l-0-0.001zM21.007 17.468c0.463-0.32 1.037-0.511 1.655-0.511 0.029 0 0.058 0 0.087 0.001l-0.004-0c0.050-0.005 0.109-0.008 0.168-0.008 0.32 0 0.619 0.089 0.875 0.244l-0.008-0.004c0.015 0.119 0.023 0.258 0.023 0.398 0 0.939-0.379 1.789-0.992 2.406l0-0c-0.14 0.127-0.275 0.059-0.212-0.109 0.207-0.552 0.67-1.794 0.45-2.096-0.219-0.302-1.447-0.144-1.999-0.072-0.085 0.010-0.134-0.025-0.14-0.076v-0.029c0.013-0.061 0.049-0.111 0.098-0.143l0.001-0.001zM29.56 15.111q0.596 0 0.596-0.91c0-0.604-0.2-0.905-0.596-0.905q-0.596 0-0.596 0.905c-0 0.607 0.2 0.91 0.596 0.91zM29.56 15.754c-0.023 0.001-0.049 0.002-0.075 0.002-0.384 0-0.731-0.158-0.98-0.412l-0-0c-0.238-0.279-0.383-0.644-0.383-1.043 0-0.035 0.001-0.069 0.003-0.104l-0 0.005c-0.002-0.029-0.003-0.062-0.003-0.096 0-0.398 0.145-0.763 0.385-1.044l-0.002 0.002c0.278-0.253 0.649-0.409 1.056-0.409s0.778 0.155 1.057 0.41l-0.001-0.001c0.238 0.279 0.383 0.643 0.383 1.041 0 0.034-0.001 0.067-0.003 0.1l0-0.005c0.002 0.029 0.003 0.064 0.003 0.098 0 0.399-0.145 0.764-0.385 1.046l0.002-0.002c-0.249 0.254-0.597 0.412-0.981 0.412-0.026 0-0.053-0.001-0.079-0.002l0.004 0zM26.508 13.251c-0.356 0-0.562 0.22-0.617 0.662 0.142 0.029 0.306 0.045 0.473 0.045 0.008 0 0.016-0 0.023-0h-0.001c0.016 0.001 0.035 0.002 0.054 0.002 0.131 0 0.254-0.033 0.361-0.091l-0.004 0.002c0.078-0.055 0.128-0.144 0.128-0.245 0-0.007-0-0.015-0.001-0.022l0 0.001c0-0.237-0.139-0.355-0.416-0.355zM26.43 14.463c-0.012 0-0.025 0-0.039 0-0.181 0-0.357-0.023-0.525-0.066l0.015 0.003c0.001 0.211 0.087 0.401 0.225 0.54l-0-0c0.142 0.103 0.321 0.165 0.513 0.165 0.024 0 0.047-0.001 0.071-0.003l-0.003 0c0.002 0 0.005 0 0.008 0 0.104 0 0.206-0.007 0.306-0.021l-0.012 0.001c0.174-0.027 0.323-0.060 0.469-0.101l-0.026 0.006 0.027-0.002c0.062 0 0.094 0.044 0.094 0.129v0.259c0 0.006 0.001 0.012 0.001 0.019 0 0.039-0.010 0.076-0.026 0.109l0.001-0.001c-0.025 0.030-0.057 0.052-0.095 0.065l-0.001 0c-0.252 0.1-0.544 0.157-0.849 0.157-0.005 0-0.009 0-0.014-0h0.001c-0.028 0.002-0.060 0.003-0.093 0.003-0.374 0-0.714-0.147-0.965-0.386l0.001 0.001c-0.232-0.264-0.373-0.613-0.373-0.995 0-0.035 0.001-0.070 0.004-0.104l-0 0.005c-0.002-0.029-0.003-0.063-0.003-0.097 0-0.4 0.144-0.767 0.382-1.052l-0.002 0.003c0.246-0.258 0.592-0.418 0.976-0.418 0.026 0 0.052 0.001 0.077 0.002l-0.004-0c0.021-0.001 0.046-0.002 0.071-0.002 0.28 0 0.537 0.096 0.74 0.258l-0.002-0.002c0.18 0.156 0.293 0.385 0.293 0.641 0 0.011-0 0.023-0.001 0.034l0-0.002c0.001 0.012 0.001 0.026 0.001 0.040 0 0.247-0.124 0.465-0.312 0.596l-0.002 0.002c-0.235 0.138-0.518 0.22-0.819 0.22-0.037 0-0.074-0.001-0.11-0.004l0.005 0zM23.195 15.1c0.195-0.001 0.376-0.061 0.526-0.162l-0.003 0.002v-1.512c-0.155-0.090-0.341-0.143-0.539-0.143-0.008 0-0.016 0-0.024 0l0.001-0c-0.009-0.001-0.020-0.001-0.031-0.001-0.174 0-0.327 0.089-0.416 0.224l-0.001 0.002c-0.095 0.173-0.151 0.378-0.151 0.597 0 0.031 0.001 0.062 0.003 0.092l-0-0.004c-0.002 0.026-0.003 0.056-0.003 0.086 0 0.219 0.059 0.425 0.161 0.602l-0.003-0.006c0.101 0.136 0.261 0.224 0.442 0.224 0.013 0 0.027-0.001 0.040-0.001l-0.002 0zM22.923 15.73c-0.008 0-0.016 0-0.025 0-0.23 0-0.444-0.072-0.619-0.194l0.004 0.002c-0.185-0.134-0.328-0.314-0.414-0.524l-0.003-0.008c-0.092-0.221-0.145-0.479-0.145-0.748 0-0.014 0-0.027 0-0.041l-0 0.002c-0.001-0.027-0.002-0.058-0.002-0.090 0-0.393 0.126-0.757 0.341-1.052l-0.004 0.005c0.197-0.26 0.507-0.427 0.855-0.427 0.011 0 0.022 0 0.033 0.001l-0.002-0c0.008-0 0.017-0 0.026-0.001 0.284 0 0.546 0.097 0.754 0.26l-0.003-0.002v-1.34c-0-0.004-0.001-0.009-0.001-0.014 0-0.037 0.013-0.071 0.035-0.098l-0 0c0.026-0.020 0.060-0.032 0.096-0.032 0.004 0 0.009 0 0.013 0.001l-0.001-0h0.545c0.008-0.002 0.017-0.003 0.027-0.003 0.065 0 0.118 0.053 0.118 0.118 0 0.010-0.001 0.020-0.004 0.030l0-0.001v3.95c0 0.005 0.001 0.010 0.001 0.015 0 0.035-0.012 0.068-0.032 0.094l0-0c-0.025 0.022-0.058 0.036-0.094 0.036-0.006 0-0.011-0-0.016-0.001l0.001 0h-0.455c-0.003 0-0.007 0-0.010 0-0.035 0-0.068-0.011-0.095-0.029l0.001 0c-0.027-0.025-0.047-0.059-0.054-0.096l-0-0.001-0.034-0.131c-0.213 0.198-0.5 0.32-0.815 0.32-0.007 0-0.014-0-0.021-0h0.001zM20.489 15.668c-0.004 0-0.008 0.001-0.012 0.001-0.037 0-0.070-0.013-0.096-0.036l0 0c-0.022-0.025-0.036-0.058-0.036-0.095 0-0.005 0-0.010 0.001-0.014l-0 0.001v-2.639c-0-0.004-0.001-0.009-0.001-0.014 0-0.037 0.013-0.072 0.036-0.098l-0 0c0.026-0.019 0.059-0.031 0.094-0.031 0.005 0 0.009 0 0.014 0.001l-0.001-0h0.545c0.005-0.001 0.011-0.001 0.017-0.001 0.036 0 0.069 0.012 0.095 0.032l-0-0c0.019 0.026 0.031 0.059 0.031 0.095 0 0.006-0 0.012-0.001 0.018l0-0.001v2.638c0 0.004 0.001 0.010 0.001 0.015 0 0.035-0.012 0.068-0.031 0.094l0-0c-0.026 0.022-0.059 0.036-0.096 0.036-0.006 0-0.012-0-0.017-0.001l0.001 0zM20.761 12.29c-0.007 0-0.015 0.001-0.022 0.001-0.124 0-0.238-0.046-0.324-0.122l0.001 0c-0.081-0.085-0.131-0.2-0.131-0.326s0.050-0.242 0.131-0.326l-0 0c0.094-0.075 0.215-0.12 0.346-0.12s0.252 0.045 0.347 0.121l-0.001-0.001c0.080 0.085 0.13 0.2 0.13 0.326s-0.049 0.241-0.13 0.326l0-0c-0.086 0.076-0.199 0.122-0.324 0.122-0.008 0-0.016-0-0.024-0.001l0.001 0zM18.237 15.668c-0.003 0-0.006 0-0.009 0-0.030 0-0.059-0.005-0.086-0.014l0.002 0.001c-0.025-0.010-0.046-0.026-0.062-0.046l-0-0c-0.019-0.025-0.035-0.055-0.047-0.086l-0.001-0.002-0.971-2.549-0.029-0.079c-0.007-0.019-0.011-0.040-0.011-0.062v-0c0-0.059 0.038-0.087 0.114-0.087h0.567c0.008-0.001 0.017-0.002 0.027-0.002 0.088 0 0.161 0.066 0.172 0.151l0 0.001 0.585 2.027 0.596-2.027c0.010-0.046 0.033-0.085 0.065-0.115l0-0c0.033-0.022 0.073-0.034 0.117-0.034 0.006 0 0.012 0 0.018 0.001l-0.001-0h0.55c0.076 0 0.114 0.027 0.114 0.085-0 0.023-0.005 0.044-0.012 0.064l0-0.001-0.027 0.081-0.971 2.548c-0.013 0.033-0.029 0.062-0.049 0.088l0.001-0.001c-0.015 0.020-0.035 0.036-0.058 0.047l-0.001 0c-0.024 0.008-0.052 0.013-0.081 0.013-0.006 0-0.012-0-0.017-0.001l0.001 0zM14.553 13.236c-0.365 0-0.575 0.225-0.632 0.675 0.145 0.030 0.312 0.046 0.482 0.046 0.009 0 0.018-0 0.026-0h-0.001c0.017 0.001 0.037 0.002 0.057 0.002 0.133 0 0.258-0.034 0.368-0.093l-0.004 0.002c0.080-0.056 0.132-0.148 0.132-0.251 0-0.007-0-0.015-0.001-0.022l0 0.001q0-0.362-0.427-0.361zM14.473 14.473c-0.010 0-0.022 0-0.035 0-0.188 0-0.37-0.023-0.545-0.066l0.016 0.003c0.002 0.215 0.090 0.409 0.231 0.55l0 0c0.146 0.105 0.329 0.168 0.526 0.168 0.024 0 0.048-0.001 0.072-0.003l-0.003 0c0.002 0 0.005 0 0.008 0 0.107 0 0.212-0.007 0.316-0.021l-0.012 0.001c0.178-0.027 0.33-0.061 0.48-0.104l-0.027 0.007 0.029-0.002q0.097 0 0.097 0.131v0.264c0 0.006 0.001 0.012 0.001 0.019 0 0.040-0.009 0.078-0.026 0.111l0.001-0.001c-0.026 0.031-0.060 0.054-0.098 0.066l-0.002 0c-0.258 0.101-0.557 0.16-0.869 0.16-0.005 0-0.011-0-0.016-0h0.001c-0.029 0.002-0.063 0.003-0.097 0.003-0.383 0-0.731-0.15-0.988-0.394l0.001 0.001c-0.238-0.268-0.383-0.623-0.383-1.013 0-0.036 0.001-0.073 0.004-0.108l-0 0.005c-0.002-0.029-0.003-0.063-0.003-0.097 0-0.409 0.148-0.784 0.392-1.074l-0.002 0.002c0.252-0.263 0.607-0.427 1-0.427 0.027 0 0.055 0.001 0.082 0.002l-0.004-0c0.022-0.001 0.047-0.002 0.072-0.002 0.287 0 0.551 0.098 0.76 0.262l-0.003-0.002c0.185 0.159 0.301 0.393 0.301 0.654 0 0.012-0 0.023-0.001 0.035l0-0.002c0.001 0.012 0.001 0.026 0.001 0.040 0 0.253-0.127 0.476-0.321 0.608l-0.003 0.002c-0.24 0.141-0.529 0.225-0.837 0.225-0.040 0-0.079-0.001-0.118-0.004l0.005 0zM8.991 13.023c0.157-0.11 0.336-0.206 0.527-0.278l0.017-0.006c0.148-0.053 0.319-0.084 0.497-0.084 0.001 0 0.002 0 0.003 0h-0c0.020-0.002 0.044-0.003 0.068-0.003 0.277 0 0.52 0.147 0.654 0.367l0.002 0.003c0.158-0.112 0.338-0.207 0.532-0.276l0.016-0.005c0.157-0.055 0.338-0.086 0.527-0.086 0.001 0 0.002 0 0.003 0h-0c0.015-0.001 0.032-0.002 0.050-0.002 0.22 0 0.42 0.086 0.569 0.226l-0-0c0.137 0.151 0.22 0.352 0.22 0.573 0 0.018-0.001 0.036-0.002 0.054l0-0.002v2.022c0 0.004 0.001 0.009 0.001 0.015 0 0.036-0.012 0.068-0.032 0.095l0-0c-0.025 0.022-0.058 0.035-0.094 0.035-0.006 0-0.012-0-0.018-0.001l0.001 0h-0.547c-0.005 0.001-0.010 0.001-0.015 0.001-0.035 0-0.068-0.013-0.092-0.035l0 0c-0.022-0.026-0.035-0.060-0.035-0.097 0-0.004 0-0.009 0.001-0.013l-0 0.001v-1.838c0-0.26-0.115-0.39-0.347-0.39-0.227 0.003-0.44 0.057-0.629 0.152l0.008-0.004v2.079c0 0.004 0.001 0.009 0.001 0.015 0 0.036-0.012 0.068-0.032 0.095l0-0c-0.025 0.022-0.058 0.035-0.094 0.035-0.006 0-0.013-0-0.019-0.001l0.001 0h-0.546c-0.005 0.001-0.010 0.001-0.015 0.001-0.035 0-0.068-0.013-0.092-0.035l0 0c-0.022-0.025-0.036-0.058-0.036-0.095 0-0.005 0-0.010 0.001-0.014l-0 0.001v-1.838c0-0.26-0.116-0.39-0.347-0.39-0.229 0.001-0.445 0.058-0.635 0.157l0.008-0.004v2.074c0 0.004 0.001 0.009 0.001 0.015 0 0.036-0.012 0.068-0.032 0.095l0-0c-0.025 0.022-0.058 0.035-0.094 0.035-0.006 0-0.012-0-0.018-0.001l0.001 0h-0.548c-0.004 0-0.009 0.001-0.014 0.001-0.036 0-0.068-0.013-0.093-0.035l0 0c-0.021-0.026-0.034-0.059-0.034-0.095 0-0.005 0-0.009 0.001-0.014l-0 0.001v-2.64c-0.001-0.005-0.001-0.011-0.001-0.016 0-0.037 0.013-0.070 0.035-0.097l-0 0c0.026-0.020 0.058-0.032 0.094-0.032 0.005 0 0.010 0 0.014 0.001l-0.001-0h0.411c0.004-0 0.008-0.001 0.013-0.001 0.074 0 0.136 0.055 0.147 0.126l0 0.001zM6.695 12.886c-0-0.004-0.001-0.009-0.001-0.014 0-0.037 0.013-0.071 0.035-0.098l-0 0c0.026-0.020 0.059-0.032 0.095-0.032 0.005 0 0.009 0 0.014 0.001l-0.001-0h0.547c0.005-0.001 0.011-0.001 0.017-0.001 0.036 0 0.068 0.012 0.095 0.032l-0-0c0.020 0.026 0.032 0.059 0.032 0.095 0 0.006-0 0.012-0.001 0.018l0-0.001v2.64c0 0.004 0.001 0.009 0.001 0.015 0 0.036-0.012 0.068-0.032 0.095l0-0c-0.026 0.022-0.060 0.036-0.097 0.036-0.005 0-0.011-0-0.016-0.001l0.001 0h-0.546c-0.003 0-0.007 0-0.011 0-0.037 0-0.071-0.013-0.097-0.036l0 0c-0.021-0.026-0.034-0.059-0.034-0.095 0-0.005 0-0.009 0.001-0.014l-0 0.001zM7.111 12.29c-0.007 0-0.015 0.001-0.024 0.001-0.124 0-0.237-0.046-0.324-0.121l0.001 0.001c-0.081-0.077-0.132-0.186-0.132-0.306 0-0.007 0-0.015 0.001-0.022l-0 0.001c-0-0.006-0.001-0.014-0.001-0.021 0-0.12 0.051-0.227 0.132-0.303l0-0c0.095-0.076 0.216-0.121 0.348-0.121s0.252 0.045 0.348 0.121l-0.001-0.001c0.081 0.077 0.132 0.185 0.132 0.305 0 0.007-0 0.015-0.001 0.022l0-0.001c0 0.006 0 0.013 0 0.020 0 0.121-0.051 0.23-0.132 0.307l-0 0c-0.086 0.075-0.2 0.12-0.324 0.12-0.008 0-0.017-0-0.025-0.001l0.001 0zM5.204 13.177c0.121-0.142 0.263-0.261 0.422-0.353l0.008-0.004c0.124-0.067 0.272-0.106 0.428-0.106 0.001 0 0.002 0 0.003 0h0.078c0.004-0 0.009-0.001 0.014-0.001 0.038 0 0.073 0.012 0.101 0.032l-0.001-0c0.022 0.026 0.035 0.060 0.035 0.096 0 0.006-0 0.011-0.001 0.017l0-0.001v0.481c0 0.004 0.001 0.009 0.001 0.014 0 0.036-0.012 0.069-0.032 0.095l0-0c-0.026 0.022-0.059 0.035-0.095 0.035-0.006 0-0.011-0-0.017-0.001l0.001 0q-0.051-0-0.102-0.005c-0.039-0.003-0.084-0.005-0.129-0.005-0.124 0-0.245 0.013-0.361 0.038l0.011-0.002c-0.121 0.023-0.228 0.055-0.33 0.099l0.010-0.004v1.924c0 0.004 0.001 0.009 0.001 0.015 0 0.036-0.012 0.068-0.032 0.095l0-0c-0.025 0.022-0.059 0.036-0.096 0.036-0.005 0-0.011-0-0.016-0.001l0.001 0h-0.546c-0.006 0.001-0.013 0.002-0.020 0.002-0.069 0-0.125-0.056-0.125-0.125 0-0.007 0.001-0.014 0.002-0.021l-0 0.001v-2.641c-0-0.005-0.001-0.010-0.001-0.015 0-0.037 0.013-0.071 0.036-0.098l-0 0c0.026-0.020 0.059-0.032 0.094-0.032 0.005 0 0.010 0 0.015 0.001l-0.001-0h0.41c0.004-0 0.008-0.001 0.013-0.001 0.074 0 0.135 0.054 0.147 0.125l0 0.001zM2.395 13.286c-0.206 0-0.399 0.058-0.563 0.158l0.005-0.003v1.507c0.156 0.097 0.344 0.155 0.547 0.155 0.004 0 0.008-0 0.012-0h-0.001c0.012 0.001 0.026 0.002 0.040 0.002 0.176 0 0.332-0.086 0.429-0.217l0.001-0.001c0.097-0.172 0.155-0.377 0.155-0.596 0-0.032-0.001-0.064-0.004-0.096l0 0.004c0.002-0.028 0.003-0.060 0.003-0.093 0-0.22-0.056-0.427-0.154-0.607l0.003 0.007c-0.098-0.133-0.255-0.219-0.431-0.219-0.015 0-0.030 0.001-0.044 0.002l0.002-0zM1.004 12.886c-0-0.004-0.001-0.010-0.001-0.015 0-0.037 0.014-0.071 0.036-0.098l-0 0c0.026-0.020 0.058-0.032 0.094-0.032 0.005 0 0.010 0 0.014 0.001l-0.001-0h0.411c0.004-0 0.009-0.001 0.013-0.001 0.074 0 0.135 0.055 0.145 0.126l0 0.001 0.040 0.149c0.115-0.11 0.252-0.199 0.404-0.259l0.009-0.003c0.146-0.062 0.316-0.098 0.495-0.099h0c0.012-0 0.026-0.001 0.040-0.001 0.338 0 0.64 0.159 0.833 0.406l0.002 0.002c0.202 0.28 0.324 0.629 0.324 1.007 0 0.033-0.001 0.065-0.003 0.097l0-0.004c0 0.014 0.001 0.030 0.001 0.046 0 0.284-0.059 0.554-0.164 0.798l0.005-0.013c-0.095 0.223-0.244 0.407-0.43 0.543l-0.004 0.003c-0.17 0.12-0.382 0.193-0.61 0.193-0.006 0-0.012-0-0.018-0h0.001c-0.002 0-0.005 0-0.007 0-0.156 0-0.307-0.027-0.446-0.078l0.009 0.003c-0.139-0.047-0.259-0.117-0.361-0.206l0.001 0.001v1.253c0.001 0.005 0.001 0.011 0.001 0.017 0 0.036-0.012 0.069-0.032 0.096l0-0c-0.027 0.020-0.060 0.032-0.096 0.032-0.006 0-0.011-0-0.017-0.001l0.001 0h-0.545c-0.004 0-0.008 0-0.013 0-0.036 0-0.069-0.012-0.095-0.032l0 0c-0.022-0.026-0.036-0.059-0.036-0.096 0-0.006 0-0.012 0.001-0.017l-0 0.001z"></path></svg>',
            ],
            [
                'slug' => 'disney-plus',
                'name' => 'Disney+',
                'title' => 'Disney+ Movies & Series',
                'description' => 'Film dan serial Disney, Pixar, Marvel, Star Wars, dan National Geographic.',
                'color' => '#113CCF',
                'accent' => 'from-blue-700/25 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-blue-500/50',
                'badge' => '414+ Judul',
                'logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="13.79 13.67 189.86 54.68" class="h-8 sm:h-9 max-w-[150px] w-auto" role="img" aria-label="Disney+"><title>Disney+</title><path d="M20 52 C60 8 160 8 200 40" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".85"/><text x="18" y="56" font-family="Georgia, \'Times New Roman\', serif" font-style="italic" font-weight="700" font-size="40" fill="currentColor">Disney</text><text x="168" y="56" font-family="Arial, sans-serif" font-weight="700" font-size="40" fill="currentColor">+</text></svg>',
            ],
            [
                'slug' => 'apple-tv-plus',
                'name' => 'Apple TV+',
                'title' => 'Apple TV+ Movies & Series',
                'description' => 'Apple Originals pemenang penghargaan, drama, komedi, dan film bertabur bintang.',
                'color' => '#A2AAAD',
                'accent' => 'from-zinc-500/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-zinc-400/50',
                'badge' => '168+ Judul',
                'logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.48 8.06 31.09 15.87" class="h-8 sm:h-9 max-w-[150px] w-auto" role="img" aria-label="Apple TV+" fill="currentColor"><title>Apple TV+</title><path fill="currentColor" d="M9.096 10.993c0.478-0.56 0.77-1.293 0.77-2.094 0-0.082-0.003-0.163-0.009-0.243l0.001 0.011c-0.861 0.088-1.613 0.494-2.147 1.098l-0.003 0.004c-0.484 0.535-0.781 1.249-0.781 2.031 0 0.078 0.003 0.156 0.009 0.233l-0.001-0.010c0.008 0 0.018 0 0.027 0 0.862 0 1.63-0.4 2.13-1.024l0.004-0.005zM10.928 16.407c0.009 1.317 0.802 2.446 1.935 2.944l0.021 0.008c-0.258 0.789-0.6 1.474-1.026 2.097l0.018-0.028c-0.608 0.886-1.235 1.77-2.234 1.791-0.977 0.020-1.285-0.577-2.409-0.577-1.111 0-1.46 0.555-2.387 0.597-0.946 0.031-1.677-0.967-2.294-1.852-0.919-1.24-1.471-2.801-1.471-4.49 0-1.026 0.204-2.005 0.573-2.898l-0.018 0.050c0.603-1.072 1.722-1.791 3.011-1.822l0.004-0c0.936-0.020 1.832 0.639 2.409 0.639 0.575 0 1.656-0.782 2.788-0.669 1.1 0.042 2.062 0.596 2.659 1.43l0.007 0.010c-0.944 0.58-1.568 1.6-1.585 2.767l-0 0.003zM20.026 23.075c-0.573 0.164-1.23 0.258-1.91 0.258-0.009 0-0.017 0-0.026-0h0.001c-1.535 0-2.296-0.865-2.296-2.604v-7.666h-1.327v-1.39h1.399v-1.811l1.842-0.751v2.573h2.13v1.389h-2.118v7.244c-0.005 0.051-0.008 0.11-0.008 0.17 0 0.367 0.102 0.709 0.279 1.002l-0.005-0.009c0.204 0.208 0.488 0.337 0.802 0.337 0.047 0 0.093-0.003 0.139-0.009l-0.005 0.001c0.401-0.018 0.779-0.069 1.145-0.152l-0.043 0.008zM26.695 23.157h-2.265l-4.168-11.485h2.038l2.521 7.471c0.094 0.288 0.341 1.123 0.731 2.511l0.371-1.244 0.412-1.255 2.613-7.493h2.028z"></path></svg>',
            ]
        ];
    }

    /**
     * Get single network detail by slug
     */
    public static function getNetworkDetail(string $slug): ?array
    {
        $networks = self::getNetworksList();
        foreach ($networks as $net) {
            if ($net['slug'] === $slug) {
                return $net;
            }
        }
        return null;
    }

    /**
     * Get media items for a specific network from IDLIX API
     */
    public static function getNetworkMedia(string $networkSlug, int $page = 1, int $limit = 36, string $sort = 'latest', ?string $type = null): array
    {
        $cacheKey = "idlix_network_{$networkSlug}_p{$page}_l{$limit}_s{$sort}_t" . ($type ?? 'all');
        return Cache::remember($cacheKey, 1800, function () use ($networkSlug, $page, $limit, $sort, $type) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . "/api/browse?page={$page}&limit={$limit}&sort={$sort}&network={$networkSlug}";
                if ($type && $type !== 'all') {
                    $url .= "&type={$type}";
                }

                try {
                    $client = new Client([
                        'timeout' => 8,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                            'Accept' => 'application/json, text/plain, */*',
                        ],
                        'verify' => false,
                    ]);
                    $res = $client->get($url);
                    if ($res->getStatusCode() === 200) {
                        $data = json_decode($res->getBody()->getContents(), true);
                        if (!empty($data['data'])) {
                            return self::formatNetworkMediaData($data, $page, $limit);
                        }
                    }
                } catch (\Exception $ge) {
                    // Fallback to Puppeteer if Guzzle blocked
                }

                $jsonText = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($jsonText, true);
                if (!empty($data['data'])) {
                    return self::formatNetworkMediaData($data, $page, $limit);
                }

                return [
                    'data' => [],
                    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 1]
                ];
            } catch (\Exception $e) {
                Log::error("Error fetching network media for {$networkSlug}: " . $e->getMessage());
                return [
                    'data' => [],
                    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 1]
                ];
            }
        });
    }

    /**
     * Format raw IDLIX browse media items
     */
    private static function formatNetworkMediaData(array $raw, int $page = 1, int $limit = 36): array
    {
        $results = [];
        $items = $raw['data'] ?? [];

        foreach ($items as $item) {
            $mapped = self::mapMediaItem($item);
            if ($mapped) {
                $results[] = $mapped;
            }
        }

        $pagination = $raw['pagination'] ?? [
            'page' => $page,
            'limit' => $limit,
            'total' => count($results),
            'totalPages' => 1
        ];

        return [
            'data' => $results,
            'pagination' => $pagination
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
        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getSeriesDetail($slug);
        }

        return Cache::remember('idlix_series_' . $slug, 3600, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/series/' . $slug;

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(15)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Format images
                    if (isset($data['posterPath'])) {
                        $data['posterPath'] = 'https://image.tmdb.org/t/p/w500' . $data['posterPath'];
                    }
                    if (isset($data['backdropPath'])) {
                        $data['backdropPath'] = 'https://image.tmdb.org/t/p/w1280' . $data['backdropPath'];
                    }
                    
                    // Format episode thumbnails
                    if (isset($data['defaultSeason']['episodes'])) {
                        foreach ($data['defaultSeason']['episodes'] as &$ep) {
                            if (isset($ep['stillPath'])) {
                                $ep['stillPath'] = 'https://image.tmdb.org/t/p/w500' . $ep['stillPath'];
                            }
                        }
                    }

                    return $data;
                }

                // Cloudflare fallback for series
                if ($response->status() === 503 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment')) {
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
        if (str_starts_with($slug, 'rb-')) {
            $s = RebahinService::getSeriesDetail($slug);
            return [
                'season' => $s['defaultSeason'] ?? ['seasonNumber' => $seasonNumber, 'episodes' => []]
            ];
        }

        return Cache::remember('idlix_series_season_' . $slug . '_' . $seasonNumber, 3600, function () use ($slug, $seasonNumber) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/series/' . $slug . '/season/' . $seasonNumber;

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(15)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Format episode thumbnails
                    if (isset($data['season']['episodes'])) {
                        foreach ($data['season']['episodes'] as &$ep) {
                            if (isset($ep['stillPath'])) {
                                $ep['stillPath'] = 'https://image.tmdb.org/t/p/w500' . $ep['stillPath'];
                            }
                        }
                    }

                    return $data;
                }

                // Cloudflare fallback
                if ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment')) {
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
        if (str_starts_with($episodeId, 'rb-')) {
            return RebahinService::resolveStream($episodeId);
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
                // Fallback to Puppeteer Stream Scraper
                $streamResult = self::fetchStreamWithPuppeteer($domain, $playInfoPath);
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
        if (str_starts_with($slug, 'rb-')) {
            return RebahinService::getMovieDetail($slug);
        }

        return Cache::remember('idlix_movie_' . $slug, 3600, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/movies/' . $slug;

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(15)->get($url);

                $formatted = null;
                if ($response->successful()) {
                    $data = $response->json();
                    $formatted = self::formatMovieData($data);
                }

                // Cloudflare fallback for movies
                if (!$formatted && ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment'))) {
                    $jsonContent = self::fetchJsonWithPuppeteer($url);
                    if ($jsonContent) {
                        $data = json_decode($jsonContent, true);
                        if ($data && is_array($data)) {
                            $formatted = self::formatMovieData($data);
                        }
                    }
                }

                if ($formatted) {
                    try {
                        $mirror = RebahinService::findMovieMirror($formatted['title'] ?? '', $formatted['year'] ?? null);
                        if ($mirror && !empty($mirror['servers'])) {
                            $formatted['mirror_servers'] = $mirror['servers'];
                        }
                    } catch (\Exception $e) {}
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
        ];
    }

    /**
     * Get non-blocking valid stream from cache if available and not expired.
     */
    public static function getValidCachedMovieStream(string $movieId): ?array
    {
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
                // Fallback to Puppeteer Stream Scraper
                $streamResult = self::fetchStreamWithPuppeteer($domain, $playInfoPath);
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