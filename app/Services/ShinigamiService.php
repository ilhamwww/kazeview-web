<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShinigamiService
{
    protected string $nucRelayUrl = 'http://100.110.54.105:8765/shinigami/api';
    protected string $socksProxy = 'socks5h://127.0.0.1:1055';

    /**
     * Panggil endpoint Shinigami API melalui jembatan NUC residensial Indonesia.
     */
    public function requestApi(string $endpoint, array $params = [], int $cacheSeconds = 900): ?array
    {
        $cacheKey = 'shinigami_' . md5($endpoint . '_' . serialize($params));

        // Hanya respons sukses yang di-cache; kegagalan relay tidak boleh tersimpan.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $fresh = (function () use ($endpoint, $params) {
            try {
                $queryParams = array_merge(['endpoint' => $endpoint], $params);

                $response = Http::withOptions([
                    'proxy' => $this->socksProxy,
                    'timeout' => 3, // Timeout singkat 3s agar tidak menghambat page saat NUC offline
                ])->get($this->nucRelayUrl, $queryParams);

                if ($response->successful()) {
                    $json = $response->json();
                    if (isset($json['retcode']) && $json['retcode'] === 0) {
                        return $json;
                    }
                }

                Log::warning('Shinigami API response error: ' . $response->status() . ' - ' . substr($response->body(), 0, 200));
            } catch (\Throwable $e) {
                Log::error('Shinigami requestApi exception: ' . $e->getMessage());
            }

            return null;
        })();

        if (is_array($fresh)) {
            Cache::put($cacheKey, $fresh, $cacheSeconds);
        }

        return $fresh;
    }

    /**
     * Ambil komik terpopuler (bisa difilter berdasarkan format: manhwa, manga, manhua).
     */
    public function getPopular(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $params = [
            'page' => $page,
            'page_size' => $pageSize,
            'sort' => 'popularity',
            'sort_order' => 'desc',
        ];

        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['format'] = strtolower($format);
        }

        $res = $this->requestApi('/manga/list', $params, 21600); // Cache 6 jam agar katalog super enteng & cepat
        return $this->formatComicListResponse($res, $page);
    }

    /**
     * Ambil update komik terbaru (chapter rilis terbaru).
     */
    public function getLatest(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $params = [
            'page' => $page,
            'page_size' => $pageSize,
            'sort' => 'latest',
            'sort_order' => 'desc',
        ];

        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['format'] = strtolower($format);
        }

        $res = $this->requestApi('/manga/list', $params, 1800); // Cache 30 menit
        return $this->formatComicListResponse($res, $page);
    }

    /**
     * Cari komik berdasarkan judul atau kata kunci.
     */
    public function search(string $query, int $page = 1, int $pageSize = 24): array
    {
        $params = [
            'page' => $page,
            'page_size' => $pageSize,
            'q' => $query,
        ];

        $res = $this->requestApi('/manga/list', $params, 7200); // Cache pencarian 2 jam
        return $this->formatComicListResponse($res, $page);
    }

    /**
     * Ambil detail lengkap komik beserta metadata & sinopsis.
     */
    public function getDetail(string $mangaId): ?array
    {
        $res = $this->requestApi("/manga/detail/{$mangaId}", [], 604800); // Cache detail 7 hari
        if (!$res || empty($res['data'])) {
            return null;
        }

        $d = $res['data'];
        $format = $d['taxonomy']['Format'][0]['name'] ?? 'Manhwa';
        $genres = array_map(fn($g) => $g['name'], $d['taxonomy']['Genre'] ?? []);
        $authors = array_map(fn($a) => $a['name'], $d['taxonomy']['Author'] ?? []);
        $artists = array_map(fn($a) => $a['name'], $d['taxonomy']['Artist'] ?? []);

        $rawCover = $d['cover_portrait_url'] ?: ($d['cover_image_url'] ?? '');
        $cover = $rawCover ? '/api/proxy-image?url=' . urlencode($rawCover) : '';
        $rawBanner = $d['cover_image_url'] ?: ($d['cover_portrait_url'] ?? '');
        $banner = $rawBanner ? '/api/proxy-image?url=' . urlencode($rawBanner) : '';

        return [
            'id' => $d['manga_id'] ?? $mangaId,
            'title' => $d['title'] ?? '',
            'alternative_title' => $d['alternative_title'] ?? '',
            'description' => $d['description'] ?? '',
            'cover' => $cover,
            'banner' => $banner,
            'rating' => isset($d['user_rate']) ? number_format((float) $d['user_rate'], 1) : null,
            'rank' => $d['rank'] ?? null,
            'status' => ($d['status'] ?? 1) === 1 ? 'Ongoing' : 'Completed',
            'release_year' => $d['release_year'] ?? '',
            'country_id' => $d['country_id'] ?? 'KR',
            'format' => $format,
            'genres' => $genres,
            'authors' => $authors,
            'artists' => $artists,
            'view_count' => $d['view_count'] ?? 0,
            'bookmark_count' => $d['bookmark_count'] ?? 0,
            'latest_chapter_number' => $d['latest_chapter_number'] ?? null,
            'latest_chapter_id' => $d['latest_chapter_id'] ?? null,
        ];
    }

    /**
     * Ambil seluruh daftar chapter dari komik.
     */
    public function getChapters(string $mangaId): array
    {
        $res = $this->requestApi("/chapter/{$mangaId}/list", ['page_size' => 1000], 7200); // Cache daftar chapter 2 jam
        if (!$res || empty($res['data'])) {
            return [];
        }

        $list = [];
        foreach ($res['data'] as $c) {
            $rawThumb = $c['thumbnail_image_url'] ?? '';
            $thumb = $rawThumb ? '/api/proxy-image?url=' . urlencode($rawThumb) : '';

            $num = $c['chapter_number'] ?? 0;
            $title = $c['chapter_title'] ? ' - ' . $c['chapter_title'] : '';
            $label = "Chapter {$num}{$title}";

            $list[] = [
                'id' => $c['chapter_id'],
                'manga_id' => $c['manga_id'] ?? $mangaId,
                'number' => $num,
                'title' => $c['chapter_title'] ?? '',
                'label' => $label,
                'thumbnail' => $thumb,
                'release_date' => $c['release_date'] ?? '',
                'view_count' => $c['view_count'] ?? 0,
            ];
        }

        // Urutkan default dari chapter terbaru (descending) untuk browsing, 
        // tapi sediakan nomor untuk navigasi reader
        return $list;
    }

    /**
     * Ambil halaman lembar gambar dari satu chapter untuk reader.
     */
    public function getChapterPages(string $chapterId): ?array
    {
        $res = $this->requestApi("/chapter/detail/{$chapterId}", [], 2592000); // Gambar chapter permanen, cache 30 hari
        if (!$res || empty($res['data'])) {
            return null;
        }

        $d = $res['data'];
        $baseUrl = rtrim($d['base_url'] ?? 'https://assets.shngm.id', '/');
        $path = '/' . trim($d['chapter']['path'] ?? '', '/') . '/';
        $rawPages = $d['chapter']['data'] ?? [];

        $pages = [];
        foreach ($rawPages as $idx => $imgName) {
            $fullUrl = $baseUrl . $path . $imgName;
            $pages[] = [
                'index' => $idx + 1,
                'url' => '/api/proxy-image?url=' . urlencode($fullUrl),
                'raw_url' => $fullUrl,
            ];
        }

        return [
            'chapter_id' => $chapterId,
            'page_count' => count($pages),
            'pages' => $pages,
        ];
    }

    /**
     * Format response JSON list komik menjadi array seragam untuk Blade / Livewire.
     */
    protected function formatComicListResponse(?array $res, int $page): array
    {
        if (!$res || empty($res['data'])) {
            return [
                'data' => [],
                'page' => $page,
                'total_page' => 1,
                'total_record' => 0,
                'has_next' => false,
            ];
        }

        $meta = $res['meta'] ?? [];
        $totalPage = $meta['total_page'] ?? 1;

        $items = [];
        foreach ($res['data'] as $d) {
            $rawCover = $d['cover_portrait_url'] ?: ($d['cover_image_url'] ?? '');
            $cover = $rawCover ? '/api/proxy-image?url=' . urlencode($rawCover) : '';

            $format = $d['taxonomy']['Format'][0]['name'] ?? 'Manhwa';
            $genres = array_map(fn($g) => $g['name'], array_slice($d['taxonomy']['Genre'] ?? [], 0, 3));

            $items[] = [
                'id' => $d['manga_id'],
                'title' => $d['title'] ?? 'Untitled',
                'cover' => $cover,
                'format' => $format,
                'genres' => $genres,
                'rating' => isset($d['user_rate']) ? number_format((float) $d['user_rate'], 1) : null,
                'latest_chapter' => $d['latest_chapter_number'] ?? null,
                'latest_chapter_id' => $d['latest_chapter_id'] ?? null,
                'latest_chapter_time' => $d['latest_chapter_time'] ?? null,
                'status' => ($d['status'] ?? 1) === 1 ? 'Ongoing' : 'Completed',
                'description' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $d['description'] ?? '')), 260),
                'views' => (int) ($d['view_count'] ?? 0),
                'bookmarks' => (int) ($d['bookmark_count'] ?? 0),
                'year' => $d['release_year'] ?? null,
            ];
        }

        return [
            'data' => $items,
            'page' => $meta['page'] ?? $page,
            'total_page' => $totalPage,
            'total_record' => $meta['total_record'] ?? count($items),
            'has_next' => ($meta['page'] ?? $page) < $totalPage,
        ];
    }
}
