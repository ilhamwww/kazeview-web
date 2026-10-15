<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PhotoThumbnailController;
use App\Services\GoogleDriveService;
use App\Services\AnoboyScraperService;
use Spatie\GoogleCalendar\Event;

// Route::get('/admin/login')->name('login');

Route::get(
    '/media/photos/{photo}/thumbnail',
    PhotoThumbnailController::class,
)->whereNumber('photo')->name('photos.thumbnail');

// Legacy redirects from /streaming to clean root URLs
Route::redirect('/streaming', '/');
Route::get('/streaming/{any}', function ($any) {
    return redirect('/' . $any);
})->where('any', '.*');

Route::prefix('api/v1/public')
    ->middleware('throttle:120,1')
    ->name('api.public.')
    ->controller(PublicApiController::class)
    ->group(function (): void {
        Route::get('/bootstrap', 'bootstrap')->name('bootstrap');
        Route::get('/home', 'home')->name('home');
        Route::get('/films', 'films')->name('films');
        Route::get('/about', 'about')->name('about');
        Route::get('/contact', 'contact')->name('contact');
        Route::get('/preview', 'preview')->name('preview.index');
        Route::post(
            '/preview/{content}/ai-photo-search',
            'searchMotorPhotos',
        )
            ->whereNumber('content')
            ->middleware('throttle:10,1')
            ->name('preview.ai-photo-search');
        Route::get(
            '/preview/{content}',
            'previewDetail',
        )
            ->whereNumber('content')
            ->name('preview.show');
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

    $detail = null;
    if (str_contains($url, 'anoboy')) {
        $anoboyScraper = new \App\Services\AnoboyScraperService();
        $detail = $anoboyScraper->scrapeEpisodeDetail($url);
    } else {
        $otakudesuScraper = new \App\Services\OtakudesuScraperService();
        $detail = $otakudesuScraper->scrapeEpisodeDetail($url);
    }

    if (!$detail) {
        return response()->json(['error' => 'Failed to fetch episode detail'], 500);
    }

    return response()->json([
        'title' => $detail['title'] ?? '',
        'video_url' => $detail['video_url'] ?? '',
        'direct_mp4' => $detail['direct_mp4'] ?? null,
        'iframe_url' => $detail['iframe_url'] ?? '',
        'quality' => $detail['quality'] ?? 'HD',
    ]);
})->name('api.anime.episode-video');

// Asynchronous Mirror Resolver for Otakudesu Anime
Route::get('/api/anime/resolve-mirror', function (\Illuminate\Http\Request $request) {
    $episodeUrl = $request->query('url');
    $id = (int) $request->query('id');
    $i = (int) $request->query('i');
    $q = $request->query('q', '720p');
    $server = $request->query('server', '');

    if (empty($episodeUrl) || empty($id)) {
        return response()->json(['error' => 'Missing url or id parameter'], 400);
    }

    $scraper = new \App\Services\OtakudesuScraperService();
    $resolved = $scraper->resolveMirror($episodeUrl, $id, $i, $q, $server);

    if (!$resolved) {
        return response()->json(['error' => 'Failed to resolve mirror stream'], 500);
    }

    return response()->json($resolved);
})->name('api.anime.resolve-mirror');

Route::get('/api/streaming/stream', function (\Illuminate\Http\Request $request) {
    $type = $request->query('type');
    $id = $request->query('id');
    $force = $request->boolean('force') || $request->query('force') === '1';

    if (empty($id)) {
        return response()->json(['error' => 'ID is required'], 400);
    }

    try {
        if ($type === 'movie') {
            $stream = \App\Services\StreamingService::getMovieStream($id, $force);
        } elseif ($type === 'series' || $type === 'episode') {
            $stream = \App\Services\StreamingService::getEpisodeStream($id, $force);
        } else {
            return response()->json(['error' => 'Invalid stream type'], 400);
        }

        if (!$stream || empty($stream['video_url'])) {
            return response()->json(['error' => 'Stream not available'], 404);
        }

        return response()->json($stream);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Stream API error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load stream'], 500);
    }
})->name('api.streaming.stream');

// Laporan error pemutar video dari browser (diagnosa "stuck"). Dibatasi agar tidak bisa dipakai spam.
Route::get('/api/player-log', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::channel('daily')->warning('[player] ' . json_encode([
        'kind' => mb_substr((string) $request->query('kind'), 0, 40),
        'detail' => mb_substr((string) $request->query('detail'), 0, 300),
        'type' => mb_substr((string) $request->query('type'), 0, 10),
        'id' => mb_substr((string) $request->query('id'), 0, 64),
        'proxy' => $request->query('proxy') === '1',
        'ua' => mb_substr((string) $request->userAgent(), 0, 160),
        'user' => auth()->id(),
    ]));
    return response()->noContent();
})->middleware('throttle:30,1')->name('api.player-log');

Route::get('/api/streaming/embed', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        abort(400, 'Invalid URL');
    }

    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    $scheme = $parsed['scheme'] ?? 'https';
    $origin = "{$scheme}://{$host}";

    $ua = $request->userAgent() ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    $cacheKey = 'embed_html_' . md5($url . $ua);
    $cookieFile = '/tmp/juicy_cookie_' . md5($request->ip()) . '.txt';
    $html = \Illuminate\Support\Facades\Cache::remember($cacheKey, 10, function () use ($url, $origin, $ua, $cookieFile) {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: ' . $ua,
                'Referer: https://rebahinxxi3.mom/',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]);
            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200 || empty($content)) {
                return null;
            }

            // Inject base tag agar asset JWPlayer load sempurna
            if (str_contains($content, '<head>')) {
                $content = str_replace('<head>', "<head><base href=\"{$origin}/\">", $content);
            } elseif (str_contains($content, '<html')) {
                $content = preg_replace('/(<html[^>]*>)/i', "$1<head><base href=\"{$origin}/\"></head>", $content);
            }

            // Netralkan popup window.open & bypass CORS untuk stream/ping XHR
            $antiPopScript = <<<'HTML'
<script>
window.open = function() { console.log("Popup blocked"); return null; };
(function() {
    const PROXY_BASE = 'https://www.kazeview.com/api/streaming/embed-proxy?url=';
    function wrapUrl(u) {
        if (!u) return u;
        if (typeof u === 'string') {
            if (u.includes('groovy.monster') || u.includes('178.211.139.171') || u.includes('199.87.210.226') || u.includes('/api/videos/')) {
                if (u.startsWith('/')) {
                    u = 'https://178.211.139.171' + u;
                }
                return PROXY_BASE + encodeURIComponent(u);
            }
        }
        return u;
    }
    const origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        return origOpen.call(this, method, wrapUrl(url), ...rest);
    };
    const origFetch = window.fetch;
    window.fetch = function(input, init) {
        if (typeof input === 'string') {
            input = wrapUrl(input);
        } else if (input && input.url) {
            input = new Request(wrapUrl(input.url), input);
        }
        return origFetch.call(this, input, init);
    };
})();
</script>
HTML;
            $content = str_replace('</head>', "{$antiPopScript}</head>", $content);

            return $content;
        } catch (\Exception $e) {
            return null;
        }
    });

    if (!$html) {
        return response('<div style="color:#fff;background:#000;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;">Server video sedang sibuk, silakan coba server lain.</div>', 200, ['Content-Type' => 'text/html']);
    }

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=utf-8',
        'X-Frame-Options' => 'SAMEORIGIN',
    ]);
})->name('api.streaming.embed');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/streaming/embed-proxy', function (\Illuminate\Http\Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }

    $targetUrl = $request->query('url');
    if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        abort(400, 'Invalid target URL');
    }

    $cookieFile = '/tmp/juicy_cookie_' . md5($request->ip()) . '.txt';
    $ch = curl_init($targetUrl);
    $ua = $request->userAgent() ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    $forwardHeaders = [
        'User-Agent: ' . $ua,
        'Referer: https://178.211.139.171/',
        'Origin: https://178.211.139.171',
    ];

    if ($range = $request->header('Range')) {
        $forwardHeaders[] = 'Range: ' . $range;
    }

    if ($request->isMethod('POST')) {
        curl_setopt($ch, CURLOPT_POST, true);
        $body = $request->getContent();
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $forwardHeaders[] = 'Content-Type: ' . ($request->header('Content-Type') ?: 'application/json');
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
        $len = strlen($header);
        if (preg_match('/^HTTP\/[\d\.]+\s+(\d+)/i', $header, $sm)) {
            http_response_code((int) $sm[1]);
            return $len;
        }
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $name = strtolower(trim($parts[0]));
            $val = trim($parts[1]);
            if (in_array($name, ['content-type', 'content-length', 'content-range', 'accept-ranges', 'content-disposition'])) {
                header("{$parts[0]}: {$val}");
            }
        }
        return $len;
    });

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: *');

    if (ob_get_level()) {
        ob_end_clean();
    }

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    exit;
})->name('api.streaming.embed-proxy');

Route::get('/api/streaming/subtitle', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    if (empty($url)) {
        return response('VTT URL is required', 400);
    }

    try {
        $vtt = \Illuminate\Support\Facades\Cache::remember('vtt_' . md5($url), 86400, function () use ($url) {
            $resp = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ])->timeout(10)->get($url);

            return $resp->successful() ? $resp->body() : null;
        });

        if (!$vtt) {
            return response('Failed to load subtitle', 404);
        }

        return response($vtt, 200, [
            'Content-Type' => 'text/vtt; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    } catch (\Throwable $e) {
        return response('Error fetching subtitle: ' . $e->getMessage(), 500);
    }
})->name('api.streaming.subtitle');

// Public Streaming Traffic Stats (100% PUBLIC)
Route::get('/traffic', function () {
    $stats = \App\Services\TrafficStatsService::getPublicStats();
    // Leaderboard memuat nama akun, jadi hanya untuk pengguna yang sudah login
    $leaderboard = auth()->check() ? \App\Services\TrafficStatsService::getLeaderboard() : null;
    return response(view('filament.streaming.layout', [
        'title' => 'Statistik Traffic Streaming - KZSTRM',
        'slot' => new \Illuminate\Support\HtmlString(view('filament.streaming.pages.traffic-stats', [
            'stats' => $stats,
            'leaderboard' => $leaderboard,
            'meId' => auth()->id(),
        ])->render())
    ])->render());
})->name('streaming.traffic.public');

// Public DMCA Takedown & Copyright Policy (100% PUBLIC)
Route::get('/dmca', function () {
    return response(view('filament.streaming.layout', [
        'title' => 'DMCA & Copyright Policy - KZSTRM',
        'slot' => new \Illuminate\Support\HtmlString(view('filament.streaming.pages.dmca')->render())
    ])->render());
})->name('streaming.dmca');

// TV Indonesia & Mancanegara Live Streaming Catalog (100% PUBLIC)
Route::get('/tv', function (\Illuminate\Http\Request $request) {
    $country = strtolower($request->query('country', 'id'));
    $channels = \App\Services\LiveTvService::getChannels($country);
    $categories = \App\Services\LiveTvService::getCategories($country);
    $countries = \App\Services\LiveTvService::getCountries();
    $currentCountry = \App\Services\LiveTvService::getCountryInfo($country);
    $countryTitle = $currentCountry['name'] ?? 'Indonesia';

    return response(view('filament.streaming.layout', [
        'title' => 'Live TV ' . $countryTitle . ' & Mancanegara Streaming 24 Jam - KZSTRM',
        'slot' => new \Illuminate\Support\HtmlString(view('filament.streaming.pages.live-tv', [
            'channels' => $channels,
            'categories' => $categories,
            'countries' => $countries,
            'currentCountry' => $currentCountry,
            'selectedCountry' => $country,
        ])->render())
    ])->render());
})->name('streaming.tv');

Route::get('/live-tv', fn(\Illuminate\Http\Request $request) => redirect('/tv' . ($request->getQueryString() ? '?' . $request->getQueryString() : '')));

// TV Live Player (100% PUBLIC)
Route::get('/tv/{slug}', function (\Illuminate\Http\Request $request, $slug) {
    $country = $request->query('country');
    $channel = \App\Services\LiveTvService::findBySlug($slug, $country);
    if (!$channel) {
        return redirect('/tv');
    }
    $channelCountry = $channel['country'] ?? 'id';
    $allChannels = \App\Services\LiveTvService::getChannels($channelCountry);
    $countries = \App\Services\LiveTvService::getCountries();
    $currentCountry = \App\Services\LiveTvService::getCountryInfo($channelCountry);

    return response(view('filament.streaming.layout', [
        'title' => 'Nonton ' . $channel['name'] . ' (' . ($channel['country_name'] ?? 'Live') . ') Live Streaming - KZSTRM',
        'slot' => new \Illuminate\Support\HtmlString(view('filament.streaming.pages.watch-tv', [
            'channel' => $channel,
            'allChannels' => $allChannels,
            'countries' => $countries,
            'currentCountry' => $currentCountry,
        ])->render())
    ])->render());
})->name('streaming.tv.watch');

Route::get('/tv/{country}/{slug}', function ($country, $slug) {
    $channel = \App\Services\LiveTvService::findBySlug($slug, $country);
    if (!$channel) {
        return redirect('/tv?country=' . $country);
    }
    $allChannels = \App\Services\LiveTvService::getChannels($country);
    $countries = \App\Services\LiveTvService::getCountries();
    $currentCountry = \App\Services\LiveTvService::getCountryInfo($country);

    return response(view('filament.streaming.layout', [
        'title' => 'Nonton ' . $channel['name'] . ' (' . ($channel['country_name'] ?? 'Live') . ') Live Streaming - KZSTRM',
        'slot' => new \Illuminate\Support\HtmlString(view('filament.streaming.pages.watch-tv', [
            'channel' => $channel,
            'allChannels' => $allChannels,
            'countries' => $countries,
            'currentCountry' => $currentCountry,
        ])->render())
    ])->render());
})->name('streaming.tv.watch.country');

Route::get('/live-tv/{slug}', fn($slug) => redirect('/tv/' . $slug));

Route::get('/stats', fn() => redirect('/traffic'));
Route::get('/bookmark', fn() => redirect('/watchlist'));
Route::get('/bookmarks', fn() => redirect('/watchlist'));
Route::get('/collections', fn() => redirect('/collection'));
Route::get('/networks', fn() => redirect('/network'));

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
    $category = $request->query('category', 'all');
    $limit = (int) $request->query('limit', 36);

    if (empty(trim($q))) {
        return response()->json([]);
    }
    
    $results = \App\Services\GlobalSearchService::search($q, $category, $limit);
    
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

    // Referer/Origin sesuai host asal (khusus untuk bilibili.tv dan CDN anime Mirainime gunakan domain kanonisnya).
    if (str_contains($host, 'bilibili.tv')) {
        $referer = 'https://www.bilibili.tv/';
        $origin = 'https://www.bilibili.tv';
    } elseif (str_contains($host, 'erisboreas.my.id') || str_contains($host, 'suzune.web.id') || str_contains($host, 'mirainime')) {
        $referer = 'https://mirainime.com/';
        $origin = 'https://mirainime.com';
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
    $allowedDomains = ['shorttv.live', 'goodbos.online', 'dramabos.fun', 'netshort.com', 'b-cdn.net', 'ibyteimg.com', 'tiktokcdn.com', 'goodshort.com', 'goodreels.com', 'crazymaplestudios.com', 'storyreel.life', 'cdreader.com', 'stardust-tv.co', 'stardust-tv.com', 'stardusttv.cc', 'vibeshort.live'];
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

    // Normalisasi gambar Melolo (ByteDance ImageX) jika URL lama mengarah ke apsoutheast atau signature expired
    if (str_contains($url, 'novel-images-') && preg_match('/novel-images-[a-z]+(?:%2F|\/)([0-9a-f]{32})/i', $url, $matches)) {
        $url = "https://p16-novel-sg.ibyteimg.com/img/novel-images-sg/{$matches[1]}~tplv-resize:570:810.jpg";
    }

    // Normalisasi double-slash pada path URL
    $url = preg_replace('#(?<!:)//#', '/', $url);

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $allowedDomains = ['dramabuzz.sbs', 'goodbos.online', 'dramabos.fun', 'wsrv.nl', 'lh3.googleusercontent.com', 'tiktokcdn.com', 'ibyteimg.com', 'netshort.com', 'shorttv.live', 'raptdrama.com', 'goodreels.com', 'goodshort.com', 'dramaboxdb.com', 'dramabox.com', 'shortolol.com', 'kintoto.xyz', 'crazymaplestudios.com', 'storyreel.life', 'cdreader.com', 'stardust-tv.co', 'assets.stardusttv.cc', 'stardusttv.cc', 'stardust-tv.com', 'vibeshort.live', 'rebahinxxi3.mom', 'rebahinxxi.mom', 'rebahinxxi3.pics', 'rebahinxxi3.casa', 'serealshort.com', 'serealplus.com'];
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

Route::get('/sitemap.xml', function () {
    $setting = \App\Models\WebsiteSetting::first();
    $seo = array_replace_recursive(
        \App\Models\WebsiteSetting::seoDefaults(),
        $setting?->seo_settings ?? [],
    );
    $baseUrl = rtrim(trim((string) ($seo['canonical_url'] ?? '')) ?: config('app.url'), '/');

    $urls = [
        ['loc' => $baseUrl . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => $baseUrl . '/films', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => $baseUrl . '/preview', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => $baseUrl . '/about', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => $baseUrl . '/contact', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ];

    $xml = view('seo.sitemap', ['urls' => $urls])->render();

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('seo.sitemap');

Route::get('/robots.txt', function () {
    $setting = \App\Models\WebsiteSetting::first();
    $seo = array_replace_recursive(
        \App\Models\WebsiteSetting::seoDefaults(),
        $setting?->seo_settings ?? [],
    );
    $baseUrl = rtrim(trim((string) ($seo['canonical_url'] ?? '')) ?: config('app.url'), '/');

    return response(
        "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin\n"
        . "Disallow: /streaming\n"
        . "Disallow: /api/\n"
        . "Sitemap: {$baseUrl}/sitemap.xml\n",
        200,
        ['Content-Type' => 'text/plain; charset=UTF-8'],
    );
})->name('seo.robots');

Route::get('/register-success', function () {
    return view('auth.register-success');
})->name('register.success');
