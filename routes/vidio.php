<?php

use Illuminate\Support\Facades\Route;

/*
 * Proxy HLS khusus Vidio.
 *
 * Playlist .m3u8 ditulis ulang supaya semua URI (variant, audio, subtitle,
 * segmen) mengarah balik ke proxy ini, sehingga pemutar cukup memakai satu
 * origin dan bebas masalah CORS. Segmen diteruskan apa adanya.
 */
Route::get('/api/hls/{key?}', function (\Illuminate\Http\Request $request, ?string $key = null) {
    $key = (string) ($key !== null ? preg_replace('/\.[a-z0-9]+$/i', '', $key) : $request->query('k', ''));
    $encoded = (string) $request->query('u', '');
    $url = $key !== ''
        ? \App\Services\StreamProxy::resolve($key)
        : \App\Services\VidioService::decodeProxyUrl($encoded);

    if (!$url || !\App\Services\VidioService::isAllowedHost($url)) {
        abort(400, 'URL tidak diizinkan');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',   // dekompres gzip/br; tanpa ini segmen rusak
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: */*', 'Referer: https://www.vidio.com/'],
    ]);

    // Range terbuka ("bytes=0-") membuat upstream menahan koneksi sampai file
    // selesai; ubah jadi potongan 8 MB supaya pemutaran langsung mulai.
    $rangeStart = null;
    $rangeUnknownTotal = false;

    if ($request->headers->has('Range') && preg_match('/bytes=(\d+)-(\d*)/', (string) $request->header('Range'), $m)) {
        $rangeStart = (int) $m[1];
        $rangeEnd = $m[2] !== '' ? (int) $m[2] : null;

        if ($rangeEnd === null) {
            $rangeEnd = $rangeStart + 2 * 1024 * 1024 - 1;
            $rangeUnknownTotal = true;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Accept: */*', 'Referer: https://www.vidio.com/', 'Range: bytes=' . $rangeStart . '-' . $rangeEnd]
        ));
    } else {
        // Segmen (bukan playlist) tanpa Range bisa menggantung di upstream;
        // minta potongan 4 MB. Playlist tetap tanpa Range.
        $looksPlaylist = str_contains(strtolower(urldecode(urldecode($url))), '.m3u8');

        if (! $looksPlaylist) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                ['Accept: */*', 'Referer: https://www.vidio.com/', 'Range: bytes=0-4194303']
            ));
        }
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

    $headers = [
        'Content-Type' => $type ?: 'application/octet-stream',
        'Access-Control-Allow-Origin' => '*',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'no-store',
    ];

    if ($rangeStart !== null) {
        $last = $rangeStart + max(0, strlen($body) - 1);

        $total = null;
        if ($rangeUnknownTotal) {
            // Chrome menolak 206 tanpa total; ambil ukuran file sekali dari
            // permintaan 1 byte lalu simpan sebentar.
            $total = \Illuminate\Support\Facades\Cache::remember('kz_size_' . md5($url), 900, function () use ($url) {
                $probe = curl_init($url);
                curl_setopt_array($probe, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_NOBODY => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_NOBODY => true,
                    CURLOPT_HEADER => true,
                ]);
                $head = (string) curl_exec($probe);
                curl_close($probe);

                if (preg_match('/content-length:\s*(\d+)/i', $head, $m)) {
                    return (int) $m[1];
                }

                $probe = curl_init($url);
                curl_setopt_array($probe, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_RANGE => '0-0',
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);
                $head = (string) curl_exec($probe);
                curl_close($probe);

                if (preg_match('~/content-range:\s*bytes\s+\d+-\d+/(\d+)~i', $head, $m)) {
                    return (int) $m[1];
                }

                return 0;
            });
        }

        $headers['Content-Range'] = 'bytes ' . $rangeStart . '-' . $last . '/' . ($total ?: ($rangeUnknownTotal ? '*' : ''));
    }

    if (strlen($body) > 0) {
        $headers['Content-Length'] = (string) strlen($body);
    }

    return response($body, $status === 0 ? 200 : $status, $headers);
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

    // Teruskan lewat domain sendiri dengan token acak; URL CDN langsung
    // sering terkunci IP, jadi tidak bisa diarahkan begitu saja.
    return redirect()->away(\App\Services\StreamProxy::url($url, 'mp4'), 302)->header('Cache-Control', 'no-store');
})->name('api.anime-video');
