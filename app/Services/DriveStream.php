<?php

namespace App\Services;

/**
 * Streaming anime 1080p dari Google Drive.
 *
 * File di Drive di-serve oleh rclone (kz-drive-serve, 127.0.0.1:8099) yang
 * menangani refresh token sendiri. Route /api/drive/<token> meneruskan Range
 * ke sana, jadi browser tidak pernah melihat file id, path Drive, atau token.
 */
class DriveStream
{
    protected const SERVE = 'http://127.0.0.1:8099';

    /** URL publik (token terenkripsi) untuk dipakai di halaman tonton. */
    public static function url(string $relPath): string
    {
        $token = rtrim(strtr(base64_encode(\Illuminate\Support\Facades\Crypt::encryptString('drivepath:' . $relPath)), '+/', '-_'), '=');

        return url('/api/drive/' . $token);
    }

    /** Path relatif di dalam folder Drive KZSTRM → URL rclone serve. */
    public static function mediaUrl(string $relPath): string
    {
        $parts = array_filter(explode('/', trim($relPath, '/')), fn ($p) => $p !== '');
        $encoded = implode('/', array_map('rawurlencode', $parts));

        return self::SERVE . '/' . $encoded;
    }

    /**
     * Teruskan Range ke rclone serve dan alirkan balik ke browser.
     *
     * Probe hanya 1 byte (bukan seluruh file) untuk tahu total ukuran, lalu
     * badan file dialirkan per potongan dengan Range yang diminta client.
     */
    public static function stream(string $relPath, ?string $range = null): \Symfony\Component\HttpFoundation\Response
    {
        $url = self::mediaUrl($relPath);

        $probeHeaders = [];
        $probe = curl_init($url);
        curl_setopt_array($probe, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$probeHeaders) {
                $trimmed = trim($line);

                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $trimmed, $m)) {
                    $probeHeaders[':status'] = (int) $m[1];
                } elseif (str_contains($trimmed, ':')) {
                    [$k, $v] = explode(':', $trimmed, 2);
                    $probeHeaders[strtolower(trim($k))] = trim($v);
                }

                return strlen($line);
            },
        ]);
        curl_exec($probe);
        $errno = curl_errno($probe);
        curl_close($probe);

        if ($errno) {
            abort(502, 'Drive lokal tidak merespons');
        }

        $status = $probeHeaders[':status'] ?? 404;

        if ($status >= 400) {
            abort(404, 'File tidak ada di Drive');
        }

        $contentType = $probeHeaders['content-type'] ?? 'video/x-matroska';
        $total = ! empty($probeHeaders['content-length']) ? (int) $probeHeaders['content-length'] : null;

        $outStatus = 200;
        $outHeaders = [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
            'X-KZ-Drive' => 'v4',
        ];

        if ($range && $total && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            $start = $m[1] === '' ? max(0, $total - (int) $m[2]) : (int) $m[1];
            $end = $m[2] === '' ? $total - 1 : min((int) $m[2], $total - 1);

            if ($start > $end || $start >= $total) {
                return response('', 416, ['Content-Range' => 'bytes */' . $total]);
            }

            $outStatus = 206;
            $outHeaders['Content-Range'] = "bytes {$start}-{$end}/{$total}";
            $outHeaders['Content-Length'] = (string) ($end - $start + 1);
        } elseif ($total) {
            $outHeaders['Content-Length'] = (string) $total;
        }

        return response()->stream(function () use ($url, $range) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $range ? ['Range: ' . $range] : [],
                CURLOPT_TIMEOUT => 0,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;
                    flush();

                    return strlen($data);
                },
            ]);

            curl_exec($ch);
            curl_close($ch);
        }, $outStatus, $outHeaders);
    }
}
