<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien provider WeTV di Indocast.
 *
 * Berbeda dari Vidio, stream WeTV tidak ber-DRM: playlist HLS bersih dan
 * beberapa pilihan resolusi (144P sampai 720P), plus subtitle SRT.
 *
 * Catatan endpoint:
 * - /home   : rak beranda (banners, wetvHot, segeraTayang, terbaru)
 * - /detail : butuh parameter cid
 * - /play   : butuh cid + vid (vid ada di daftar episode)
 * - /search dan /explore saat ini balas 502 dari sisi Indocast
 */
class WeTVService
{
    protected const BASE = 'https://indocast.site/api/wetv';

    public static function enabled(): bool
    {
        return trim((string) env('INDOCAST_API_KEY', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) env('INDOCAST_API_KEY', ''));
    }

    public static function api(string $path, array $query = [], int $ttl = 600, int $timeout = 30): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'wetv_' . md5($path . '|' . http_build_query($query));

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

        if ($result === null) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    /**
     * Item rak WeTV -> bentuk katalog KZSTRM.
     */
    public static function mapItem(array $item): ?array
    {
        $cid = trim((string) ($item['id'] ?? ''));

        if ($cid === '') {
            return null;
        }

        $title = trim((string) ($item['title'] ?? ''));

        if ($title === '') {
            return null;
        }

        $vertical = (string) ($item['coverVertical'] ?? '');
        $horizontal = (string) ($item['coverHorizontal'] ?? '');
        $score = $item['score'] ?? null;

        return [
            'cid' => $cid,
            'slug' => $cid,
            'title' => $title,
            'thumbnail' => ImageProxyService::wrap($vertical ?: $horizontal),
            'banner' => ImageProxyService::wrap($horizontal ?: $vertical),
            'subtitle' => (string) ($item['subtitle'] ?? ''),
            'badge' => (string) ($item['badge'] ?? ''),
            'status' => (string) ($item['statusText'] ?? ''),
            'rating' => is_numeric($score) && (float) $score > 0 ? (float) $score : null,
            'provider_badge' => '',
            'type' => 'series',
            'is_series' => true,
            'url' => '/drama-asia/' . $cid,
        ];
    }

    /**
     * Semua judul dari rak beranda (di-cache).
     */
    public static function rails(): array
    {
        return Cache::remember('wetv_rails_v1', 1800, function () {
            $json = self::api('/home', [], 1800);

            if (!$json) {
                return [];
            }

            $out = [];
            $sections = (array) ($json['sections'] ?? []);

            foreach (['banners', 'wetvHot', 'terbaru', 'segeraTayang'] as $key) {
                foreach ((array) ($sections[$key] ?? []) as $item) {
                    $mapped = self::mapItem((array) $item);

                    if ($mapped && !isset($out[$mapped['cid']])) {
                        $out[$mapped['cid']] = $mapped;
                    }
                }
            }

            return array_values($out);
        });
    }

    /**
     * Katalog sederhana: filter judul lokal + paging (pencarian API sedang rusak).
     */
    public static function catalog(string $category = 'all', int $page = 1, int $limit = 24, string $search = ''): array
    {
        $items = self::rails();
        $needle = strtolower(trim($search));

        if ($needle !== '') {
            $items = array_values(array_filter(
                $items,
                fn ($i) => str_contains(strtolower($i['title']), $needle)
            ));
        }

        $total = count($items);
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'data' => array_slice($items, ($page - 1) * $limit, $limit),
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
     * Sinopsis kaya dari WeTV untuk judul tertentu (dipakai sebagai pelengkap).
     */
    public static function descriptionForTitle(string $title): ?string
    {
        $needle = strtolower(trim($title));

        if ($needle === '') {
            return null;
        }

        return Cache::remember('wetv_desc_' . md5($needle), 3600, function () use ($needle) {
            // Ambil beranda terbaru: rak bisa berubah dan cache lama tidak memuat
            // semua judul.
            $home = self::api('/home', [], 300);
            $sections = (array) (($home['sections'] ?? []));

            foreach (['wetvHot', 'terbaru', 'segeraTayang', 'banners'] as $key) {
                foreach ((array) ($sections[$key] ?? []) as $item) {
                    if (strtolower(trim((string) ($item['title'] ?? ''))) !== $needle) {
                        continue;
                    }

                    $detail = self::detail((string) $item['id']);
                    $desc = trim((string) ($detail['description'] ?? ''));

                    return mb_strlen($desc) >= 80 ? $desc : null;
                }
            }

            return null;
        });
    }

    /**
     * Detail serial + daftar episode (vid dipakai untuk memutar).
     */
    public static function detail(string $cid, int $ttl = 900): ?array
    {
        $cid = trim($cid);

        if ($cid === '') {
            return null;
        }

        $json = self::api('/detail', ['cid' => $cid], $ttl);

        if (!$json || empty($json['success'])) {
            return null;
        }

        $series = (array) ($json['series'] ?? []);
        $episodes = [];

        foreach ((array) ($json['episodes'] ?? []) as $ep) {
            $vid = trim((string) ($ep['vid'] ?? ''));

            if ($vid === '') {
                continue;
            }

            $episodes[] = [
                'vid' => $vid,
                'title' => (string) ($ep['title'] ?? ''),
                'number' => (string) ($ep['episodeNumber'] ?? ''),
                'duration' => (int) ($ep['durationSeconds'] ?? 0),
                'free' => (bool) ($ep['isFree'] ?? false),
                'thumbnail' => ImageProxyService::wrap((string) ($ep['thumbnail'] ?? '')),
            ];
        }

        $genres = [];
        foreach ((array) ($series['genres'] ?? []) as $g) {
            $genres[] = is_array($g) ? (string) ($g['name'] ?? '') : (string) $g;
        }

        return [
            'cid' => $cid,
            'title' => (string) ($series['title'] ?? ''),
            'subtitle' => (string) ($series['subtitle'] ?? ''),
            'description' => (string) ($series['description'] ?? ''),
            'poster' => ImageProxyService::wrap((string) ($series['coverVertical'] ?? '')),
            'backdrop' => ImageProxyService::wrap((string) ($series['coverHorizontal'] ?? '')),
            'year' => (int) ($series['year'] ?? 0) ?: null,
            'region' => (string) ($series['region'] ?? ''),
            'genres' => array_values(array_filter($genres)),
            'rating' => is_numeric($series['score'] ?? null) && (float) $series['score'] > 0 ? (float) $series['score'] : null,
            'episode_total' => (int) ($series['episodeTotal'] ?? count($episodes)),
            'episodes' => $episodes,
        ];
    }

    /**
     * URL putar untuk satu episode. Resolusi tertinggi tanpa DRM dipakai.
     */
    public static function play(string $cid, string $vid): ?array
    {
        $cid = trim($cid);
        $vid = trim($vid);

        if ($cid === '' || $vid === '') {
            return null;
        }

        $json = self::api('/play', ['cid' => $cid, 'vid' => $vid], 120, 60);

        if (!$json || (int) ($json['code'] ?? -1) !== 0) {
            return null;
        }

        $data = (array) ($json['data'] ?? []);
        $resolutions = array_values(array_filter(
            (array) ($data['resolutions'] ?? []),
            fn ($r) => is_array($r) && empty($r['drm']) && !empty($r['url'])
        ));

        // Urutkan dari kualitas tertinggi.
        usort($resolutions, fn ($a, $b) => (int) preg_replace('/\D/', '', (string) ($b['resolution'] ?? 0))
            <=> (int) preg_replace('/\D/', '', (string) ($a['resolution'] ?? 0)));

        $subtitles = [];

        foreach ((array) ($data['subtitles'] ?? []) as $sub) {
            if (empty($sub['url'])) {
                continue;
            }

            $subtitles[] = [
                'lang' => strtolower((string) ($sub['lang'] ?? '')),
                'label' => (string) ($sub['name'] ?? strtoupper((string) ($sub['lang'] ?? ''))),
                // Lewat proxy sendiri supaya host asal tidak tampil di HTML.
                'url' => VidioService::proxyUrl((string) $sub['url']),
            ];
        }

        return [
            'stream_url' => VidioService::proxyUrl((string) ($resolutions[0]['url'] ?? $data['url'] ?? '')),
            'stream_direct' => '',
            'qualities' => array_map(fn ($r) => [
                'label' => (string) ($r['resolution'] ?? ''),
                'url' => VidioService::proxyUrl((string) $r['url']),
            ], $resolutions),
            'subtitles' => $subtitles,
            'preview' => (bool) ($data['preview'] ?? false),
            'duration' => (int) ($data['duration'] ?? 0),
        ];
    }
}
