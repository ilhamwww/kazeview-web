<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NobarLk21Service
{
    protected static string $defaultDomain = 'https://t4.nobarlk21.com';

    /**
     * Dapatkan base URL aktif dengan fallback
     */
    public static function getBaseUrl(): string
    {
        return Cache::remember('nobar_lk21_active_domain', 3600, function () {
            // Bisa diperluas auto-probing subdomain bila diperlukan
            return config('services.nobarlk21.base_url', self::$defaultDomain);
        });
    }

    /**
     * Ambil katalog film / serial Indonesia dari Nobar LK21
     * Mengambil dari endpoint API internal Next.js:
     * 1) /api/v1/contents?type=movie&country=indonesia&page={page}
     * 2) /api/v1/contents?type=series&country=indonesia&page={page}
     * Dengan fallback scraping HTML SSR jika API tidak merespons.
     */
    public static function getCatalog(string $category = 'all', int $page = 1, string $search = ''): array
    {
        $page = max(1, $page);
        $cacheKey = "nobar_lk21_catalog_v3_cat_{$category}_p{$page}_s_" . md5($search);

        return Cache::remember($cacheKey, 300, function () use ($category, $page, $search) {
            try {
                $baseUrl = self::getBaseUrl();
                $items = [];
                $hasMore = false;
                $totalPages = 1;

                // 1. Jika pencarian aktif
                if (!empty($search)) {
                    $apiUrl = "{$baseUrl}/api/v1/contents?search=" . urlencode($search) . "&page={$page}";
                    $res = Http::withHeaders(self::getApiHeaders())->timeout(10)->get($apiUrl);
                    if ($res->successful()) {
                        $json = $res->json();
                        $items = self::mapApiItems($json['data'] ?? []);
                        $hasMore = (bool) ($json['meta']['hasNextPage'] ?? false);
                        $totalPages = (int) ($json['meta']['totalPages'] ?? 1);
                    }
                }
                // 2. Jika category spesifik 'movie'
                elseif ($category === 'movie') {
                    $apiUrl = "{$baseUrl}/api/v1/contents?type=movie&country=indonesia&sort=popular&page={$page}";
                    $res = Http::withHeaders(self::getApiHeaders())->timeout(10)->get($apiUrl);
                    if ($res->successful()) {
                        $json = $res->json();
                        $items = self::mapApiItems($json['data'] ?? [], 'movie');
                        $hasMore = (bool) ($json['meta']['hasNextPage'] ?? false);
                        $totalPages = (int) ($json['meta']['totalPages'] ?? 1);
                    }
                }
                // 3. Jika category spesifik 'series'
                elseif ($category === 'series') {
                    $apiUrl = "{$baseUrl}/api/v1/contents?type=series&country=indonesia&sort=popular&page={$page}";
                    $res = Http::withHeaders(self::getApiHeaders())->timeout(10)->get($apiUrl);
                    if ($res->successful()) {
                        $json = $res->json();
                        $items = self::mapApiItems($json['data'] ?? [], 'series');
                        $hasMore = (bool) ($json['meta']['hasNextPage'] ?? false);
                        $totalPages = (int) ($json['meta']['totalPages'] ?? 1);
                    }
                }
                // 4. Category 'all' atau filter tahun
                else {
                    $responses = Http::pool(fn ($pool) => [
                        $pool->as('movie')->withHeaders(self::getApiHeaders())->timeout(10)->get("{$baseUrl}/api/v1/contents?type=movie&country=indonesia&sort=popular&page={$page}"),
                        $pool->as('series')->withHeaders(self::getApiHeaders())->timeout(10)->get("{$baseUrl}/api/v1/contents?type=series&country=indonesia&sort=popular&page={$page}"),
                    ]);

                    $movieJson = $responses['movie']->successful() ? $responses['movie']->json() : null;
                    $seriesJson = $responses['series']->successful() ? $responses['series']->json() : null;

                    $movieItems = self::mapApiItems($movieJson['data'] ?? [], 'movie');
                    $seriesItems = self::mapApiItems($seriesJson['data'] ?? [], 'series');

                    $items = array_merge($movieItems, $seriesItems);

                    $hasMore = (bool) (($movieJson['meta']['hasNextPage'] ?? false) || ($seriesJson['meta']['hasNextPage'] ?? false));
                    $totalPages = max(
                        (int) ($movieJson['meta']['totalPages'] ?? 1),
                        (int) ($seriesJson['meta']['totalPages'] ?? 1)
                    );
                }

                // Fallback ke SSR HTML jika API kosong di page 1
                if (empty($items) && $page === 1) {
                    $ssrRes = Http::withHeaders(self::getHeaders())->timeout(12)->get("{$baseUrl}/movie?country=indonesia");
                    if ($ssrRes->successful()) {
                        $items = self::extractItemsFromHtml($ssrRes->body(), 'movie');
                        $hasMore = false;
                        $totalPages = 1;
                    }
                }

                // Filter tahun jika spesifik
                if (in_array($category, ['2026', '2025', '2024'], true)) {
                    $items = array_values(array_filter($items, fn($it) => ($it['year'] ?? '') === $category));
                } elseif ($category === 'classic') {
                    $items = array_values(array_filter($items, fn($it) => (int) ($it['year'] ?? 0) <= 2023));
                }

                return [
                    'data' => $items,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => $totalPages,
                        'totalItems' => count($items),
                        'hasMore' => $hasMore,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::error("[NobarLk21Service] getCatalog error: " . $e->getMessage());
                return [
                    'data' => [],
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => 1,
                        'totalItems' => 0,
                        'hasMore' => false,
                    ],
                ];
            }
        });
    }

    protected static function getApiHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept' => 'application/json',
            'Referer' => 'https://t4.nobarlk21.com/movie?country=indonesia',
        ];
    }

    /**
     * Map hasil API /api/v1/contents ke format kartu KZSTRM
     */
    protected static function mapApiItems(array $rawItems, string $forceType = ''): array
    {
        $items = [];
        foreach ($rawItems as $item) {
            $rawSlug = $item['slug'] ?? '';
            if (empty($rawSlug)) {
                continue;
            }

            $year = (string) ($item['year'] ?? '');
            $title = $item['title'] ?? 'Konten Indonesia';
            $cleanTitle = trim(preg_replace('/\[indonesian\]/i', '', $title));

            $type = !empty($forceType) ? $forceType : (($item['type'] ?? 'movie') === 'series' ? 'series' : 'movie');

            $items[] = [
                'id' => 'lk-' . $rawSlug,
                'title' => $cleanTitle,
                'slug' => 'lk-' . $rawSlug,
                'raw_slug' => $rawSlug,
                'target_slug' => $rawSlug,
                'type' => $type,
                'year' => $year,
                'rating' => (float) ($item['rating'] ?? 7.5),
                'voteAverage' => (float) ($item['rating'] ?? 7.5),
                'quality' => $item['quality'] ?? 'HD',
                'duration' => null,
                'runtime' => null,
                'poster' => $item['posterUrl'] ?? null,
                'thumbnail' => $item['posterUrl'] ?? null,
                'banner' => $item['posterUrl'] ?? null,
                'isEmbed' => false,
                'source' => 'lk21',
                'provider_badge' => 'LK21',
                'genres' => ['Indonesia', ucfirst($type)],
            ];
        }
        return $items;
    }

    protected static function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'id,en-US;q=0.9,en;q=0.8',
        ];
    }

    /**
     * Parse items dari payload Next.js SSR
     */
    protected static function extractItemsFromHtml(string $html, string $forceType = 'movie'): array
    {
        $items = [];

        if (preg_match('/(\[{\\\"id\\\":\\\"\d+\\\",\\\"slug\\\":.*?\}\])/', $html, $matches)) {
            $raw = $matches[1];
            $clean = stripcslashes($raw);
            $parsed = json_decode($clean, true);

            if (is_array($parsed)) {
                foreach ($parsed as $item) {
                    $rawSlug = $item['slug'] ?? '';
                    if (empty($rawSlug)) {
                        continue;
                    }

                    $year = (string) ($item['year'] ?? '');
                    $title = $item['title'] ?? 'Konten Indonesia';
                    $cleanTitle = trim(preg_replace('/\[indonesian\]/i', '', $title));

                    $itemType = $forceType !== 'all' ? $forceType : (($item['type'] ?? 'movie') === 'series' ? 'series' : 'movie');

                    $items[] = [
                        'id' => 'lk-' . $rawSlug,
                        'title' => $cleanTitle,
                        'slug' => 'lk-' . $rawSlug,
                        'raw_slug' => $rawSlug,
                        'target_slug' => $rawSlug,
                        'type' => $itemType,
                        'year' => $year,
                        'rating' => (float) ($item['rating'] ?? 7.5),
                        'voteAverage' => (float) ($item['rating'] ?? 7.5),
                        'quality' => $item['quality'] ?? 'HD',
                        'duration' => null,
                        'runtime' => null,
                        'poster' => $item['posterUrl'] ?? null,
                        'thumbnail' => $item['posterUrl'] ?? null,
                        'banner' => $item['posterUrl'] ?? null,
                        'isEmbed' => false,
                        'source' => 'lk21',
                        'provider_badge' => 'LK21',
                        'genres' => ['Indonesia', ucfirst($itemType)],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Ambil detail film / series & direct streams dari LK21
     */
    public static function getDetail(string $slug, int $episode = 1): ?array
    {
        $rawSlug = preg_replace('/^(?:lk-|rb-|mb-)/', '', $slug);
        $cacheKey = "nobar_lk21_detail_{$rawSlug}_ep_{$episode}";

        return Cache::remember($cacheKey, 600, function () use ($rawSlug, $episode) {
            try {
                $baseUrl = self::getBaseUrl();
                // Halaman tonton di LK21: /watch/{slug}?episode={ep} jika episode > 1
                $url = "{$baseUrl}/watch/{$rawSlug}" . ($episode > 1 ? "?season=1&episode={$episode}" : "");

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Referer' => "{$baseUrl}/movie/{$rawSlug}",
                ])->timeout(12)->get($url);

                if (!$response->successful()) {
                    return null;
                }

                $html = $response->body();

                // 1. Ekstrak judul
                $title = 'Film Indonesia';
                if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $tm)) {
                    $title = trim(strip_tags($tm[1]));
                }

                // 2. Ekstrak Next.js payloads
                preg_match_all('/self\.__next_f\.push\(\[1,\s*"(.*?)"\]\)/', $html, $pushes);
                $fullPayload = '';
                if (!empty($pushes[1])) {
                    $fullPayload = implode('', $pushes[1]);
                    $fullPayload = str_replace(['\\"', '\\\\'], ['"', '\\'], $fullPayload);
                }

                // 3. Ekstrak sinopsis
                $overview = '';
                if (preg_match('/"(?:synopsis|overview|description)":\s*"(.*?)"/', $fullPayload, $sm)) {
                    $overview = stripcslashes($sm[1]);
                }

                // 4. Ekstrak poster / backdrop
                $poster = null;
                if (preg_match('/"(?:posterUrl|poster_path|poster)":\s*"(https?:\/\/[^"]+)"/', $fullPayload, $pm)) {
                    $poster = $pm[1];
                }

                // 5. Ekstrak episode list jika series
                $episodes = [];
                if (preg_match_all('/\/watch\/[a-zA-Z0-9_-]+\?season=\d+&(?:amp;)?episode=(\d+)/', $html, $em)) {
                    $foundEpNumbers = array_unique(array_map('intval', $em[1]));
                    sort($foundEpNumbers);
                    foreach ($foundEpNumbers as $epNum) {
                        $episodes[] = [
                            'episode_number' => $epNum,
                            'episodeNumber' => $epNum,
                            'title' => "Episode {$epNum}",
                            'name' => "Episode {$epNum}",
                            'stillPath' => $poster,
                        ];
                    }
                }

                $isSeries = count($episodes) > 0;

                // 6. Ekstrak streaming sources
                $servers = [];
                // Prioritas 1: Ekstrak dari objek JSON "sources":[{"id":"source_1",...,"url":"https://..."}]
                $cleanedHtml = str_replace(['\\"', '\\\\'], ['"', '\\'], $html);
                if (preg_match('/"sources":\s*(\[\{.*?\}\])/s', $cleanedHtml, $sourceMatch)) {
                    $sourcesJson = json_decode($sourceMatch[1], true);
                    if (is_array($sourcesJson)) {
                        $idx = 1;
                        foreach ($sourcesJson as $src) {
                            $streamUrl = $src['url'] ?? '';
                            if (!empty($streamUrl) && (str_contains($streamUrl, 'cdn-proxy') || str_contains($streamUrl, '.mp4'))) {
                                $proxyUrl = url('/api/proxy-stream?url=' . urlencode($streamUrl));
                                $servers[] = [
                                    'id' => 'server_lk_' . $idx,
                                    'name' => 'Server ' . $idx . ' (Direct ' . ($src['label'] ?? '1080p HD') . ')',
                                    'type' => 'mp4',
                                    'video_url' => $proxyUrl,
                                    'raw_video_url' => $streamUrl,
                                    'embed_url' => '',
                                ];
                                $idx++;
                            }
                        }
                    }
                }

                // Prioritas 2 (Fallback): Regex jika objek sources tidak ditemukan
                if (empty($servers)) {
                    if (preg_match_all('/https?:\/\/[^\s"\'\\|]+?(?:bcdnxw|cdn-proxy)[^\s"\'\\|]+?\.mp4%3Fsign%3D[a-f0-9]{32}%26t%3D\d+/', $fullPayload, $sm)) {
                        $rawUrls = array_unique($sm[0]);
                        $idx = 1;
                        foreach ($rawUrls as $streamUrl) {
                            $proxyUrl = url('/api/proxy-stream?url=' . urlencode($streamUrl));
                            $servers[] = [
                                'id' => 'server_lk_' . $idx,
                                'name' => 'Server ' . $idx . ' (Direct 1080p HD)',
                                'type' => 'mp4',
                                'video_url' => $proxyUrl,
                                'raw_video_url' => $streamUrl,
                                'embed_url' => '',
                            ];
                            $idx++;
                        }
                    }
                }

                if (empty($servers)) {
                    return null;
                }

                $firstServer = $servers[0];

                return [
                    'id' => 'lk-' . $rawSlug,
                    'title' => $title,
                    'slug' => 'lk-' . $rawSlug,
                    'raw_slug' => $rawSlug,
                    'type' => $isSeries ? 'series' : 'movie',
                    'year' => date('Y'),
                    'overview' => $overview,
                    'synopsis' => $overview,
                    'poster' => $poster,
                    'backdrop' => $poster,
                    'banner' => $poster,
                    'thumbnail' => $poster,
                    'rating' => 7.5,
                    'voteAverage' => 7.5,
                    'genres' => ['Indonesia', $isSeries ? 'Series' : 'Film'],
                    'video_url' => $firstServer['video_url'] ?? '',
                    'servers' => $servers,
                    'episodes' => $episodes,
                    'isEmbed' => false,
                    'quality' => '1080p Ultra HD',
                    'provider' => 'lk21',
                    'provider_label' => 'Layarkaca21',
                ];
            } catch (\Throwable $e) {
                Log::error("[NobarLk21Service] getDetail error: " . $e->getMessage());
                return null;
            }
        });
    }
}
