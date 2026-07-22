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
                return self::fetchHomepageJson();
            } catch (\Exception $e) {
                Log::error('Failed to fetch IDLIX homepage: ' . $e->getMessage());
                // Fallback to empty structure
                return [
                    'hero' => null,
                    'sections' => []
                ];
            }
        });
    }

    /**
     * Search IDLIX API
     */
    public static function search(string $query, int $page = 1, int $limit = 8): array
    {
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
            $results = [];
            
            $items = $data['data'] ?? $data['results'] ?? $data ?? [];
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
            Log::warning('Search failed via Guzzle: ' . $e->getMessage() . '. Falling back to Puppeteer.');
            try {
                if (!isset($url)) {
                    $domain = ScraperSetting::getInstance()->idlix_domain;
                    $url = rtrim($domain, '/') . '/api/search?q=' . urlencode($query) . '&page=' . $page . '&limit=' . $limit;
                }
                $body = self::fetchJsonWithPuppeteer($url);
                $data = json_decode($body, true);
                
                $results = [];
                $items = $data['data'] ?? $data['results'] ?? $data ?? [];
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
                Log::error('Search failed via Puppeteer: ' . $pe->getMessage());
                return [];
            }
        }
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
                return [
                    'data' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => 0,
                        'totalPages' => 0
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
                return [
                    'data' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => 0,
                        'totalPages' => 0
                    ]
                ];
            }
        }
        });
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
        $executablePath = env('PUPPETEER_EXECUTABLE_PATH');
        $cmd = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($executablePath ?? '') . ' 2>&1';

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
            
            // Skip ads
            if ($type === 'ads') {
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
            'description' => $content['overview'] ?? '',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', // Default demo url
            'slug' => $content['slug'] ?? '',
        ];
    }

    /**
     * Fetch Series Detail from IDLIX
     */
    public static function getSeriesDetail(string $slug)
    {
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
     * Fetch Series Season Detail from IDLIX
     */
    public static function getSeriesSeasonDetail(string $slug, int $seasonNumber)
    {
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
     * Fetch Stream and Subtitles for a specific Episode ID
     */
    public static function getEpisodeStream(string $episodeId)
    {
        return Cache::remember('episode_stream_' . $episodeId, 1800, function () use ($episodeId) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $playInfoPath = '/api/watch/play-info/episode/' . $episodeId;
                $playInfoUrl = rtrim($domain, '/') . $playInfoPath;
                
                // Fast check if CF is active
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(10)->get($playInfoUrl);

                if (!$response->successful() && ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment'))) {
                    // Fallback to Puppeteer Stream Scraper
                    return self::fetchStreamWithPuppeteer($domain, $playInfoPath);
                }

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

                return [
                    'video_url' => $videoUrl,
                    'subtitles' => $redeemData['subtitles'] ?? [],
                    'title' => $redeemData['title'] ?? '',
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching episode stream: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Run Puppeteer stream scraper script to fetch video URL and subtitles.
     */
    private static function fetchStreamWithPuppeteer(string $domain, string $playInfoPath): ?array
    {
        $scriptPath = base_path('puppeteer_stream_scraper.js');
        $executablePath = env('PUPPETEER_EXECUTABLE_PATH');
        $cmd = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($domain) . ' ' . escapeshellarg($playInfoPath) . ' ' . escapeshellarg($executablePath ?? '') . ' 2>&1';

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
     * Fetch Movie Detail from IDLIX
     */
    public static function getMovieDetail(string $slug)
    {
        return Cache::remember('idlix_movie_' . $slug, 3600, function () use ($slug) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $url = rtrim($domain, '/') . '/api/movies/' . $slug;

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(15)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    return self::formatMovieData($data);
                }

                // Cloudflare fallback for movies
                if ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment')) {
                    $jsonContent = self::fetchJsonWithPuppeteer($url);
                    if ($jsonContent) {
                        $data = json_decode($jsonContent, true);
                        if ($data && is_array($data)) {
                            return self::formatMovieData($data);
                        }
                    }
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
     * Fetch Stream and Subtitles for a specific Movie ID
     */
    public static function getMovieStream(string $movieId)
    {
        return Cache::remember('movie_stream_' . $movieId, 1800, function () use ($movieId) {
            try {
                $domain = ScraperSetting::getInstance()->idlix_domain;
                $playInfoPath = '/api/watch/play-info/movie/' . $movieId;
                $playInfoUrl = rtrim($domain, '/') . $playInfoPath;
                
                // Fast check if CF is active
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])->timeout(10)->get($playInfoUrl);

                if (!$response->successful() && ($response->status() === 503 || $response->status() === 403 || str_contains($response->body(), 'Cloudflare') || str_contains($response->body(), 'Just a moment'))) {
                    // Fallback to Puppeteer Stream Scraper
                    return self::fetchStreamWithPuppeteer($domain, $playInfoPath);
                }

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

                return [
                    'video_url' => $videoUrl,
                    'subtitles' => $redeemData['subtitles'] ?? [],
                    'title' => $redeemData['title'] ?? '',
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching movie stream: ' . $e->getMessage());
                return null;
            }
        });
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