<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Services\GoogleDriveService;
use App\Services\AnoboyScraperService;
use Spatie\GoogleCalendar\Event;

// Route::get('/admin/login')->name('login');

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home.index');
    Route::get('/films', 'films')->name('home.films');
    Route::get('/about', 'about')->name('home.about');
    Route::get('/contact', 'contact')->name('home.contact');
    Route::get('/preview', 'index_product')->name('home.index_product');
    // Route::get('/test', 'index_preview')->name('home.index_preview');
});

// Route::get('/drive-preview', function (GoogleDriveService $driveService) {
    // $folderId = '1KP-_LpsLn-yax1fYrQZqOh_IcEQOrxw0';

    // $images = $driveService->listImages($folderId);

//     return view('landingpage.preview_', compact('images'));
// });
Route::controller(GoogleDriveService::class)->group(function () {
    // Route::get('/drive-preview', 'index')->name('drive.index');
    Route::post('/download-data', 'download')->name('drive.download-data');
});
Route::get('/test-calendar', function () {
    return Event::get();
});

// Anime episode video URL endpoint (used by Filament admin modal)
Route::get('/api/anime/episode-video', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');

    if (empty($url)) {
        return response()->json(['error' => 'URL is required'], 400);
    }

    $scraper = new AnoboyScraperService();
    $detail = $scraper->scrapeEpisodeDetail($url);

    if (!$detail) {
        return response()->json(['error' => 'Failed to fetch episode detail'], 500);
    }

    return response()->json([
        'title' => $detail['title'],
        'video_url' => $detail['video_url'] ?? '',
    ]);
})->name('api.anime.episode-video');

Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    
    \Filament\Notifications\Notification::make()
        ->title('Cache Berhasil Dihapus')
        ->success()
        ->send();

    return redirect()->back();
})->name('clear.cache');

Route::get('/api/search', function (\Illuminate\Http\Request $request) {
    $q = $request->query('q', '');
    if (empty($q)) {
        return response()->json([]);
    }
    
    $results = \App\Services\StreamingService::search($q);
    
    foreach ($results as &$item) {
        $isSeries = isset($item['type']) && in_array($item['type'], ['tv_series', 'tv', 'series']);
        $item['url'] = $isSeries 
            ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
            : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
    }
    
    return response()->json($results);
})->name('api.search.proxy');

Route::get('/api/proxy-stream', function (\Illuminate\Http\Request $request) {
    $url = $request->get('url');
    if (!$url) {
        return response('Missing URL', 400);
    }

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $scheme = $parsedUrl['scheme'] ?? 'https';

    // Referer/Origin sesuai host asal (khusus untuk bilibili.tv gunakan domain kanonisnya).
    if (str_contains($host, 'bilibili.tv')) {
        $referer = 'https://www.bilibili.tv/';
        $origin = 'https://www.bilibili.tv';
    } else {
        $referer = "{$scheme}://{$host}/";
        $origin = "{$scheme}://{$host}";
    }

    $reqHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: */*',
        'Connection: keep-alive',
        'Referer: ' . $referer,
        'Origin: ' . $origin,
    ];
    if ($request->hasHeader('Range')) {
        $reqHeaders[] = 'Range: ' . $request->header('Range');
    }

    // Handle HEAD request to avoid downloading the body
    $isHeadRequest = $request->isMethod('HEAD');

    // Direct cURL → php://output. Menghindari overhead Guzzle + response()->stream
    // yang biasanya menjadi bottleneck untuk banyak segmen HLS berjalan paralel.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $statusCode = 200;
    $headersSent = false;
    $headersBuffer = [];
    $forwardHeaders = ['content-type', 'content-length', 'content-range', 'accept-ranges', 'etag', 'last-modified'];

    $ch = curl_init();
    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $reqHeaders,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_BUFFERSIZE => 131072, // 128 KB per read
        CURLOPT_ACCEPT_ENCODING => '', // izinkan gzip/deflate/br dari upstream, auto-decode
        CURLOPT_HEADERFUNCTION => function ($_ch, $headerLine) use (&$statusCode, &$headersBuffer, &$headersSent, $forwardHeaders) {
            $trim = trim($headerLine);
            
            // Baris kosong menandakan akhir dari header block HTTP response ini
            if ($trim === '') {
                // Hanya kirim header ke client jika response BUKAN redirect (3xx)
                if ($statusCode < 300 || $statusCode >= 400) {
                    if (!headers_sent() && !$headersSent) {
                        http_response_code($statusCode);
                        header('Access-Control-Allow-Origin: *');
                        header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
                        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Range');
                        header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
                        // Segment .ts memiliki auth_key yang stabil; cache di browser 1 jam mempercepat seek/replay.
                        header('Cache-Control: public, max-age=3600');
                        
                        foreach ($headersBuffer as $h) {
                            header($h);
                        }
                        $headersSent = true;
                    }
                }
                return strlen($headerLine);
            }

            if (stripos($trim, 'HTTP/') === 0) {
                if (preg_match('~HTTP/\S+\s+(\d+)~', $trim, $m)) {
                    $statusCode = (int) $m[1];
                }
                // Bersihkan buffer header karena ini bisa jadi respon redirect baru
                $headersBuffer = [];
                return strlen($headerLine);
            }

            if (str_contains($trim, ':')) {
                [$name, $value] = explode(':', $trim, 2);
                if (in_array(strtolower(trim($name)), $forwardHeaders, true)) {
                    $headersBuffer[] = $trim;
                }
            }
            return strlen($headerLine);
        },
        CURLOPT_WRITEFUNCTION => function ($_ch, $data) use (&$headersSent, &$statusCode, &$headersBuffer) {
            if (!$headersSent && !headers_sent()) {
                http_response_code($statusCode);
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
                header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Range');
                header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
                header('Cache-Control: public, max-age=3600');
                foreach ($headersBuffer as $h) {
                    header($h);
                }
                $headersSent = true;
            }
            $len = strlen($data);
            echo $data;
            return $len;
        },
    ];

    if ($isHeadRequest) {
        $curlOptions[CURLOPT_NOBODY] = true;
    }

    curl_setopt_array($ch, $curlOptions);

    curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err && !$headersSent) {
        if (!headers_sent()) {
            http_response_code(502);
            header('Content-Type: text/plain');
            echo 'Upstream error: ' . $err;
        }
    }
    // Handler cURL sudah mengirim body langsung ke output. Hentikan Laravel di sini
    // agar tidak menambahkan wrapper response tambahan.
    exit;
})->name('api.proxy-stream');

Route::get('/api/proxy-hls', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    if (empty($url)) {
        abort(400, 'URL is required');
    }

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $scheme = $parsedUrl['scheme'] ?? 'https';

    // Domain HLS yang diperbolehkan.
    $allowedDomains = ['shorttv.live', 'goodbos.online', 'dramabos.fun', 'netshort.com', 'b-cdn.net', 'ibyteimg.com', 'tiktokcdn.com', 'goodshort.com', 'goodreels.com'];
    $allowed = false;
    foreach ($allowedDomains as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        abort(403, 'Unauthorized domain');
    }

    try {
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/vnd.apple.mpegurl,application/x-mpegURL,*/*',
                'Referer' => "{$scheme}://{$host}/",
                'Origin' => "{$scheme}://{$host}",
            ])
            ->get($url);
    } catch (\Exception $e) {
        return response('Upstream fetch failed: ' . $e->getMessage(), 502);
    }

    if (!$response->successful()) {
        return response('Upstream returned ' . $response->status(), 502);
    }

    $body = $response->body();

    // Direktori base (untuk URL relatif) dan host base (untuk URL absolute path).
    $basePath = parse_url($url, PHP_URL_PATH) ?: '/';
    $baseDir = $scheme . '://' . $host . preg_replace('~/[^/]*$~', '/', $basePath);
    $baseAbs = $scheme . '://' . $host;
    // Bunny memakai token query dengan token_path. Segmen relatif harus membawa token
    // yang sama agar manifest signed dapat diputar sampai selesai.
    $inheritedQuery = str_ends_with($host, '.b-cdn.net')
        ? (parse_url($url, PHP_URL_QUERY) ?: null)
        : null;

    $resolve = function (string $ref) use ($baseDir, $baseAbs, $scheme, $host, $inheritedQuery): string {
        $ref = trim($ref);

        if ($ref === '') return $ref;

        if (preg_match('#^https?://#i', $ref)) {
            $absolute = $ref;
        } elseif (str_starts_with($ref, '//')) {
            $absolute = $scheme . ':' . $ref;
        } elseif (str_starts_with($ref, '/')) {
            $absolute = $baseAbs . $ref;
        } else {
            $absolute = $baseDir . $ref;
        }

        $absoluteHost = parse_url($absolute, PHP_URL_HOST);

        if (
            $inheritedQuery !== null
            && $absoluteHost === $host
            && parse_url($absolute, PHP_URL_QUERY) === null
        ) {
            $absolute .= '?' . $inheritedQuery;
        }

        return $absolute;
    };

    $wrap = function (string $absUrl): string {
        return url('/api/proxy-stream?url=' . urlencode($absUrl));
    };

    $lines = preg_split('/\r?\n/', $body);
    $out = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '') {
            $out[] = $line;
            continue;
        }
        $videoKey = request()->query('key');

        if (str_starts_with($trimmed, '#')) {
            // Rewrite URIs inside directive tags (mis. #EXT-X-KEY:URI="...", #EXT-X-MEDIA:URI="...").
            // Audio/subtitle rendition sering berupa playlist .m3u8 terpisah, sehingga harus
            // di-proxy lewat proxy-hls agar segmen di dalamnya ikut ditulis ulang (mewarisi token).
            if (str_contains($line, 'URI="')) {
                if ($videoKey && preg_match('/URI="local:\/\/[^"]*"/i', $line)) {
                    $line = preg_replace('/URI="local:\/\/[^"]*"/i', 'URI="data:text/plain;base64,' . $videoKey . '"', $line);
                } else {
                    $line = preg_replace_callback('/URI="([^"]+)"/i', function ($m) use ($resolve, $wrap, $videoKey) {
                        $abs = $resolve($m[1]);
                        $target = preg_match('~\.m3u8(\?|$)~i', $abs)
                            ? url('/api/proxy-hls?url=' . urlencode($abs) . ($videoKey ? '&key=' . urlencode($videoKey) : ''))
                            : $wrap($abs);

                        return 'URI="' . $target . '"';
                    }, $line);
                }
            }
            $out[] = $line;
            continue;
        }
        // Baris konten: playlist nested atau segmen media. Bungkus lewat proxy-stream.
        // Jika ini nested m3u8, bisa dibungkus recursive lewat proxy-hls; sederhananya kita
        // proxy-stream saja karena segmen di dalamnya sudah URL absolute setelah rewrite oleh
        // proxy-stream tidak dilakukan — jadi jika file adalah m3u8, biarkan tetap proxy-hls.
        $abs = $resolve($trimmed);
        $isPlaylist = preg_match('~\.m3u8(\?|$)~i', $abs);
        $out[] = $isPlaylist
            ? url('/api/proxy-hls?url=' . urlencode($abs) . ($videoKey ? '&key=' . urlencode($videoKey) : ''))
            : $wrap($abs);
    }

    return response(implode("\n", $out), 200, [
        'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Range',
        'Cache-Control' => 'no-store',
    ]);
})->name('api.proxy-hls');

Route::get('/api/proxy-image', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');

    if (empty($url)) {
        abort(400, 'URL is required');
    }

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $allowedDomains = ['dramabuzz.sbs', 'goodbos.online', 'dramabos.fun', 'wsrv.nl', 'lh3.googleusercontent.com', 'tiktokcdn.com', 'ibyteimg.com', 'netshort.com', 'shorttv.live', 'raptdrama.com', 'goodreels.com', 'goodshort.com'];
    $allowed = false;
    foreach ($allowedDomains as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        abort(403, 'Unauthorized domain');
    }

    $cacheKey = 'img_proxy_' . md5($url);
    $cacheDuration = 86400 * 7; // Cache for 7 days

    $imageData = \Illuminate\Support\Facades\Cache::remember($cacheKey, $cacheDuration, function () use ($url) {
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                ])
                ->get($url);
            if ($response->successful()) {
                return [
                    'content' => base64_encode($response->body()),
                    'content_type' => $response->header('Content-Type') ?: 'image/png'
                ];
            }
        } catch (\Exception $e) {
            // Log or ignore
        }
        return null;
    });

    if (!$imageData) {
        abort(404, 'Image not found');
    }

    return response(base64_decode($imageData['content']))
        ->header('Content-Type', $imageData['content_type'])
        ->header('Cache-Control', 'public, max-age=604800');
})->name('api.proxy-image');

Route::get('/api/proxy-subtitle', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');

    if (empty($url)) {
        abort(400, 'URL is required');
    }

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $allowedDomains = ['netshort.com', 'goodbos.online', 'dramabos.fun', 'dramabuzz.sbs'];
    $allowed = false;
    foreach ($allowedDomains as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        abort(403, 'Unauthorized domain');
    }

    $cacheKey = 'sub_proxy_' . md5($url);

    $body = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($url) {
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return null;
    });

    if ($body === null) {
        abort(404, 'Subtitle not found');
    }

    // Strip UTF-8 BOM if present so the WEBVTT signature is at the very start.
    if (str_starts_with($body, "\xEF\xBB\xBF")) {
        $body = substr($body, 3);
    }

    // Ensure WEBVTT header (some sources return plain text SRT-like — but netshort uses WebVTT)
    if (!str_starts_with(ltrim($body), 'WEBVTT')) {
        $body = "WEBVTT\n\n" . $body;
    }

    return response($body, 200, [
        'Content-Type' => 'text/vtt; charset=utf-8',
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('api.proxy-subtitle');

Route::get('/register-success', function () {
    return view('auth.register-success');
})->name('register.success');
