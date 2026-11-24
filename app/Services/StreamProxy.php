<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Membungkus URL media pihak ketiga menjadi URL milik domain sendiri
 * dengan token terenkripsi, supaya nama provider tidak pernah muncul di browser.
 *
 * Token memakai enkripsi APP_KEY, bukan cache: tetap valid walau cache
 * dibersihkan dan tidak bisa dibaca sebagai base64 biasa.
 */
class StreamProxy
{
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

        $token = rtrim(strtr(base64_encode(Crypt::encryptString($url)), '+/', '-_'), '=');

        // Hanya playlist yang perlu akhiran .m3u8 (deteksi tipe di pemutar).
        // .mp4 sengaja tidak dipakai: Cloudflare menganggapnya video dan
        // menelan header Range, sehingga seek/pemutaran rusak.
        $suffix = $ext === 'm3u8' ? '.m3u8' : '';

        return url('/api/hls/' . $token . $suffix);
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
        $token = basename($path);
        $token = preg_replace('/\.[a-z0-9]+$/i', '', $token) ?: '';

        return self::resolve($token);
    }

    public static function resolve(string $token): ?string
    {
        $token = preg_replace('/\.[a-z0-9]+$/i', '', trim($token));

        if ($token === '') {
            return null;
        }

        $raw = strtr($token, '-_', '+/');
        $raw .= str_repeat('=', (4 - strlen($raw) % 4) % 4);
        $encrypted = base64_decode($raw, true);

        if ($encrypted === false || $encrypted === '') {
            return null;
        }

        try {
            $url = Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($url) && str_starts_with($url, 'http') ? $url : null;
    }

    /** Seperti resolve(), tapi mengizinkan skema internal (mis. drivepath:). */
    public static function resolveRaw(string $token): ?string
    {
        $token = preg_replace('/\.[a-z0-9]+$/i', '', trim($token));

        if ($token === '') {
            return null;
        }

        $raw = strtr($token, '-_', '+/');
        $raw .= str_repeat('=', (4 - strlen($raw) % 4) % 4);
        $encrypted = base64_decode($raw, true);

        if ($encrypted === false || $encrypted === '') {
            return null;
        }

        try {
            $url = Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($url) && $url !== '' ? $url : null;
    }
}
