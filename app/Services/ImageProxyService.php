<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Proxy gambar untuk aset pihak ketiga.
 *
 * Tujuannya bukan sekadar teknis: URL asli sering memuat nama provider
 * (mis. wetvinfo.com, vidiocdn.com), dan kita tidak ingin itu tampil di HTML.
 */
class ImageProxyService
{
    /** Host yang boleh diambil. */
    protected const HOSTS = [
        'wetvinfo.com',
        'vidiocdn.com',
        'vidio.com',
        'aoneroom.com',
        'hakunaymatata.com',
        'indocast.site',
        'animekompi.sbs',
        'anichin.moe',
        'anilist.co',
        'discomax.com',
        'hbomax.com',
        'shinigami.gg',
        'shinigami.io',
        'shinigami.mx',
        'shngm.id',
        'shngm.io',
    ];

    public static function encode(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    public static function decode(string $encoded): ?string
    {
        if ($encoded === '') {
            return null;
        }

        $encoded = strtr($encoded, '-_', '+/');
        $pad = strlen($encoded) % 4;

        if ($pad) {
            $encoded .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($encoded, true);

        return $decoded && filter_var($decoded, FILTER_VALIDATE_URL) ? $decoded : null;
    }

    public static function allowed(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach (self::HOSTS as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bungkus URL gambar supaya lewat domain kita.
     */
    public static function wrap(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || str_starts_with($url, '/')) {
            return $url;
        }

        if (!self::allowed($url)) {
            return $url;
        }

        return url('/api/img?u=' . self::encode($url));
    }

    public static function fetch(string $url)
    {
        // Cache di disk: byte gambar tidak lagi masuk tabel cache database.
        if ($hit = ImageDiskCache::get($url)) {
            return response($hit['body'], 200, [
                'Content-Type' => $hit['type'],
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
            CURLOPT_REFERER => (parse_url($url, PHP_URL_SCHEME) ?: 'https') . '://' . (parse_url($url, PHP_URL_HOST) ?: '') . '/',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false || $status >= 400 || strlen($body) < 200) {
            return response('', 404);
        }

        ImageDiskCache::put($url, $body, $type);

        return response($body, 200, [
            'Content-Type' => $type ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
