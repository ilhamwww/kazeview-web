<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoujindesuScraperService
{
    protected string $baseUrl = 'https://doujin.desu.xxx';
    protected string $apiUrl = 'https://doujin.desu.xxx/api';
    protected string $salt = 'doujindesu-scrapers-cannot-read-this-super-secret-salt-2026-v2';
    protected int $mo = 3600000;
    protected string $appSecret = 'dfdf72051dbfdc7d76889ebd31324e74';

    /**
     * Generate dynamic rolling key from timestamp
     */
    protected function rn(int $t): string
    {
        $n = $this->salt . "_" . $t;
        $r = 0;
        $len = strlen($n);
        for ($l = 0; $l < $len; $l++) {
            $r = ($r << 5) - $r + ord($n[$l]);
            $r = (int)($r & 0xFFFFFFFF);
            if ($r & 0x80000000) {
                $r = -((~$r & 0xFFFFFFFF) + 1);
            }
        }
        $c = abs($r) ?: 123456789;
        $i = "";
        for ($l = 0; $l < 32; $l++) {
            $c = fmod(($c * 1664525 + 1013904223), 4294967296);
            $i .= chr(33 + ((int)$c % 93));
        }
        return $i;
    }

    /**
     * Custom XOR decipher matching upstream JavaScript
     */
    protected function eo(string $hex, string $key): string
    {
        $r = [];
        $hexLen = strlen($hex);
        for ($x = 0; $x < $hexLen; $x += 2) {
            $v = substr($hex, $x, 2);
            if ($v === false || $v === '') {
                break;
            }
            $r[] = hexdec($v);
        }

        $i = '';
        $keyLen = strlen($key);
        $l = 42;
        $rCount = count($r);
        for ($x = 0; $x < $rCount; $x++) {
            $v = $r[$x];
            $j = ord($key[$x % $keyLen]);
            $k = ($v ^ $j ^ ($x * 13) ^ $l) & 255;
            $i .= chr($k);
            $l = ($l + $v) % 256;
        }

        return $i;
    }

    /**
     * Decrypt upstream _enc_resp_
     */
    public function decryptPayload(string $hex): ?array
    {
        $timeMs = round(microtime(true) * 1000);
        $n = floor($timeMs / $this->mo);
        $keys = [
            $this->rn((int)$n),
            $this->rn((int)$n - 1),
            $this->rn((int)$n + 1),
        ];

        foreach ($keys as $key) {
            try {
                $raw = $this->eo($hex, $key);
                $dec = urldecode($raw);
                $json = json_decode($dec, true);
                if ($json !== null) {
                    return $json;
                }
            } catch (\Throwable $e) {
                // Try next key
            }
        }

        return null;
    }

    /**
     * Fetch API with secret headers and auto-decrypt
     */
    public function fetchApi(string $endpoint, array $queryParams = []): ?array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        $deviceId = 'dev_' . substr(md5($endpoint . serialize($queryParams)), 0, 12) . '_' . base_convert(time(), 10, 36);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Referer' => $this->baseUrl . '/',
                'Origin' => $this->baseUrl,
                'x-app-secret' => $this->appSecret,
                'X-App-Secret' => $this->appSecret,
                'x-device-id' => $deviceId,
                'x-device-name' => 'Chrome/124.0.0.0',
            ])->timeout(15)->get($url, $queryParams);

            if (!$response->successful()) {
                Log::warning("Doujindesu API error on {$endpoint}: " . $response->status());
                return null;
            }

            $body = $response->json();
            if (isset($body['_enc_resp_'])) {
                return $this->decryptPayload($body['_enc_resp_']);
            }

            return $body;
        } catch (\Throwable $e) {
            Log::error("Doujindesu API fetch exception on {$endpoint}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Banners for Slider
     */
    public function getBanners(): array
    {
        return Cache::remember('doujin_banners', 1800, function () {
            $data = $this->fetchApi('/banners');
            if (!is_array($data)) return [];

            $clean = [];
            foreach ($data as $b) {
                if (empty($b['image_url'])) continue;
                $slug = $b['manga_slug'] ?? '';
                if (empty($slug) && !empty($b['link_url'])) {
                    $slug = basename(trim($b['link_url'], '/'));
                }

                $clean[] = [
                    'id' => $b['id'] ?? '',
                    'title' => $b['title'] ?? ($b['manga_title'] ?? 'Doujin Title'),
                    'slug' => $slug,
                    'image' => $b['image_url'],
                    'type' => $b['manga_type'] ?? 'manhwa',
                    'rating' => $b['manga_rating'] ?? '8.5',
                    'description' => $b['manga_description'] ?? '',
                ];
            }
            return $clean;
        });
    }

    /**
     * Get All Genres
     */
    public function getGenres(): array
    {
        return Cache::remember('doujin_genres', 86400, function () {
            $data = $this->fetchApi('/genres');
            if (!is_array($data)) return [];

            $genres = [];
            foreach ($data as $g) {
                $name = trim($g['name'] ?? '', " \t\n\r\0\x0B\"'");
                $slug = $g['slug'] ?? '';
                $count = $g['manga_count'] ?? ($g['_count']['manga_genres'] ?? 0);
                if (!empty($slug) && !empty($name)) {
                    $genres[] = [
                        'id' => $g['id'] ?? '',
                        'name' => $name,
                        'slug' => $slug,
                        'count' => $count,
                    ];
                }
            }

            // Urutkan berdasarkan popularitas / jumlah karya
            usort($genres, fn($a, $b) => $b['count'] <=> $a['count']);
            return $genres;
        });
    }

    /**
     * Get Manga Catalog List with pagination, type, sort, and multiple genres
     */
    public function getMangaList(
        int $page = 1,
        string $type = 'all',
        string $sort = 'latest_chapter',
        array $genres = [],
        string $search = '',
        int $limit = 24
    ): array {
        $offset = max(0, ($page - 1) * $limit);
        $params = [
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
        ];

        if ($type !== 'all' && !empty($type)) {
            $params['type'] = $type;
        }

        if (!empty($genres)) {
            $params['genres'] = implode(',', array_filter($genres));
        }

        if (!empty($search)) {
            $params['search'] = trim($search);
        }

        $cacheKey = 'doujin_list_' . md5(serialize($params));

        return Cache::remember($cacheKey, 600, function () use ($params) {
            $data = $this->fetchApi('/manga', $params);
            if (!is_array($data)) return ['items' => [], 'has_next' => false];

            $items = [];
            foreach ($data as $m) {
                $slug = $m['slug'] ?? '';
                if (empty($slug)) continue;

                $genresList = [];
                if (!empty($m['manga_genres']) && is_array($m['manga_genres'])) {
                    foreach ($m['manga_genres'] as $mg) {
                        if (!empty($mg['genres']['name'])) {
                            $genresList[] = $mg['genres']['name'];
                        }
                    }
                }

                $latestChapter = null;
                if (!empty($m['chapters'][0]['chapter_number'])) {
                    $latestChapter = (string)$m['chapters'][0]['chapter_number'];
                }

                $rawCover = $m['cover_url'] ?? '';
                $coverUrl = !empty($rawCover) ? url('/api/proxy-image?url=' . urlencode($rawCover)) : '';

                $items[] = [
                    'id' => $m['id'] ?? '',
                    'title' => $m['title'] ?? 'Untitled',
                    'slug' => $slug,
                    'cover' => $coverUrl,
                    'raw_cover' => $rawCover,
                    'type' => $m['type'] ?? 'doujinshi',
                    'status' => $m['status'] ?? 'ongoing',
                    'rating' => $m['rating'] ?? '0.0',
                    'views' => $m['views'] ?? 0,
                    'chapter_count' => $m['chapter_count'] ?? count($m['chapters'] ?? []),
                    'latest_chapter' => $latestChapter,
                    'genres' => array_slice($genresList, 0, 3),
                ];
            }

            return [
                'items' => $items,
                'has_next' => count($items) >= ($params['limit'] ?? 24),
            ];
        });
    }

    /**
     * Get Manga Detail & Full Chapters List
     */
    public function getMangaDetail(string $slug): ?array
    {
        $cacheKey = 'doujin_detail_' . md5($slug);

        return Cache::remember($cacheKey, 1800, function () use ($slug) {
            $data = $this->fetchApi("/manga/{$slug}");
            if (empty($data) || empty($data['id'])) {
                return null;
            }

            // Extract genres
            $genres = [];
            if (!empty($data['manga_genres']) && is_array($data['manga_genres'])) {
                foreach ($data['manga_genres'] as $mg) {
                    if (!empty($mg['genres'])) {
                        $genres[] = [
                            'name' => $mg['genres']['name'] ?? '',
                            'slug' => $mg['genres']['slug'] ?? '',
                        ];
                    }
                }
            }

            // Extract authors & serialization
            $authors = [];
            if (!empty($data['manga_authors'])) {
                foreach ($data['manga_authors'] as $ma) {
                    if (!empty($ma['authors']['name'])) {
                        $authors[] = $ma['authors']['name'];
                    }
                }
            }

            // Extract chapters
            $chapters = [];
            if (!empty($data['chapters']) && is_array($data['chapters'])) {
                foreach ($data['chapters'] as $ch) {
                    $chNum = (string)($ch['chapter_number'] ?? '');
                    if ($chNum === '') continue;

                    $chapters[] = [
                        'id' => $ch['id'] ?? '',
                        'chapter_number' => $chNum,
                        'title' => $ch['title'] ?? "Chapter {$chNum}",
                        'created_at' => !empty($ch['created_at']) ? date('d M Y', strtotime($ch['created_at'])) : '',
                        'views' => $ch['views'] ?? 0,
                    ];
                }
            }

            $rawCover = $data['cover_url'] ?? '';
            $rawBanner = $data['banner_url'] ?? ($data['cover_url'] ?? '');
            $coverUrl = !empty($rawCover) ? url('/api/proxy-image?url=' . urlencode($rawCover)) : '';
            $bannerUrl = !empty($rawBanner) ? url('/api/proxy-image?url=' . urlencode($rawBanner)) : '';

            return [
                'id' => $data['id'],
                'title' => $data['title'] ?? '',
                'alternative_titles' => $data['alternative_titles'] ?? '',
                'slug' => $data['slug'] ?? $slug,
                'cover' => $coverUrl,
                'banner' => $bannerUrl,
                'raw_cover' => $rawCover,
                'type' => $data['type'] ?? 'manhwa',
                'status' => $data['status'] ?? 'ongoing',
                'rating' => $data['rating'] ?? '8.5',
                'views' => $data['views'] ?? 0,
                'description' => $data['description'] ?? '',
                'authors' => $authors,
                'genres' => $genres,
                'chapters' => $chapters,
                'total_chapters' => count($chapters),
            ];
        });
    }

    /**
     * Get Chapter Images & Metadata for Reading
     */
    public function getChapterPages(string $chapterId): ?array
    {
        $cacheKey = 'doujin_chapter_' . md5($chapterId);

        return Cache::remember($cacheKey, 3600, function () use ($chapterId) {
            $data = $this->fetchApi("/chapters/{$chapterId}");
            if (empty($data) || empty($data['id'])) {
                return null;
            }

            $images = [];
            $rawImages = $data['content_urls'] ?? ($data['chapter_images'] ?? ($data['images'] ?? []));
            if (is_array($rawImages)) {
                foreach ($rawImages as $idx => $img) {
                    $imgUrl = is_string($img) ? $img : ($img['image_url'] ?? ($img['url'] ?? ''));
                    if (!empty($imgUrl)) {
                        $images[] = [
                            'page_number' => is_array($img) && isset($img['page_number']) ? $img['page_number'] : ($idx + 1),
                            'url' => url('/api/proxy-image?url=' . urlencode($imgUrl)),
                            'raw_url' => $imgUrl,
                        ];
                    }
                }
            }

            // Sort images by page number
            usort($images, fn($a, $b) => ($a['page_number'] ?? 0) <=> ($b['page_number'] ?? 0));

            return [
                'id' => $data['id'],
                'manga_id' => $data['manga_id'] ?? '',
                'manga_title' => $data['manga_title'] ?? '',
                'chapter_number' => (string)($data['chapter_number'] ?? '1'),
                'title' => $data['title'] ?? '',
                'images' => $images,
                'total_pages' => count($images),
            ];
        });
    }
}
