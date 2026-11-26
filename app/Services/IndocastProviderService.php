<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus katalog beberapa provider Indocast (vidio, wetv, sinetron, iqiyi, viu, hbo).
 *
 * Bentuk respons tiap provider berbeda-beda, jadi di sini diseragamkan menjadi:
 * [ 'items' => [ ['id','title','cover','year','desc','badge'] ], 'ok' => bool ]
 */
class IndocastProviderService
{
    protected const BASE = 'https://indocast.site/api';

    public static function key(): string
    {
        return trim((string) config('services.indocast.key', ''));
    }

    public static function enabled(): bool
    {
        return self::key() !== '';
    }

    protected static function get(string $path, int $ttl = 1800): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'ic_' . md5($path);

        $result = Cache::remember($cacheKey, $ttl, function () use ($path) {
            try {
                $res = Http::withHeaders([
                    'x-api-key' => self::key(),
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
                ])->timeout(20)->get(self::BASE . '/' . ltrim($path, '/'));

                return $res->ok() ? $res->json() : null;
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
     * Katalog ringkas satu provider: ambil item dari struktur apa pun yang dipakai.
     */
    public static function home(string $provider, int $limit = 24): array
    {
        $json = self::get($provider . '/home', 1800);

        if (!is_array($json)) {
            return ['ok' => false, 'items' => []];
        }

        $items = [];
        $seen = [];

        $push = function ($node) use (&$items, &$seen, $limit) {
            if (count($items) >= $limit || !is_array($node)) {
                return;
            }

            $title = trim((string) ($node['title'] ?? $node['name'] ?? ''));
            $id = (string) ($node['id'] ?? $node['seriesId'] ?? $node['series_id'] ?? $node['content_id'] ?? '');

            if ($title === '' || $id === '' || isset($seen[$id])) {
                return;
            }

            $pick = function ($v) {
                if (is_string($v) && $v !== '') {
                    return $v;
                }

                if (is_array($v)) {
                    foreach (['poster', 'tile', 'url', 'src'] as $k) {
                        if (!empty($v[$k]) && is_string($v[$k])) {
                            return $v[$k];
                        }
                    }

                    // HBO mengirim daftar gambar ber-kind: pilih poster tegak duluan.
                    $preferred = ['poster', 'default-portrait', 'portrait', 'box-art', 'default-tall', 'tile', 'default-wide'];
                    foreach ($preferred as $kind) {
                        foreach ($v as $img) {
                            if (is_array($img) && ($img['kind'] ?? '') === $kind && !empty($img['url'])) {
                                return (string) $img['url'];
                            }
                        }
                    }

                    foreach ($v as $img) {
                        if (is_array($img) && !empty($img['url'])) {
                            return (string) $img['url'];
                        }
                    }
                }

                return '';
            };

            $cover = $pick($node['coverUrl'] ?? null)
                ?: $pick($node['cover'] ?? null)
                ?: $pick($node['imageUrl'] ?? null)
                ?: $pick($node['portraitThumbnail'] ?? null)
                ?: $pick($node['thumbnail'] ?? null)
                ?: $pick($node['images'] ?? null);

            $seen[$id] = true;

            $items[] = [
                'id' => $id,
                'title' => $title,
                'cover' => ImageProxyService::wrap($cover),
                'year' => substr((string) ($node['releaseDate'] ?? $node['release_year'] ?? $node['year'] ?? ''), 0, 4),
                'desc' => trim((string) (is_scalar($node['description'] ?? $node['synopsis'] ?? $node['overview'] ?? '') ? ($node['description'] ?? $node['synopsis'] ?? $node['overview'] ?? '') : '')),
                'badge' => (string) ($node['type'] ?? $node['contentType'] ?? ''),
            ];
        };

        $walk = function ($node) use (&$walk, $push) {
            if (!is_array($node)) {
                return;
            }

            // Daftar item biasanya berada di 'items', 'sliderItems', 'banners'.
            foreach (['items', 'sliderItems', 'banners', 'animes'] as $listKey) {
                if (!empty($node[$listKey]) && is_array($node[$listKey])) {
                    foreach ($node[$listKey] as $child) {
                        $push($child);
                    }
                }
            }

            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($json);

        return ['ok' => !empty($items), 'items' => $items];
    }

    /**
     * Status semua provider Indocast (/api/status).
     *
     * Hasilnya disimpan sebentar di cache supaya halaman yang memanggil
     * berkali-kali tidak menghajar endpoint status terus-menerus.
     *
     * @return array{ok:bool, all_online:bool, online:int, maintenance:int, updated_at:?string, providers:array<string,array>}
     */
    public static function status(int $ttl = 60): array
    {
        $json = self::get('status', $ttl);
        $data = (array) ($json['data'] ?? []);

        $providers = [];

        foreach ((array) ($data['providers'] ?? []) as $provider) {
            $id = trim((string) ($provider['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $providers[$id] = [
                'id' => $id,
                'name' => (string) ($provider['name'] ?? $id),
                'logo' => (string) ($provider['logo'] ?? ''),
                'status' => strtolower((string) ($provider['status'] ?? 'unknown')),
                'message' => $provider['message'] ?? null,
                'since' => $provider['since'] ?? null,
                'until' => $provider['until'] ?? null,
            ];
        }

        return [
            'ok' => $json !== null && $providers !== [],
            'all_online' => (bool) ($data['all_online'] ?? false),
            'online' => (int) ($data['online'] ?? 0),
            'maintenance' => (int) ($data['maintenance'] ?? 0),
            'updated_at' => $data['updated_at'] ?? null,
            'providers' => $providers,
        ];
    }

    /**
     * True kalau provider tertentu sedang online menurut endpoint status.
     * Provider yang tidak dikenal dianggap online supaya tidak memblokir
     * pemanggilan yang sudah ada.
     */
    public static function isOnline(string $provider): bool
    {
        $status = self::status();
        $provider = strtolower(trim($provider));

        if (! isset($status['providers'][$provider])) {
            return true;
        }

        return $status['providers'][$provider]['status'] === 'online';
    }

    /**
     * Daftar id provider yang sedang maintenance.
     *
     * @return array<int,string>
     */
    public static function maintenanceProviders(): array
    {
        $status = self::status();

        return array_values(array_map(
            fn (array $provider): string => $provider['id'],
            array_filter($status['providers'], fn (array $provider): bool => $provider['status'] !== 'online')
        ));
    }
}
