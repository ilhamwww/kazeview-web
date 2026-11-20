<?php

use Illuminate\Support\Facades\Route;

/*
 * Proxy media dan gambar KZSTRM.
 *
 * Semua URL pihak ketiga dibungkus token milik sendiri (/api/hls/<token>)
 * supaya browser tidak pernah melihat domain provider. Playlist ditulis ulang,
 * file media dialirkan langsung (streaming) agar pemutaran tidak menunggu
 * seluruh potongan selesai diunduh.
 */

Route::get('/api/hls/{key?}', function (\Illuminate\Http\Request $request, ?string $key = null) {
    $key = (string) ($key !== null ? preg_replace('/\.[a-z0-9]+$/i', '', $key) : $request->query('k', ''));
    $encoded = (string) $request->query('u', '');
    $url = $key !== ''
        ? \App\Services\StreamProxy::resolve($key)
        : \App\Services\VidioService::decodeProxyUrl($encoded);

    if (! $url || ! \App\Services\VidioService::isAllowedHost($url)) {
        abort(400, 'URL tidak diizinkan');
    }

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36';
    $looksPlaylist = str_contains(strtolower(urldecode(urldecode($url))), '.m3u8');

    // ---- Playlist: ambil utuh, tulis ulang URI-nya ke proxy sendiri ----
    if ($looksPlaylist) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            return response('Upstream error', 502);
        }

        $rewritten = \App\Services\VidioService::rewritePlaylist((string) $body, $url);

        return response($rewritten, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ]);
    }

    // ---- File media: alirkan sambil diunduh (tanpa menunggu utuh) ----
    $start = 0;
    $end = 4 * 1024 * 1024 - 1;

    $rangeHeader = (string) ($request->header('Range') ?: ($_SERVER['HTTP_RANGE'] ?? ''));

    if ($rangeHeader !== '' && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $m)) {
        $start = (int) $m[1];
        $end = $m[2] !== '' ? (int) $m[2] : $start + 2 * 1024 * 1024 - 1;
    }

    $total = \Illuminate\Support\Facades\Cache::remember('kz_size_' . md5($url), 900, function () use ($url, $ua) {
        $probe = curl_init($url);
        curl_setopt_array($probe, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
        ]);
        $head = (string) curl_exec($probe);
        curl_close($probe);

        if (preg_match('/content-length:\s*(\d+)/i', $head, $mm)) {
            return (int) $mm[1];
        }

        $probe = curl_init($url);
        curl_setopt_array($probe, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_RANGE => '0-0',
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
        ]);
        $head = (string) curl_exec($probe);
        curl_close($probe);

        if (preg_match('~content-range:\s*bytes\s+\d+-\d+/(\d+)~i', $head, $mm)) {
            return (int) $mm[1];
        }

        return 0;
    });

    return response()->stream(function () use ($url, $start, $end, $ua) {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RANGE => $start . '-' . $end,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
            CURLOPT_WRITEFUNCTION => function ($curl, $chunk) {
                echo $chunk;
                flush();

                return strlen($chunk);
            },
        ]);
        curl_exec($ch);
        curl_close($ch);
    }, 206, [
        'Content-Type' => 'video/mp4',
        'Access-Control-Allow-Origin' => '*',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-store',
        'Content-Range' => 'bytes ' . $start . '-' . $end . '/' . ($total ?: '*'),
    ]);
})->middleware('throttle:600,1')->name('vidio.hls');

/*
 * Proxy gambar: dipakai supaya URL aset pihak ketiga (yang memuat nama
 * provider) tidak muncul di HTML publik.
 */
Route::get('/api/img', function (\Illuminate\Http\Request $request) {
    $url = \App\Services\ImageProxyService::decode((string) $request->query('u', ''));

    if (! $url || ! \App\Services\ImageProxyService::allowed($url)) {
        abort(404);
    }

    return \App\Services\ImageProxyService::fetch($url);
})->middleware('throttle:600,1')->name('img.proxy');

// Video anime: teruskan lewat domain sendiri dengan token acak.
Route::get('/api/av', function (\Illuminate\Http\Request $request) {
    $url = \App\Services\ImageProxyService::decode((string) $request->query('u', ''));

    if (! $url || ! str_starts_with($url, 'https://indocast.site/api/animekompi/proxy')) {
        abort(404);
    }

    return redirect()->away(\App\Services\StreamProxy::url($url, 'mp4'), 302)->header('Cache-Control', 'no-store');
})->name('api.anime-video');
