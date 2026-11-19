<?php

use Illuminate\Support\Facades\Route;

/*
 * Proxy HLS khusus Vidio.
 *
 * Playlist .m3u8 ditulis ulang supaya semua URI (variant, audio, subtitle,
 * segmen) mengarah balik ke proxy ini, sehingga pemutar cukup memakai satu
 * origin dan bebas masalah CORS. Segmen diteruskan apa adanya.
 */
Route::get('/api/hls', function (\Illuminate\Http\Request $request) {
    $encoded = (string) $request->query('u', '');
    $url = \App\Services\VidioService::decodeProxyUrl($encoded);

    if (!$url || !\App\Services\VidioService::isAllowedHost($url)) {
        abort(400, 'URL tidak diizinkan');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',   // dekompres gzip/br; tanpa ini segmen rusak
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
    ]);

    if ($request->headers->has('Range')) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Accept: */*', 'Referer: https://www.vidio.com/', 'Range: ' . $request->header('Range')]
        ));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return response('Upstream error', 502);
    }

    $isPlaylist = str_contains(strtolower($type), 'mpegurl')
        || str_starts_with(ltrim((string) $body), '#EXTM3U');

    if ($isPlaylist) {
        $base = $url;
        $rewritten = \App\Services\VidioService::rewritePlaylist((string) $body, $base);

        return response($rewritten, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ]);
    }

    return response($body, $status === 0 ? 200 : $status, [
        'Content-Type' => $type ?: 'application/octet-stream',
        'Access-Control-Allow-Origin' => '*',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-store',
    ]);
})->middleware('throttle:600,1')->name('vidio.hls');

/*
 * Proxy gambar: dipakai supaya URL aset pihak ketiga (yang memuat nama
 * provider) tidak muncul di HTML publik.
 */
Route::get('/api/img', function (\Illuminate\Http\Request $request) {
    $url = \App\Services\ImageProxyService::decode((string) $request->query('u', ''));

    if (!$url || !\App\Services\ImageProxyService::allowed($url)) {
        abort(404);
    }

    return \App\Services\ImageProxyService::fetch($url);
})->middleware('throttle:600,1')->name('img.proxy');

// Video anime: redirect 302 ke sumber, supaya HTML tidak memuat host provider
// tapi bandwidth tetap langsung dari sumber (tidak lewat server kita).
Route::get('/api/av', function (\Illuminate\Http\Request $request) {
    $url = \App\Services\ImageProxyService::decode((string) $request->query('u', ''));

    if (!$url || !str_starts_with($url, 'https://indocast.site/api/animekompi/proxy')) {
        abort(404);
    }

    return redirect()->away($url, 302)->header('Cache-Control', 'no-store');
})->name('api.anime-video');
