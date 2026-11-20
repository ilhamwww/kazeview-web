<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache gambar di disk, bukan di tabel cache database.
 *
 * Sebelumnya byte gambar disimpan lewat Cache::put sehingga tabel `cache`
 * membengkak ratusan MB (TOAST), padahal isinya cuma poster.
 */
class ImageDiskCache
{
    protected static function baseDir(): string
    {
        return storage_path('app/image-cache');
    }

    protected static function path(string $url): string
    {
        $hash = md5($url);

        return self::baseDir() . '/' . substr($hash, 0, 2) . '/' . $hash . '.img';
    }

    /**
     * @return array{body:string,type:string}|null
     */
    public static function get(string $url): ?array
    {
        $path = self::path($url);

        if (!is_file($path)) {
            return null;
        }

        $body = @file_get_contents($path);

        if ($body === false || $body === '') {
            return null;
        }

        return ['body' => $body, 'type' => self::sniff($body)];
    }

    public static function put(string $url, string $body, string $type = ''): void
    {
        if ($body === '') {
            return;
        }

        $path = self::path($url);
        $dir = dirname($path);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (@file_put_contents($tmp, $body) === false) {
            return;
        }

        @rename($tmp, $path);
    }

    protected static function sniff(string $body): string
    {
        if (str_starts_with($body, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($body, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($body, 'GIF8')) {
            return 'image/gif';
        }

        if (str_starts_with($body, 'RIFF') && substr($body, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        if (str_starts_with($body, '<svg') || str_contains(substr($body, 0, 300), '<svg')) {
            return 'image/svg+xml';
        }

        return 'image/jpeg';
    }

    /**
     * Hapus berkas yang lebih tua dari $days hari.
     */
    public static function prune(int $days = 7): int
    {
        $root = self::baseDir();

        if (!is_dir($root)) {
            return 0;
        }

        $limit = time() - ($days * 86400);
        $removed = 0;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getMTime() < $limit && @unlink($file->getPathname())) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Bersihkan sisa cache gambar lama di tabel cache database.
     */
    public static function purgeLegacyDbCache(): int
    {
        $deleted = 0;

        try {
            $deleted += \Illuminate\Support\Facades\DB::table('cache')
                ->where('key', 'like', 'img\_%')
                ->orWhere('key', 'like', 'img\_proxy\_%')
                ->delete();
        } catch (\Throwable $e) {
        }

        return $deleted;
    }
}
