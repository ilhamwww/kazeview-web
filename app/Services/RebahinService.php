<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RebahinService
{
    protected static string $baseUrl = 'https://rebahinxxi3.mom';

    protected static function getClient(): Client
    {
        return new Client([
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Referer' => self::$baseUrl . '/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
            ],
            'verify' => false,
        ]);
    }

    public static function getBaseUrl(): string
    {
        return self::$baseUrl;
    }

    /**
     * Ambil katalog film terbaru
     */
    public static function getMovies(int $page = 1): array
    {
        $cacheKey = "rb_movies_p{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($page) {
            try {
                $client = self::getClient();
                $url = $page > 1 
                    ? self::$baseUrl . "/movies/page/{$page}/"
                    : self::$baseUrl . "/movies/";

                $res = $client->get($url);
                if ($res->getStatusCode() !== 200) {
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }

                $html = (string) $res->getBody();
                $items = self::parseCards($html, 'movie');

                return [
                    'data' => $items,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => 100,
                        'hasMore' => count($items) > 0,
                    ],
                ];
            } catch (\Exception $e) {
                Log::warning('RebahinService::getMovies error: ' . $e->getMessage());
                return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
            }
        });
    }

    /**
     * Ambil katalog TV Series terbaru
     */
    public static function getSeries(int $page = 1): array
    {
        $cacheKey = "rb_series_p{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($page) {
            try {
                $client = self::getClient();
                $url = $page > 1 
                    ? self::$baseUrl . "/series/page/{$page}/"
                    : self::$baseUrl . "/series/";

                $res = $client->get($url);
                if ($res->getStatusCode() !== 200) {
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }

                $html = (string) $res->getBody();
                $items = self::parseCards($html, 'series');

                return [
                    'data' => $items,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => 100,
                        'hasMore' => count($items) > 0,
                    ],
                ];
            } catch (\Exception $e) {
                Log::warning('RebahinService::getSeries error: ' . $e->getMessage());
                return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
            }
        });
    }

    /**
     * Ambil katalog khusus Film & Series Indonesia
     */
    public static function getIndonesia(int $page = 1): array
    {
        $cacheKey = "rb_indonesia_p{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($page) {
            try {
                $client = self::getClient();
                $url = $page > 1 
                    ? self::$baseUrl . "/country/indonesia/page/{$page}/"
                    : self::$baseUrl . "/country/indonesia/";

                $res = $client->get($url);
                if ($res->getStatusCode() !== 200) {
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }

                $html = (string) $res->getBody();
                $items = self::parseCards($html);

                return [
                    'data' => $items,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => 100,
                        'hasMore' => count($items) > 0,
                    ],
                ];
            } catch (\Exception $e) {
                Log::warning('RebahinService::getIndonesia error: ' . $e->getMessage());
                return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
            }
        });
    }

    /**
     * Cek apakah judul film / serial benar-benar berasal dari Indonesia (Country: Indonesia)
     */
    public static function isIndonesianMovie(string $rawSlug, string $url = ''): bool
    {
        $cacheKey = "rb_is_indo_" . md5($rawSlug);
        return Cache::remember($cacheKey, 86400 * 30, function () use ($rawSlug, $url) {
            try {
                $client = self::getClient();
                $targetUrl = !empty($url) ? $url : (self::$baseUrl . "/{$rawSlug}/");
                $res = $client->get($targetUrl, ['timeout' => 4]);
                $body = (string) $res->getBody();
                if (preg_match("/class=[\x27\x22]mvici-right[\x27\x22].*?<\/div>/s", $body, $mr)) {
                    return (bool) preg_match('#/country/(?:indonesia|id)/#i', $mr[0]);
                }
                return false;
            } catch (\Throwable $e) {
                // Coba format /series/{slug}/ jika format film 404
                try {
                    $seriesUrl = self::$baseUrl . "/series/{$rawSlug}/";
                    $res2 = $client->get($seriesUrl, ['timeout' => 4]);
                    $body2 = (string) $res2->getBody();
                    if (preg_match("/class=[\x27\x22]mvici-right[\x27\x22].*?<\/div>/s", $body2, $mr)) {
                        return (bool) preg_match('#/country/(?:indonesia|id)/#i', $mr[0]);
                    }
                } catch (\Throwable $e2) {}
                return false;
            }
        });
    }

    /**
     * Ambil katalog Indonesia berdasarkan kategori (Semua, Film, Series, atau Genre) & kata kunci pencarian
     */
    public static function getIndonesiaCatalog(string $category = 'all', int $page = 1, string $search = ''): array
    {
        $search = trim($search);

        // Jika ada pencarian judul langsung
        if (!empty($search)) {
            $cacheKey = "rb_indo_s_" . md5(strtolower($search)) . "_p{$page}";
            return Cache::remember($cacheKey, 1800, function () use ($search, $page) {
                try {
                    $client = self::getClient();
                    $url = $page > 1
                        ? self::$baseUrl . "/page/{$page}/?s=" . urlencode($search)
                        : self::$baseUrl . "/?s=" . urlencode($search);

                    $res = $client->get($url);
                    if ($res->getStatusCode() !== 200) {
                        return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                    }

                    $html = (string) $res->getBody();
                    $items = self::parseCards($html);

                    return [
                        'data' => $items,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => 100,
                            'hasMore' => count($items) > 0,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::warning("RebahinService search '{$search}' error: " . $e->getMessage());
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }
            });
        }

        // Kategori: Series Indonesia
        if ($category === 'series') {
            $cacheKey = "rb_indo_series_p{$page}";
            return Cache::remember($cacheKey, 1800, function () use ($page) {
                try {
                    $client = self::getClient();
                    $url = $page > 1
                        ? self::$baseUrl . "/genre/series-indonesia/page/{$page}/"
                        : self::$baseUrl . "/genre/series-indonesia/";

                    $res = $client->get($url);
                    if ($res->getStatusCode() !== 200) {
                        return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                    }

                    $html = (string) $res->getBody();
                    $items = self::parseCards($html, 'series');

                    return [
                        'data' => $items,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => 100,
                            'hasMore' => count($items) > 0,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::warning("RebahinService series error: " . $e->getMessage());
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }
            });
        }

        // Kategori: Film Indonesia (Movie)
        if ($category === 'movie') {
            $cacheKey = "rb_indo_movies_p{$page}";
            return Cache::remember($cacheKey, 1800, function () use ($page) {
                try {
                    $client = self::getClient();
                    $url = $page > 1
                        ? self::$baseUrl . "/country/indonesia/page/{$page}/"
                        : self::$baseUrl . "/country/indonesia/";

                    $res = $client->get($url);
                    if ($res->getStatusCode() !== 200) {
                        return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                    }

                    $html = (string) $res->getBody();
                    $items = self::parseCards($html, 'movie');
                    $filtered = array_values(array_filter($items, fn($it) => ($it['type'] ?? '') === 'movie'));

                    return [
                        'data' => $filtered,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => 100,
                            'hasMore' => count($filtered) > 0,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::warning("RebahinService movie error: " . $e->getMessage());
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }
            });
        }

        // Kategori Tahun Rilis (2026, 2025, 2024, classic)
        if (in_array($category, ['2026', '2025', '2024', 'classic'], true)) {
            $cacheKey = "rb_indo_year_{$category}_p{$page}";
            return Cache::remember($cacheKey, 1800, function () use ($category, $page) {
                try {
                    $client = self::getClient();

                    // Tentukan halaman sumber di country/indonesia berdasarkan tahun
                    $startPage = match ($category) {
                        '2026' => $page,
                        '2025' => 4 + ($page - 1) * 2,
                        '2024' => 15 + ($page - 1) * 2,
                        'classic' => 25 + ($page - 1) * 2,
                        default => $page,
                    };

                    $pagesToFetch = ($category === '2026') ? [$startPage] : [$startPage, $startPage + 1];
                    $allItems = [];

                    foreach ($pagesToFetch as $pNum) {
                        $url = $pNum > 1
                            ? self::$baseUrl . "/country/indonesia/page/{$pNum}/"
                            : self::$baseUrl . "/country/indonesia/";

                        $res = $client->get($url);
                        if ($res->getStatusCode() === 200) {
                            $cards = self::parseCards((string) $res->getBody());
                            $allItems = array_merge($allItems, $cards);
                        }
                    }

                    $filtered = array_values(array_filter($allItems, function ($item) use ($category) {
                        $y = (int) ($item['year'] ?? 0);
                        return match ($category) {
                            '2026' => $y === 2026,
                            '2025' => $y === 2025,
                            '2024' => $y === 2024,
                            'classic' => $y > 1970 && $y <= 2023,
                            default => true,
                        };
                    }));

                    return [
                        'data' => $filtered,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => 100,
                            'hasMore' => count($filtered) > 0,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::warning("RebahinService year {$category} error: " . $e->getMessage());
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }
            });
        }

        // Kategori Genre (horor, komedi, romantis, drama, aksi, thriller) - dengan filter ketat negara Indonesia
        $validGenres = ['horor', 'komedi', 'romantis', 'drama', 'aksi', 'thriller'];
        if (in_array(strtolower($category), $validGenres, true)) {
            $cat = strtolower($category);
            $queryKw = "{$cat} indonesia";
            $cacheKey = "rb_indo_cat_{$cat}_p{$page}";
            return Cache::remember($cacheKey, 1800, function () use ($queryKw, $page, $cat) {
                try {
                    $client = self::getClient();
                    $url = $page > 1
                        ? self::$baseUrl . "/page/{$page}/?s=" . urlencode($queryKw)
                        : self::$baseUrl . "/?s=" . urlencode($queryKw);

                    $res = $client->get($url);
                    if ($res->getStatusCode() !== 200) {
                        return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                    }

                    $html = (string) $res->getBody();
                    $items = self::parseCards($html);

                    // Filter ketat: Pastikan hanya judul produksi Indonesia yang diloloskan
                    $filtered = [];
                    foreach ($items as $item) {
                        $rawSlug = $item['raw_slug'] ?? '';
                        if (empty($rawSlug)) continue;
                        $itemUrl = $item['href'] ?? (self::$baseUrl . "/{$rawSlug}/");
                        if (self::isIndonesianMovie($rawSlug, $itemUrl)) {
                            $filtered[] = $item;
                        }
                    }

                    return [
                        'data' => $filtered,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => 100,
                            'hasMore' => count($filtered) > 0,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::warning("RebahinService genre {$cat} error: " . $e->getMessage());
                    return ['data' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 1, 'hasMore' => false]];
                }
            });
        }

        // Default 'all'
        return self::getIndonesia($page);
    }

    /**
     * Pencarian film & series
     */
    public static function search(string $query): array
    {
        $query = trim($query);
        if (empty($query)) return [];

        $cacheKey = "rb_search_" . md5(strtolower($query));
        return Cache::remember($cacheKey, 1800, function () use ($query) {
            try {
                $client = self::getClient();
                $url = self::$baseUrl . "/?s=" . urlencode($query);
                $res = $client->get($url);
                if ($res->getStatusCode() !== 200) return [];

                $html = (string) $res->getBody();
                return self::parseCards($html);
            } catch (\Exception $e) {
                Log::warning('RebahinService::search error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Detail film untuk pemutaran
     */
    public static function getMovieDetail(string $slug): ?array
    {
        $rawSlug = preg_replace('/^rb-/', '', $slug);
        $cacheKey = "rb_movie_detail_" . md5($rawSlug);

        return Cache::remember($cacheKey, 3600, function () use ($rawSlug, $slug) {
            try {
                $client = self::getClient();
                
                // Ambil halaman play untuk mendapatkan server iframe
                $playUrl = self::$baseUrl . "/{$rawSlug}/play/";
                $resPlay = $client->get($playUrl);
                $htmlPlay = (string) $resPlay->getBody();

                // Ambil metadata & backdrop dari halaman utama
                $mainUrl = self::$baseUrl . "/{$rawSlug}/";
                $htmlMain = '';
                try {
                    $resMain = $client->get($mainUrl);
                    $htmlMain = (string) $resMain->getBody();
                } catch (\Exception $e) {
                    $htmlMain = $htmlPlay;
                }

                // Parse title
                preg_match("/<meta[^>]*property=[\x27\x22]og:title[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain ?: $htmlPlay, $t);
                $rawTitle = $t[1] ?? '';
                $cleanTitle = trim(preg_replace('/^(Nonton\s+(Film)?\s*)/i', '', $rawTitle));
                $cleanTitle = trim(preg_replace('/\s*(Sub\s+Indo)?\s*(\|\s*REBAHIN)?$/i', '', $cleanTitle));
                if (empty($cleanTitle)) $cleanTitle = ucwords(str_replace('-', ' ', $rawSlug));

                // Parse poster & backdrop
                preg_match("/<meta[^>]*property=[\x27\x22]og:image[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain ?: $htmlPlay, $p);
                $poster = $p[1] ?? '';
                if ($poster && !str_starts_with($poster, 'http')) $poster = self::$baseUrl . '/' . ltrim($poster, '/');

                preg_match("/class=[\x27\x22][^\x27\x22]*thumb mvi-cover[^\x27\x22]*[\x27\x22][^>]*style=[\x27\x22][^>]*url\(([^)]+)\)/i", $htmlMain, $bd);
                $backdrop = $bd[1] ?? $poster;
                if ($backdrop && !str_starts_with($backdrop, 'http')) $backdrop = self::$baseUrl . '/' . ltrim($backdrop, '/');

                // Parse synopsis
                $synopsis = '';
                preg_match("/<span itemprop=[\x27\x22]reviewBody[\x27\x22]>.*?<p>(.*?)<\/p>/s", $htmlMain, $syn);
                if (!empty($syn[1])) {
                    $synopsis = trim(strip_tags($syn[1]));
                    $synopsis = preg_replace('/^(Nonton\s+Film\s+.*?&#8211;\s*)/i', '', $synopsis);
                    $synopsis = preg_replace('/^(Nonton\s+Film\s+.*?--\s*)/i', '', $synopsis);
                }

                // Parse rating, duration, release date
                preg_match("/class=[\x27\x22]irank-voters[\x27\x22][^>]*>([\d\.]+)/", $htmlMain ?: $htmlPlay, $rt);
                $rating = !empty($rt[1]) ? (float)$rt[1] : 7.0;

                preg_match("/Duration:<\/strong>\s*(\d+)/i", $htmlMain, $dur);
                $runtime = !empty($dur[1]) ? (int)$dur[1] : null;

                preg_match("/Release Date:<\/strong>[^<]*<meta itemprop=[\x27\x22]datePublished[\x27\x22]\s*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain, $rd);
                $releaseDate = $rd[1] ?? '';
                $year = $releaseDate ? substr($releaseDate, 0, 4) : '';

                // Parse genres
                preg_match_all("/<a href=[\x27\x22][^\x27\x22]*\/genre\/[^\x27\x22]+[\x27\x22][^>]*><span itemprop=[\x27\x22]genre[\x27\x22]>([^<]+)<\/span><\/a>/i", $htmlMain, $gn);
                $genres = array_values(array_unique($gn[1] ?? []));

                // Parse servers
                preg_match_all("/data-iframe=[\x27\x22]([^\x27\x22]+)[\x27\x22]/", $htmlPlay, $sv);
                $servers = [];
                foreach ($sv[1] as $idx => $b64) {
                    $embedUrl = base64_decode($b64);
                    if ($embedUrl && str_starts_with($embedUrl, 'http')) {
                        $sNum = count($servers) + 1;
                        $label = $sNum === 1 ? 'Server 1 (HD)' : ($sNum === 2 ? 'Server 2 (Mirror)' : "Server {$sNum}");
                        $wrappedUrl = url('/api/streaming/embed?url=' . urlencode($embedUrl));
                        $servers[] = [
                            'id' => "server{$sNum}",
                            'name' => $label,
                            'embed_url' => $wrappedUrl,
                        ];
                    }
                }

                return [
                    'id' => 'rb-' . $rawSlug,
                    'title' => $cleanTitle,
                    'slug' => 'rb-' . $rawSlug,
                    'raw_slug' => $rawSlug,
                    'overview' => $synopsis,
                    'poster' => $poster,
                    'backdrop' => $backdrop,
                    'thumbnail' => $poster,
                    'voteAverage' => $rating,
                    'runtime' => $runtime,
                    'releaseDate' => $releaseDate,
                    'year' => $year,
                    'genres' => $genres,
                    'servers' => $servers,
                    'isEmbed' => true,
                    'source' => 'server2',
                ];
            } catch (\Exception $e) {
                Log::warning("RebahinService::getMovieDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Resolves HLS stream for Rebahin video through Nginx stream relay
     */
    public static function resolveStream(string $input): ?array
    {
        $cleanInput = trim($input);
        if (str_starts_with($cleanInput, 'rb-')) {
            $cleanInput = substr($cleanInput, 3);
        }

        $cacheKey = 'rb_resolved_stream_' . md5($cleanInput);
        return Cache::remember($cacheKey, 7200, function () use ($cleanInput) {
            try {
                $script = base_path('scripts/resolve_rebahin_stream.cjs');
                $process = new \Symfony\Component\Process\Process(['node', $script, $cleanInput]);
                $process->setTimeout(25);
                $process->run();

                if (!$process->isSuccessful()) {
                    Log::warning("RebahinService::resolveStream failed for {$cleanInput}: " . $process->getErrorOutput());
                    return null;
                }

                $data = json_decode($process->getOutput(), true);
                if (!empty($data['success']) && !empty($data['video_url'])) {
                    return [
                        'video_url' => $data['video_url'],
                        'raw_url' => $data['raw_url'] ?? null,
                        'type' => 'm3u8',
                        'subtitles' => [],
                        'image' => $data['image'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("RebahinService::resolveStream exception: " . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Detail serial TV beserta episode
     */
    public static function getSeriesDetail(string $slug): ?array
    {
        $rawSlug = preg_replace('/^rb-/', '', $slug);
        $cacheKey = "rb_series_detail_" . md5($rawSlug);

        return Cache::remember($cacheKey, 3600, function () use ($rawSlug, $slug) {
            try {
                $client = self::getClient();

                // Halaman watch untuk episode & server
                $watchUrl = self::$baseUrl . "/series/{$rawSlug}/watch/";
                $resWatch = $client->get($watchUrl);
                $htmlWatch = (string) $resWatch->getBody();

                // Halaman utama serial
                $mainUrl = self::$baseUrl . "/series/{$rawSlug}/";
                $htmlMain = '';
                try {
                    $resMain = $client->get($mainUrl);
                    $htmlMain = (string) $resMain->getBody();
                } catch (\Exception $e) {
                    $htmlMain = $htmlWatch;
                }

                // Parse title
                preg_match("/<meta[^>]*property=[\x27\x22]og:title[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain ?: $htmlWatch, $t);
                $rawTitle = $t[1] ?? '';
                $cleanTitle = trim(preg_replace('/^(Nonton\s+(Series|Film)?\s*)/i', '', $rawTitle));
                $cleanTitle = trim(preg_replace('/\s*(Sub\s+Indo)?\s*(\|\s*REBAHIN)?$/i', '', $cleanTitle));
                if (empty($cleanTitle)) $cleanTitle = ucwords(str_replace('-', ' ', $rawSlug));

                // Poster & backdrop
                preg_match("/<meta[^>]*property=[\x27\x22]og:image[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain ?: $htmlWatch, $p);
                $poster = $p[1] ?? '';
                if ($poster && !str_starts_with($poster, 'http')) $poster = self::$baseUrl . '/' . ltrim($poster, '/');

                preg_match("/class=[\x27\x22][^\x27\x22]*thumb mvi-cover[^\x27\x22]*[\x27\x22][^>]*style=[\x27\x22][^>]*url\(([^)]+)\)/i", $htmlMain, $bd);
                $backdrop = $bd[1] ?? $poster;
                if ($backdrop && !str_starts_with($backdrop, 'http')) $backdrop = self::$baseUrl . '/' . ltrim($backdrop, '/');

                // Synopsis
                $synopsis = '';
                preg_match("/<span itemprop=[\x27\x22]reviewBody[\x27\x22]>.*?<p>(.*?)<\/p>/s", $htmlMain, $syn);
                if (!empty($syn[1])) {
                    $synopsis = trim(strip_tags($syn[1]));
                    $synopsis = preg_replace('/^(Nonton\s+Film\s+.*?--\s*)/i', '', $synopsis);
                    $synopsis = preg_replace('/^(Nonton\s+Film\s+.*?&#8211;\s*)/i', '', $synopsis);
                }

                // Rating & release
                preg_match("/class=[\x27\x22]irank-voters[\x27\x22][^>]*>([\d\.]+)/", $htmlMain ?: $htmlWatch, $rt);
                $rating = !empty($rt[1]) ? (float)$rt[1] : 7.5;

                preg_match("/Release Date:<\/strong>[^<]*<meta itemprop=[\x27\x22]datePublished[\x27\x22]\s*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $htmlMain, $rd);
                $releaseDate = $rd[1] ?? '';
                $year = $releaseDate ? substr($releaseDate, 0, 4) : '';

                // Genres
                preg_match_all("/<a href=[\x27\x22][^\x27\x22]*\/genre\/[^\x27\x22]+[\x27\x22][^>]*><span itemprop=[\x27\x22]genre[\x27\x22]>([^<]+)<\/span><\/a>/i", $htmlMain, $gn);
                $genres = array_values(array_unique($gn[1] ?? []));

                // Parse episodes & servers
                preg_match_all("/<a[^>]*class=[\x27\x22]btn-eps[\x27\x22][^>]*>(.*?)<\/a>/s", $htmlWatch, $epsLinks);
                $episodes = [];
                foreach ($epsLinks[0] as $idx => $linkHtml) {
                    preg_match("/data-iframe=[\x27\x22]([^\x27\x22]+)[\x27\x22]/", $linkHtml, $d);
                    $epText = trim(strip_tags($epsLinks[1][$idx]));
                    $embedUrl = !empty($d[1]) ? base64_decode($d[1]) : "";
                    
                    if (preg_match("/Ep(?:isode)?\s*(\d+)/i", $epText, $epNumMatch)) {
                        $epNum = (int)$epNumMatch[1];
                    } else {
                        $epNum = $idx + 1;
                    }

                    if (!isset($episodes[$epNum])) {
                        $episodes[$epNum] = [
                            'episodeNumber' => $epNum,
                            'name' => "Episode {$epNum}",
                            'overview' => $synopsis,
                            'stillPath' => $backdrop,
                            'runtime' => null,
                            'servers' => [],
                        ];
                    }

                    if ($embedUrl && str_starts_with($embedUrl, 'http')) {
                        if (!isset($episodes[$epNum]['id'])) {
                            $episodes[$epNum]['id'] = 'rb-' . $embedUrl;
                        }
                        $sNum = count($episodes[$epNum]['servers']) + 1;
                        $label = $sNum === 1 ? 'Server 1 (HD)' : ($sNum === 2 ? 'Server 2 (Mirror)' : "Server {$sNum}");
                        $wrappedUrl = url('/api/streaming/embed?url=' . urlencode($embedUrl));
                        $episodes[$epNum]['servers'][] = [
                            'id' => "server{$sNum}",
                            'name' => $label,
                            'embed_url' => $wrappedUrl,
                        ];
                    }
                }

                ksort($episodes);
                $episodesList = array_values($episodes);

                return [
                    'id' => 'rb-' . $rawSlug,
                    'title' => $cleanTitle,
                    'slug' => 'rb-' . $rawSlug,
                    'raw_slug' => $rawSlug,
                    'overview' => $synopsis,
                    'poster' => $poster,
                    'backdrop' => $backdrop,
                    'posterPath' => $poster,
                    'backdropPath' => $backdrop,
                    'voteAverage' => $rating,
                    'firstAirDate' => $releaseDate,
                    'year' => $year,
                    'genres' => array_map(fn($g) => ['name' => $g], $genres),
                    'seasons' => [
                        [
                            'seasonNumber' => 1,
                            'name' => 'Season 1',
                            'episodeCount' => count($episodesList),
                            'posterPath' => $poster,
                        ]
                    ],
                    'defaultSeason' => [
                        'seasonNumber' => 1,
                        'name' => 'Season 1',
                        'episodes' => $episodesList,
                    ],
                    'isEmbed' => true,
                    'source' => 'server2',
                ];
            } catch (\Exception $e) {
                Log::warning("RebahinService::getSeriesDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Cari mirror untuk film IDLIX (berdasarkan judul & tahun)
     */
    public static function findMovieMirror(string $title, ?string $year = null): ?array
    {
        $cleanTitle = preg_replace('/\s*\(\d{4}\)$/', '', trim($title));
        $cacheKey = 'rb_mirror_m_' . md5(strtolower($cleanTitle . '_' . $year));

        return Cache::remember($cacheKey, 86400, function () use ($cleanTitle, $year) {
            $results = self::search($cleanTitle);
            $normSearch = strtolower(preg_replace('/[^a-z0-9]/', '', $cleanTitle));

            foreach ($results as $item) {
                if (($item['type'] ?? '') !== 'movie') continue;
                $normItem = strtolower(preg_replace('/[^a-z0-9]/', '', $item['title'] ?? ''));

                if ($normSearch === $normItem || str_contains($normItem, $normSearch)) {
                    if ($year && !empty($item['year']) && abs((int)$year - (int)$item['year']) > 1) {
                        continue;
                    }
                    return self::getMovieDetail($item['raw_slug']);
                }
            }
            return null;
        });
    }

    /**
     * Cari mirror untuk serial TV IDLIX (berdasarkan judul & tahun)
     */
    public static function findSeriesMirror(string $title, ?string $year = null): ?array
    {
        $cleanTitle = preg_replace('/\s*\(\d{4}\)$/', '', trim($title));
        $cacheKey = 'rb_mirror_s_' . md5(strtolower($cleanTitle . '_' . $year));

        return Cache::remember($cacheKey, 86400, function () use ($cleanTitle, $year) {
            $results = self::search($cleanTitle);
            $normSearch = strtolower(preg_replace('/[^a-z0-9]/', '', $cleanTitle));

            foreach ($results as $item) {
                if (($item['type'] ?? '') !== 'series') continue;
                $normItem = strtolower(preg_replace('/[^a-z0-9]/', '', $item['title'] ?? ''));

                if ($normSearch === $normItem || str_contains($normItem, $normSearch)) {
                    return self::getSeriesDetail($item['raw_slug']);
                }
            }
            return null;
        });
    }

    /**
     * Parser kartu .ml-item
     */
    protected static function parseCards(string $html, ?string $forcedType = null): array
    {
        preg_match_all("/<div[^>]*data-movie-id=[\x27\x22](\d+)[\x27\x22][^>]*class=[\x27\x22]ml-item[\x27\x22].*?<\/a><\/div>/s", $html, $matches);
        $cards = [];

        foreach ($matches[0] as $cardHtml) {
            preg_match("/href=[\x27\x22]([^\x27\x22]+)[\x27\x22]/", $cardHtml, $h);
            preg_match("/title=[\x27\x22]([^\x27\x22]+)[\x27\x22]/", $cardHtml, $t);
            preg_match("/(?:data-original|src)=[\x27\x22](https?:\/\/[^\x27\x22]+)[\x27\x22]/i", $cardHtml, $im);
            preg_match("/class=[\x27\x22]mli-quality[^'\"]*[\x27\x22]>([^<]+)<\/span>/", $cardHtml, $q);
            preg_match("/class=[\x27\x22]mli-rating[\x27\x22]>[^<]*<i[^>]*><\/i>\s*([\d\.]+)/s", $cardHtml, $rt);
            preg_match("/class=[\x27\x22]mli-durasi[\x27\x22]>.*?([\d]+).*?<\/span>/s", $cardHtml, $dr);

            $href = $h[1] ?? '';
            $titleRaw = $t[1] ?? '';
            if (empty($href) || empty($titleRaw)) continue;

            // Tentukan tipe
            $isSeries = str_contains($href, '/series/') || ($forcedType === 'series');
            $type = $isSeries ? 'series' : 'movie';

            // Raw slug
            $path = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');
            $parts = explode('/', $path);
            $rawSlug = end($parts);
            if (empty($rawSlug)) continue;

            // Ekstrak tahun dari judul (termasuk kurung unicode/fullwidth)
            $year = '';
            if (preg_match('/[\(（](\d{4})[\)）]/u', $titleRaw, $ym)) {
                $year = $ym[1];
            }

            // Bersihkan judul
            $cleanTitle = trim(preg_replace('/\s*[\(（]\d{4}[\)）]\s*$/u', '', $titleRaw));

            // Poster image via internal proxy
            $imgSrc = $im[1] ?? '';
            if ($imgSrc && !str_starts_with($imgSrc, 'http')) {
                $imgSrc = self::$baseUrl . '/' . ltrim($imgSrc, '/');
            }
            $posterProxy = $imgSrc ? '/api/proxy-image?url=' . urlencode($imgSrc) : null;

            $ratingVal = !empty($rt[1]) ? (float)$rt[1] : null;
            if ($ratingVal && $ratingVal > 10) {
                $ratingVal = round($ratingVal / 10, 1);
            }

            $cards[] = [
                'id' => 'rb-' . $rawSlug,
                'title' => $cleanTitle,
                'slug' => 'rb-' . $rawSlug,
                'raw_slug' => $rawSlug,
                'type' => $type,
                'year' => $year,
                'rating' => $ratingVal,
                'voteAverage' => $ratingVal,
                'quality' => strtoupper(trim($q[1] ?? 'HD')),
                'duration' => !empty($dr[1]) ? (int)$dr[1] : null,
                'runtime' => !empty($dr[1]) ? (int)$dr[1] : null,
                'poster' => $posterProxy ?: $imgSrc,
                'thumbnail' => $posterProxy ?: $imgSrc,
                'banner' => $posterProxy ?: $imgSrc,
                'isEmbed' => true,
                'source' => 'server2',
            ];
        }

        return $cards;
    }
}
