<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Membungkus URL media pihak ketiga menjadi URL milik domain sendiri
 * dengan token acak, supaya nama provider tidak pernah muncul di browser.
 *
 * Token disimpan di cache selama 6 jam; URL asli tidak pernah dikirim
 * ke halaman.
 */
class StreamProxy
{
    protected const TTL = 21600;

    public static function url(string $url, string $ext = ''): string
    {
        $url = trim($url);

        if ($url === '' || ! str_starts_with($url, 'http')) {
            return $url;
        }

        // URL milik sendiri tidak perlu dibungkus lagi.
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        if ($host !== '' && ($host === $appHost || str_ends_with($host, 'kazeview.com'))) {
            return $url;
        }

        $key = substr(hash('sha256', $url), 0, 32);
        Cache::put('kz_stream_' . $key, $url, self::TTL);

        $suffix = in_array($ext, ['m3u8', 'mp4'], true) ? '.' . $ext : '';

        return url('/api/hls/' . $key . $suffix);
    }

    /**
     * Ambil URL asli dari sebuah URL proxy milik sendiri.
     */
    public static function resolveFromUrl(string $url): ?string
    {
        if (! str_contains($url, '/api/hls/')) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $key = basename($path);
        $key = preg_replace('/\.[a-z0-9]+$/i', '', $key) ?: '';

        return self::resolve($key);
    }

    public static function resolve(string $key): ?string
    {
        if ($key === '' || ! preg_match('/^[a-f0-9]{32}$/', $key)) {
            return null;
        }

        $url = Cache::get('kz_stream_' . $key);

        return is_string($url) && $url !== '' ? $url : null;
    }
}
