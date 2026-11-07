<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PencuriMovieService
{
    protected static string $baseUrl = 'https://ww44.pencurimovie.baby';

    protected static function getClient(): Client
    {
        return new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            'timeout' => 12,
        ]);
    }

    /**
     * Ambil katalog film Indonesia dari PencuriMovie (dengan filter sensor otomatis)
     */
    public static function getIndonesiaCatalog(int $page = 1): array
    {
        $cacheKey = "pm_catalog_p{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($page) {
            try {
                $client = self::getClient();
                $url = $page > 1
                    ? self::$baseUrl . "/country/indonesia/page/{$page}/"
                    : self::$baseUrl . "/country/indonesia/";

                $res = $client->get($url);
                if ($res->getStatusCode() !== 200) {
                    return ['data' => [], 'hasMore' => false];
                }

                $html = (string) $res->getBody();
                
                // Cari setiap item kartu ml-item
                preg_match_all('/<div\s+data-movie-id="(\d+)"\s+class="ml-item">(.*?)<\/div>\s*<\/div>/s', $html, $matches, PREG_SET_ORDER);

                if (empty($matches)) {
                    preg_match_all('/<div\s+class="ml-item"[^>]*>(.*?)<\/div>\s*<\/div>/s', $html, $matches, PREG_SET_ORDER);
                }

                $items = [];
                foreach ($matches as $m) {
                    $itemHtml = $m[0];

                    // URL & Title
                    if (!preg_match('/<a\s+href="([^"]+)"[^>]*oldtitle="([^"]+)"/i', $itemHtml, $am)) {
                        if (!preg_match('/<a\s+href="([^"]+)"[^>]*title="([^"]+)"/i', $itemHtml, $am)) {
                            continue;
                        }
                    }

                    $watchUrl = trim($am[1] ?? '');
                    $rawTitle = html_entity_decode(trim($am[2] ?? ''), ENT_QUOTES, 'UTF-8');
                    if (empty($rawTitle) || empty($watchUrl) || $watchUrl === 'javascript:void(0)') continue;

                    // Tahun
                    $year = null;
                    if (preg_match('/\((\d{4})\)/', $rawTitle, $ym)) {
                        $year = $ym[1];
                    }

                    $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $rawTitle));

                    // Poster
                    $poster = null;
                    if (preg_match('/<img[^>]+src="([^"]+)"[^>]*class="[^"]*mli-thumb/i', $itemHtml, $im)) {
                        $poster = $im[1];
                        // Tingkatkan resolusi poster TMDB jika w185
                        $poster = str_replace('/t/p/w185/', '/t/p/w500/', $poster);
                    }

                    // Kualitas
                    $quality = 'HD';
                    if (preg_match('/class="mli-quality-text">([^<]+)<\/span>/i', $itemHtml, $qm)) {
                        $quality = strtoupper(trim($qm[1]));
                    }

                    // Slug
                    $pathParts = explode('/', trim(parse_url($watchUrl, PHP_URL_PATH), '/'));
                    $rawSlug = end($pathParts);
                    if (empty($rawSlug)) continue;

                    $card = [
                        'id' => 'pm-' . $rawSlug,
                        'title' => $cleanTitle,
                        'slug' => 'pm-' . $rawSlug,
                        'raw_slug' => $rawSlug,
                        'type' => 'movie',
                        'year' => $year,
                        'rating' => 7.8,
                        'voteAverage' => 7.8,
                        'quality' => $quality,
                        'duration' => null,
                        'poster' => $poster,
                        'thumbnail' => $poster,
                        'banner' => $poster,
                        'genres' => ['Indonesia', 'Bioskop'],
                        'tags' => ['Indonesia', 'Film Bioskop'],
                        'url' => $watchUrl,
                        'watch_url' => $watchUrl,
                        'external_id' => $m[1] ?? null,
                        'isEmbed' => true,
                        'source' => 'pencurimovie',
                        'provider_badge' => 'Server 4',
                    ];

                    // SENSOR KONTEN DEWASA & GENRE SEMI
                    if (MediaContentSanitizerService::isAdult($card)) {
                        continue;
                    }

                    $items[] = $card;
                }

                $hasMore = str_contains($html, '/country/indonesia/page/' . ($page + 1) . '/');

                return [
                    'data' => $items,
                    'hasMore' => $hasMore,
                ];
            } catch (\Throwable $e) {
                Log::warning("PencuriMovieService::getIndonesiaCatalog error: " . $e->getMessage());
                return ['data' => [], 'hasMore' => false];
            }
        });
    }

    /**
     * Ekstrak direct stream MP4 dari Streamtape (Bebas Iklan 100%)
     */
    public static function resolveStreamtapeDirectMp4(string $embedUrl): ?string
    {
        $cacheKey = "st_direct_mp4_" . md5($embedUrl);
        return Cache::remember($cacheKey, 7200, function () use ($embedUrl) {
            try {
                $client = self::getClient();
                $res = $client->get($embedUrl);
                $html = (string) $res->getBody();

                // Match robotlink innerHTML
                if (preg_match("/document\.getElementById\(['\"]robotlink['\"]\)\.innerHTML\s*=\s*([^\n;]+);/", $html, $m)) {
                    $expr = trim($m[1]);
                    // Evaluasi ekspresi javascript dengan node
                    $escaped = escapeshellarg("console.log(" . $expr . ")");
                    $output = trim((string) shell_exec("node -e " . $escaped));

                    if ($output && !str_contains($output, 'undefined') && !str_contains($output, 'null')) {
                        $fullUrl = str_starts_with($output, '//') ? ('https:' . $output) : $output;

                        // Follow redirect to obtain direct CDN tapecontent.net MP4 URL
                        $ch = curl_init($fullUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_NOBODY, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Referer: https://streamtape.com/',
                        ]);
                        curl_exec($ch);
                        $finalCdnUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($code === 200 && $finalCdnUrl && str_contains($finalCdnUrl, 'tapecontent')) {
                            return $finalCdnUrl;
                        }

                        return $fullUrl;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("PencuriMovieService::resolveStreamtapeDirectMp4 failed: " . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Ambil detail film dari PencuriMovie lengkap dengan server bebas iklan
     */
    public static function getMovieDetail(string $slug): ?array
    {
        $rawSlug = preg_replace('/^pm-/', '', $slug);
        $cacheKey = "pm_movie_detail_" . md5($rawSlug);

        return Cache::remember($cacheKey, 3600, function () use ($rawSlug, $slug) {
            try {
                $vaultItem = \App\Models\IndonesiaMediaItem::where('slug', $slug)->first();
                $watchUrl = $vaultItem?->watch_url ?: (self::$baseUrl . '/' . $rawSlug . '/');

                $client = self::getClient();
                $res = $client->get($watchUrl);
                if ($res->getStatusCode() !== 200) {
                    return null;
                }

                $html = (string) $res->getBody();

                // Judul dari og:title atau h2/h1
                $cleanTitle = '';
                $year = '';
                if (preg_match('/<meta[^>]*property=[\x27\x22]og:title[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i', $html, $ogm)) {
                    $rawTitle = html_entity_decode(trim($ogm[1]), ENT_QUOTES, 'UTF-8');
                    $cleanTitle = trim(preg_replace('/\s*-\s*Pencuri\s*Movie.*$/i', '', $rawTitle));
                    if (preg_match('/\((\d{4})\)/', $cleanTitle, $ym)) {
                        $year = $ym[1];
                    }
                    $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $cleanTitle));
                }

                if (empty($cleanTitle) && $vaultItem) {
                    $cleanTitle = $vaultItem->title;
                    $year = $vaultItem->year;
                }
                if (empty($cleanTitle)) {
                    $cleanTitle = ucwords(str_replace('-', ' ', $rawSlug));
                }

                // Sinopsis
                $synopsis = '';
                if (preg_match('/class="[^"]*f-desc[^"]*"[^>]*>(.*?)<\/div>/s', $html, $dm)) {
                    $synopsis = html_entity_decode(trim(strip_tags($dm[1])), ENT_QUOTES, 'UTF-8');
                }

                // Poster dari og:image atau thumb
                $poster = null;
                if (preg_match('/<meta[^>]*property=[\x27\x22]og:image[\x27\x22][^>]*content=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i', $html, $ogim)) {
                    $poster = $ogim[1];
                    $poster = str_replace(['/w185/', '/w300/'], '/w780/', $poster);
                }
                if (empty($poster) && preg_match('/<img[^>]+class="[^"]*thumb[^"]*"[^>]*src="([^"]+)"/i', $html, $im)) {
                    $poster = $im[1];
                    $poster = str_replace(['/w185/', '/w300/'], '/w780/', $poster);
                }
                if (empty($poster) && $vaultItem) {
                    $poster = $vaultItem->poster;
                }

                // Ekstrak semua server iframe yang tersedia
                preg_match_all('/<iframe[^>]+data-src="([^"]+)"/i', $html, $ifm);
                $rawEmbeds = $ifm[1] ?? [];

                $streamtapeEmbed = null;
                $directMp4 = null;
                $servers = [];

                // Cari streamtape dulu untuk diekstrak menjadi MP4 langsung
                foreach ($rawEmbeds as $u) {
                    if (str_contains($u, 'streamtape.com')) {
                        $streamtapeEmbed = $u;
                        $directMp4 = self::resolveStreamtapeDirectMp4($u);
                        break;
                    }
                }

                // Jika berhasil mengekstrak direct MP4, jadikan SERVER UTAMA (Native Artplayer Bebas Iklan via Stream Relay)
                if ($directMp4) {
                    $relayedVideoUrl = url('/api/proxy-stream?url=' . urlencode($directMp4));
                    $servers[] = [
                        'id' => 'server_pm_direct',
                        'name' => 'Server 4 (Native HD - Bebas Iklan)',
                        'type' => 'mp4',
                        'video_url' => $relayedVideoUrl,
                        'embed_url' => '',
                    ];
                } else {
                    // Jika Streamtape tidak ada/404, coba ekstrak Direct HLS (m3u8) dari HGCloud / Vibuxer untuk Artplayer Native!
                    foreach ($rawEmbeds as $u) {
                        if (str_contains($u, 'hgcloud.') || str_contains($u, 'hglink.') || str_contains($u, 'vibuxer.')) {
                            $hgM3u8 = DirectStreamResolverService::extractHgCloudDirectM3u8($u);
                            if ($hgM3u8) {
                                $relayedVideoUrl = $hgM3u8;
                                $servers[] = [
                                    'id' => 'server_pm_direct_hls',
                                    'name' => 'Server 4 (Native HD - Bebas Iklan)',
                                    'type' => 'hls',
                                    'video_url' => $hgM3u8,
                                    'embed_url' => '',
                                ];
                                break;
                            }
                        }
                    }
                }

                // Masukkan server-server mirror pendukung (dengan sandbox anti-iklan)
                $idx = 1;
                foreach ($rawEmbeds as $u) {
                    $host = parse_url($u, PHP_URL_HOST);
                    $nameLabel = 'Server 4 (Mirror ' . $idx . ')';
                    if (str_contains($host, 'streamtape')) {
                        $nameLabel = 'Server 4 (Streamtape)';
                    } elseif (str_contains($host, 'hgcloud')) {
                        $nameLabel = 'Server 4 (HGCloud HD)';
                    } elseif (str_contains($host, 'playmogo')) {
                        $nameLabel = 'Server 4 (PlayMogo)';
                    } elseif (str_contains($host, 'voe.sx')) {
                        $nameLabel = 'Server 4 (VOE)';
                    }

                    $servers[] = [
                        'id' => 'server_pm_embed_' . $idx,
                        'name' => $nameLabel,
                        'type' => 'embed',
                        'embed_url' => $u,
                    ];
                    $idx++;
                }

                return [
                    'id' => $slug,
                    'title' => $cleanTitle,
                    'slug' => $slug,
                    'raw_slug' => $rawSlug,
                    'overview' => $synopsis ?: ($vaultItem?->synopsis ?? ''),
                    'poster' => $poster,
                    'posterPath' => $poster,
                    'backdrop' => $poster,
                    'backdropPath' => $poster,
                    'thumbnail' => $poster,
                    'voteAverage' => 7.8,
                    'year' => $year,
                    'country' => 'Indonesia',
                    'genres' => ['Indonesia', 'Bioskop'],
                    'video_url' => $relayedVideoUrl ?? ($directMp4 ?: ''),
                    'isEmbed' => empty($relayedVideoUrl) && empty($directMp4),
                    'source' => 'pencurimovie',
                    'servers' => $servers,
                ];
            } catch (\Throwable $e) {
                Log::warning("PencuriMovieService::getMovieDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }
}
