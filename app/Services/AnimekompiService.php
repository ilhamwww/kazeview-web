<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Provider anime Animekompi (Indocast).
 *
 * Stream yang dikembalikan sudah berupa MP4 lewat proxy Indocast, mendukung
 * Range dan mengirim CORS, jadi bisa diputar langsung tanpa relay tambahan.
 */
class AnimekompiService
{
    protected const BASE = 'https://indocast.site/api/animekompi';

    public static function enabled(): bool
    {
        return trim((string) env('INDOCAST_API_KEY', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) env('INDOCAST_API_KEY', ''));
    }

    public static function api(string $path, array $query = [], int $ttl = 900, int $timeout = 30): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'ak_' . md5($path . '|' . http_build_query($query));

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
     * Item Animekompi -> bentuk katalog KZSTRM.
     */
    public static function mapItem(array $item): ?array
    {
        $path = trim((string) ($item['path'] ?? ''));

        if ($path === '') {
            return null;
        }

        $title = trim((string) ($item['title'] ?? ''));

        if ($title === '') {
            return null;
        }

        // Judul "Uncen"/18+ tidak ditampilkan.
        if (stripos($title, 'uncen') !== false || stripos($title, '18+') !== false) {
            return null;
        }

        $rating = $item['rating'] ?? null;

        return [
            'path' => $path,
            'slug' => $path,
            'title' => $title,
            'thumbnail' => ImageProxyService::wrap((string) ($item['cover_url'] ?? '')),
            'banner' => ImageProxyService::wrap((string) ($item['cover_url'] ?? '')),
            'rating' => is_numeric($rating) && (float) $rating > 0 ? (float) $rating : null,
            'year' => (string) ($item['release_year'] ?? ''),
            'episodes' => (string) ($item['jumlah_episode'] ?? ''),
            'type' => 'anime',
            'provider_badge' => '',
            'url' => '/animek/' . $path,
        ];
    }

    /**
     * Rak beranda: trending hari ini + update terbaru.
     */
    public static function home(): array
    {
        return Cache::remember('ak_home_v1', 1800, function () {
            $json = self::api('/home', [], 1800);
            $data = (array) ($json['data'] ?? []);

            $build = function (array $list): array {
                $out = [];

                foreach ($list as $item) {
                    $mapped = self::mapItem((array) $item);

                    if ($mapped && !isset($out[$mapped['path']])) {
                        $out[$mapped['path']] = $mapped;
                    }
                }

                return array_values($out);
            };

            return [
                'trending' => $build((array) ($data['trending_today'] ?? [])),
                'latest' => $build((array) ($data['latest_updates'] ?? [])),
                'sliders' => $build((array) ($data['sliders'] ?? [])),
            ];
        });
    }

    /**
     * Seluruh judul dari rak + halaman daftar.
     */
    public static function catalog(): array
    {
        return Cache::remember('ak_catalog_v1', 1800, function () {
            $home = self::home();
            $all = [];

            foreach (array_merge($home['trending'], $home['latest'], $home['sliders']) as $item) {
                $all[$item['path']] = $item;
            }

            // /list mengembalikan indeks A-Z: tiap baris berisi grup huruf
            // dengan daftar 'animes'. Parameter page tidak memengaruhi hasil.
            $list = self::api('/list', [], 1800);
            $groups = is_array($list) ? ($list['data'] ?? (isset($list[0]) ? $list : [])) : [];

            foreach ($groups as $group) {
                foreach ((array) (($group['animes'] ?? [])) as $item) {
                    $mapped = self::mapItem((array) $item);

                    if ($mapped) {
                        $all[$mapped['path']] = $mapped;
                    }
                }
            }

            return array_values($all);
        });
    }

    public static function search(string $query): array
    {
        $needle = strtolower(trim($query));

        if ($needle === '') {
            return [];
        }

        return array_values(array_filter(
            self::catalog(),
            fn ($item) => str_contains(strtolower($item['title']), $needle)
        ));
    }

    /**
     * Detail anime + daftar episode.
     */
    public static function detail(string $path, int $ttl = 1800): ?array
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        $json = self::api('/detail', ['path' => $path], $ttl);

        if (!$json || (int) ($json['code'] ?? -1) !== 0) {
            return null;
        }

        $data = (array) ($json['data'] ?? []);

        if (empty($data['title'])) {
            return null;
        }

        $episodes = [];
        foreach ((array) ($data['episode_list'] ?? []) as $ep) {
            $id = trim((string) ($ep['episode_id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $episodes[] = [
                'id' => $id,
                'number' => (string) ($ep['episode_number'] ?? ''),
                'title' => (string) ($ep['title'] ?? ''),
                'date' => (string) ($ep['release_date'] ?? ''),
            ];
        }

        $genres = [];
        foreach ((array) ($data['genres'] ?? []) as $g) {
            $genres[] = is_array($g) ? (string) ($g['name'] ?? '') : (string) $g;
        }

        return [
            'path' => $path,
            'title' => (string) $data['title'],
            'synopsis' => self::cleanSynopsis((string) ($data['synopsis'] ?? '')),
            'poster' => ImageProxyService::wrap((string) ($data['cover_url'] ?? '')),
            'rating' => is_numeric($data['rating'] ?? null) && (float) $data['rating'] > 0 ? (float) $data['rating'] : null,
            'status' => (string) ($data['status'] ?? ''),
            'type' => (string) ($data['type'] ?? ''),
            'season' => (string) ($data['season'] ?? ''),
            'duration' => (string) ($data['duration'] ?? ''),
            'studio' => (string) ($data['studio'] ?? ''),
            'release_date' => (string) ($data['release_date'] ?? ''),
            'year' => substr((string) ($data['release_date'] ?? ''), 0, 4),
            'genres' => array_values(array_filter($genres)),
            'total_episodes' => (int) ($data['total_episodes'] ?? count($episodes)),
            'episodes' => $episodes,
        ];
    }

    protected static function cleanSynopsis(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Sumber putar satu episode. Server pertama yang punya URL dipakai.
     */
    public static function play(string $episodeId): ?array
    {
        $episodeId = trim($episodeId);

        if ($episodeId === '') {
            return null;
        }

        $json = self::api('/play', ['episode_id' => $episodeId], 120, 45);

        if (!$json || (int) ($json['code'] ?? -1) !== 0) {
            return null;
        }

        $data = (array) ($json['data'] ?? []);
        $servers = [];

        foreach ((array) ($data['servers'] ?? []) as $server) {
            $url = trim((string) ($server['url'] ?? ''));

            if ($url === '' || !str_starts_with($url, 'http')) {
                continue;
            }

            $servers[] = [
                'name' => (string) ($server['name'] ?? 'Server'),
                'url' => $url,
            ];
        }

        if (empty($servers)) {
            return null;
        }

        return [
            'episode_title' => (string) ($data['title'] ?? ''),
            'video_url' => $servers[0]['url'],
            'servers' => $servers,
            'subtitles' => [],
        ];
    }
}
