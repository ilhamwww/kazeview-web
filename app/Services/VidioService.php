<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien untuk provider Vidio di Indocast (https://indocast.site/api/vidio).
 *
 * Catatan:
 * - Autentikasi memakai header x-api-key dari .env (INDOCAST_API_KEY).
 * - Play butuh id EPISODE, bukan id judul. id judul dipakai untuk detail.
 * - HLS yang dikembalikan bisa diputar langsung (tidak ber-DRM); subtitle
 *   tersedia sebagai VTT terpisah.
 */
class VidioService
{
    protected const BASE = 'https://indocast.site/api/vidio';

    protected const RAIL_TYPES = ['movie' => 'film', 'series' => 'serial'];

    public static function enabled(): bool
    {
        return trim((string) env('INDOCAST_API_KEY', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) env('INDOCAST_API_KEY', ''));
    }

    /**
     * Request mentah ke Indocast. Mengembalikan null kalau gagal.
     */
    public static function api(string $path, array $query = [], int $ttl = 300, int $timeout = 25): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'vidio_' . md5($path . '|' . http_build_query($query));

        $result = Cache::remember($cacheKey, $ttl, function () use ($path, $query, $timeout) {
            try {
                $res = Http::withHeaders([
                    'x-api-key' => self::key(),
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
                ])->timeout($timeout)->get(self::BASE . $path, $query);

                if (!$res->ok()) {
                    return null;
                }

                $json = $res->json();

                return is_array($json) ? $json : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        // Kegagalan sesaat jangan ikut tersimpan di cache.
        if ($result === null) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    /**
     * Ubah item Vidio menjadi bentuk yang dipakai katalog KZSTRM.
     */
    public static function mapItem(array $item, string $fallbackType = 'movie'): ?array
    {
        $id = trim((string) ($item['id'] ?? ''));

        if ($id === '') {
            return null;
        }

        $title = trim((string) ($item['title'] ?? $item['altTitle'] ?? ''));

        if ($title === '') {
            return null;
        }

        $cover = (string) ($item['coverUrl'] ?? $item['coverPortrait'] ?? '');
        $landscape = (string) ($item['coverLandscape'] ?? '');

        // Thumbor: minta ukuran yang lebih besar untuk poster grid.
        $poster = str_contains($cover, '/288x432/')
            ? str_replace('/288x432/', '/432x648/', $cover)
            : $cover;

        return [
            'id' => $id,
            'slug' => $id,
            'vidio_id' => $id,
            'title' => $title,
            'thumbnail' => ImageProxyService::wrap($poster),
            'banner' => ImageProxyService::wrap($landscape ?: $poster),
            'description' => (string) ($item['description'] ?? ''),
            'rating' => isset($item['rating']) && is_numeric($item['rating']) ? (float) $item['rating'] : null,
            'year' => isset($item['year']) ? (int) $item['year'] : null,
            'type' => $fallbackType === 'series' ? 'series' : 'movie',
            'is_series' => $fallbackType === 'series',
            'country' => 'Indonesia',
            'provider_badge' => '',
            'url' => '/original/' . $id,
        ];
    }

    /**
     * Bersihkan daftar section menjadi item unik.
     */
    protected static function flattenSections(array $sections, string $type): array
    {
        $out = [];
        $seen = [];

        foreach ($sections as $section) {
            foreach ((array) ($section['items'] ?? []) as $item) {
                $mapped = self::mapItem($item, $type);

                if (!$mapped || isset($seen[$mapped['id']])) {
                    continue;
                }

                $seen[$mapped['id']] = true;
                $out[] = $mapped;
            }
        }

        return $out;
    }

    /**
     * Seluruh judul dari rak kurasi (film + serial). Dipakai untuk halaman katalog.
     */
    public static function rails(): array
    {
        return Cache::remember('vidio_rails_v1', 1800, function () {
            $all = [];

            foreach (['movie' => 'film', 'series' => 'serial'] as $path => $type) {
                for ($page = 1; $page <= 4; $page++) {
                    $json = self::api('/' . $path, ['page' => $page], 1800);

                    if (!$json || empty($json['sections'])) {
                        break;
                    }

                    foreach (self::flattenSections($json['sections'], $type) as $item) {
                        $all[$item['id']] = $item;
                    }

                    if (empty($json['nextPage'])) {
                        break;
                    }
                }
            }

            return array_values($all);
        });
    }

    /**
     * Katalog dengan filter kategori + pencarian, meniru bentuk ChitogenxService::indonesiaCatalog.
     */
    public static function catalog(string $category = 'all', int $page = 1, int $limit = 24, string $search = ''): array
    {
        $needle = trim($search);

        if ($needle !== '') {
            $items = self::search($needle);

            // Hasil pencarian tidak punya tahun, jadi filter tahun/kategori dilewati.
            if ($category === 'movie' || $category === 'series') {
                $items = array_values(array_filter($items, fn ($i) => $i['type'] === $category));
            }
        } else {
            $items = self::rails();

            if ($category === 'movie' || $category === 'series') {
                $items = array_values(array_filter($items, fn ($i) => $i['type'] === $category));
            }
        }

        $total = count($items);
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'data' => array_slice($items, ($page - 1) * $limit, $limit),
            'all_ids' => $total,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
                'hasMore' => ($page * $limit) < $total,
            ],
        ];
    }

    /**
     * Pencarian judul. Vidio hanya menerima kata kunci >= 3 huruf.
     */
    public static function search(string $query, int $ttl = 900): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return [];
        }

        $json = self::api('/search', ['q' => $query], $ttl);

        if (!$json) {
            return [];
        }

        // Pencarian mengembalikan satu section "Movies & Series"; tipenya tidak
        // dibedakan oleh API, jadi is_series diisi dari detail saat dibuka.
        return self::flattenSections($json['sections'] ?? [], 'movie');
    }

    /**
     * Detail judul: metadata + daftar season/episode.
     */
    public static function detail(string $id, int $ttl = 600): ?array
    {
        $id = trim($id);

        if ($id === '' || !ctype_digit($id)) {
            return null;
        }

        $json = self::api('/detail', ['id' => $id], $ttl);

        if (!$json || empty($json['success'])) {
            return null;
        }

        $episodes = [];
        $seasons = [];

        foreach ((array) ($json['seasons'] ?? []) as $season) {
            $seasonEpisodes = [];

            foreach ((array) ($season['episodes'] ?? []) as $ep) {
                $episode = [
                    'id' => (string) ($ep['id'] ?? ''),
                    'title' => (string) ($ep['title'] ?? ''),
                    'description' => (string) ($ep['description'] ?? ''),
                    'duration' => $ep['duration'] ?? null,
                    'number' => count($seasonEpisodes) + 1,
                ];

                if ($episode['id'] === '') {
                    continue;
                }

                $seasonEpisodes[] = $episode;
                $episodes[] = $episode;
            }

            if (!empty($seasonEpisodes)) {
                $seasons[] = [
                    'id' => (string) ($season['id'] ?? ''),
                    'name' => (string) ($season['name'] ?? 'Season'),
                    'episodes' => $seasonEpisodes,
                ];
            }
        }

        $genres = array_values(array_filter(array_map(
            fn ($g) => is_array($g) ? (string) ($g['name'] ?? '') : (string) $g,
            (array) ($json['genres'] ?? [])
        )));

        return [
            'id' => (string) ($json['id'] ?? $id),
            'title' => (string) ($json['title'] ?? ''),
            'description' => (string) ($json['description'] ?? ''),
            'poster' => ImageProxyService::wrap((string) ($json['coverPortrait'] ?? '')),
            'backdrop' => ImageProxyService::wrap((string) ($json['coverLandscape'] ?? '')),
            'trailer' => (string) ($json['trailerUrl'] ?? ''),
            'rating' => isset($json['rating']) && is_numeric($json['rating']) ? (float) $json['rating'] : null,
            'year' => isset($json['releaseDate']) ? (int) substr((string) $json['releaseDate'], 0, 4) : null,
            'release_date' => (string) ($json['releaseDate'] ?? ''),
            'country' => (string) ($json['country'] ?? 'Indonesia'),
            'age_rating' => (string) ($json['contentRating'] ?? ''),
            'genres' => $genres,
            'total_episodes' => (int) ($json['totalEpisodes'] ?? count($episodes)),
            'seasons' => $seasons,
            'episodes' => $episodes,
            'is_series' => !empty($episodes),
        ];
    }

    /**
     * URL putar untuk satu episode (atau satu film).
     */
    public static function play(string $videoId): ?array
    {
        $videoId = trim($videoId);

        if ($videoId === '' || !ctype_digit($videoId)) {
            return null;
        }

        $json = self::api('/play', ['id' => $videoId], 60, 40);

        if (!$json || empty($json['streamUrl'])) {
            return null;
        }

        $subtitles = [];

        foreach ((array) ($json['subtitles'] ?? []) as $sub) {
            $lang = strtolower((string) ($sub['language'] ?? ''));
            $label = match (true) {
                str_contains($lang, 'indones') => 'Indonesia',
                str_contains($lang, 'english') => 'English',
                str_contains($lang, 'malay') => 'Melayu',
                default => ucfirst($lang),
            };

            $subtitles[] = [
                'lang' => $lang,
                'label' => $label,
                'url' => \App\Services\StreamProxy::url((string) ($sub['url_proxy'] ?? $sub['url'] ?? '')),
            ];
        }

        // Pakai proxy Indocast: playlist sudah ditulis ulang ke URL proxy dan
        // mengirim CORS, sehingga hls.js bisa memutar lintas-origin.
        $proxy = (string) ($json['streamUrl_Proxy'] ?? '');

        return [
            // Token acak: browser hanya melihat domain KZSTRM.
            'stream_url' => \App\Services\StreamProxy::url($proxy !== '' ? $proxy : (string) $json['streamUrl'], 'm3u8'),
            'stream_direct' => \App\Services\StreamProxy::url((string) $json['streamUrl'], 'm3u8'),
            'subtitles' => array_values(array_filter($subtitles, fn ($s) => $s['url'] !== '')),
        ];
    }

    /**
     * URL proxy HLS milik kita sendiri (playlist ditulis ulang, bebas CORS).
     */
    public static function proxyUrl(string $url): string
    {
        return url('/api/hls?u=' . self::encodeProxyUrl($url) . '&v=2');
    }

    public static function encodeProxyUrl(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    public static function decodeProxyUrl(string $encoded): ?string
    {
        $encoded = strtr($encoded, '-_', '+/');

        if ($encoded === '') {
            return null;
        }

        $pad = strlen($encoded) % 4;

        if ($pad) {
            $encoded .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($encoded, true);

        return $decoded && filter_var($decoded, FILTER_VALIDATE_URL) ? $decoded : null;
    }

    /**
     * Batasi proxy hanya untuk host CDN yang memang dipakai Vidio.
     */
    public static function isAllowedHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach (['vidio.com', 'vidiocdn.com', 'akamaized.net', 'indocast.site', 'aoneroom.com', 'wetvinfo.com', 'hakunaymatata.com'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tulis ulang semua URI di playlist agar lewat proxy kita.
     */
    public static function rewritePlaylist(string $body, string $baseUrl): string
    {
        // Query string (token hdnts/hdntl) harus dibuang dulu, kalau tidak URL
        // relatif akan menempel di belakang token dan jadi tidak valid.
        $clean = preg_replace('~[?#].*$~', '', $baseUrl) ?: $baseUrl;
        $dir = preg_replace('~/[^/]*$~', '/', $clean) ?: $clean;
        $parts = parse_url($clean);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

        $resolve = function (string $uri) use ($dir, $origin): string {
            $uri = trim($uri);

            if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
                return $uri;
            }

            if (str_starts_with($uri, '//')) {
                return 'https:' . $uri;
            }

            if (str_starts_with($uri, '/')) {
                return $origin . $uri;
            }

            return $dir . $uri;
        };

        $lines = preg_split("~\r\n|\n|\r~", $body) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $out[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                $out[] = preg_replace_callback(
                    '~URI="([^"]+)"~',
                    fn ($m) => 'URI="' . self::proxyUrl($resolve($m[1])) . '"',
                    $line
                );
                continue;
            }

            $out[] = self::proxyUrl($resolve($trimmed));
        }

        return implode("\n", $out);
    }
}
