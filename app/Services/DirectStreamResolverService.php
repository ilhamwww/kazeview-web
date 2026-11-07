<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectStreamResolverService
{
    /**
     * Resolve direct m3u8 / mp4 stream for movie by title and year (Zero-NUC & Zero-Bandwidth Native Artplayer)
     */
    public static function resolveMovieStream(string $title, ?string $year = null, ?string $tmdbId = null): ?array
    {
        $cacheKey = 'direct_stream_movie_v2_' . md5(strtolower(trim($title)) . '_' . ($year ?? ''));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached['video_url'])) {
            return $cached;
        }

        $cleanTitle = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $title);
        $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));

        // 1. Cari di PencuriMovie (ww44.pencurimovie.baby) -> Ekstrak Direct HLS2 (cdn-centaurus) atau Direct MP4 (Streamtape)
        try {
            $slugCandidates = [];
            if ($year) {
                $slugCandidates[] = Str::slug($title . ' ' . $year);
            }
            $slugCandidates[] = Str::slug($title);

            // Coba cari juga lewat search PencuriMovie
            $searchHtml = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->timeout(5)->get('https://ww44.pencurimovie.baby/?s=' . urlencode($cleanTitle))->body();

            if (preg_match_all('/<a\s+href="https?:\/\/[^\/]+\/([^"\/]+)\/?"[^>]*title="([^"]+)"/i', $searchHtml, $matches)) {
                foreach ($matches[1] as $idx => $foundSlug) {
                    $foundTitle = html_entity_decode($matches[2][$idx], ENT_QUOTES, 'UTF-8');
                    if (stripos($foundTitle, $cleanTitle) !== false || stripos($foundSlug, Str::slug($title)) !== false) {
                        if ($year && str_contains($foundSlug, $year)) {
                            array_unshift($slugCandidates, $foundSlug);
                        } else {
                            $slugCandidates[] = $foundSlug;
                        }
                    }
                }
            }

            foreach (array_slice(array_unique(array_filter($slugCandidates)), 0, 3) as $candSlug) {
                $pmDetail = PencuriMovieService::getMovieDetail('pm-' . $candSlug);
                if (!$pmDetail) continue;

                // Prioritas A: Jika PencuriMovie berhasil ekstrak Direct MP4 Streamtape
                if (!empty($pmDetail['video_url'])) {
                    $res = [
                        'video_url' => $pmDetail['video_url'],
                        'type' => 'mp4',
                        'subtitles' => [],
                        'source' => 'pencurimovie_direct_mp4',
                    ];
                    Cache::put($cacheKey, $res, 7200);
                    return $res;
                }

                // Prioritas B: Jika ada HGCloud / Vibuxer embed, ekstrak direct HLS2 master.m3u8!
                foreach ($pmDetail['servers'] ?? [] as $s) {
                    $embedUrl = $s['embed_url'] ?? '';
                    if (str_contains($embedUrl, 'hgcloud.') || str_contains($embedUrl, 'hglink.') || str_contains($embedUrl, 'vibuxer.')) {
                        $m3u8 = self::extractHgCloudDirectM3u8($embedUrl);
                        if ($m3u8) {
                            $res = [
                                'video_url' => $m3u8,
                                'type' => 'm3u8',
                                'subtitles' => [],
                                'source' => 'pencurimovie_hgcloud_hls',
                            ];
                            Cache::put($cacheKey, $res, 7200);
                            return $res;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('DirectStreamResolver PencuriMovie fallback error: ' . $e->getMessage());
        }

        // 2. Cari di Server WP (198.252.99.106 / 198.252.109.24) -> Ekstrak HGCloud / dhtpre
        try {
            $searchUrl = 'http://198.252.99.106/wp-json/wp/v2/posts?search=' . urlencode($cleanTitle) . '&per_page=5';
            $response = Http::timeout(5)->get($searchUrl);
            if ($response->successful()) {
                $posts = $response->json();
                if (!empty($posts) && is_array($posts)) {
                    $lkService = new Lk21ScraperService();
                    foreach ($posts as $p) {
                        $slug = $p['slug'] ?? '';
                        if (!$slug) continue;

                        $detail = $lkService->getMovieDetail($slug);
                        foreach ($detail['servers'] ?? [] as $s) {
                            $embedUrl = $s['embed_url'] ?? '';
                            if (str_contains($embedUrl, 'hgcloud.') || str_contains($embedUrl, 'hglink.') || str_contains($embedUrl, 'vibuxer.')) {
                                $m3u8 = self::extractHgCloudDirectM3u8($embedUrl);
                                if ($m3u8) {
                                    $res = [
                                        'video_url' => $m3u8,
                                        'type' => 'm3u8',
                                        'subtitles' => [],
                                        'source' => 'hgcloud_direct_hls',
                                    ];
                                    Cache::put($cacheKey, $res, 7200);
                                    return $res;
                                }
                            }
                            if (str_contains($embedUrl, 'dhtpre.com')) {
                                $m3u8 = self::unpackDhtpre($embedUrl);
                                if ($m3u8) {
                                    $res = [
                                        'video_url' => $m3u8,
                                        'type' => 'm3u8',
                                        'subtitles' => [],
                                        'source' => 'dhtpre_direct_hls',
                                    ];
                                    Cache::put($cacheKey, $res, 7200);
                                    return $res;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('DirectStreamResolverService failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Ekstrak direct master.m3u8 (CORS: *) dari HGCloud /HGLink / Vibuxer embed URL
     */
    public static function extractHgCloudDirectM3u8(string $embedUrl): ?string
    {
        try {
            // Ambil ID video dari /e/{id}
            if (!preg_match('#/e/([a-zA-Z0-9_\-]+)#', $embedUrl, $m)) {
                return null;
            }
            $videoId = $m[1];
            $vibuxerUrl = "https://vibuxer.com/e/{$videoId}";

            $html = '';
            $helperPath = app_path('Services/fetch_cf.py');
            if (file_exists($helperPath)) {
                $cmd = '/usr/bin/python3.10 ' . escapeshellarg($helperPath) . ' ' . escapeshellarg($vibuxerUrl);
                $html = (string) shell_exec($cmd);
            }
            if (empty($html)) {
                $html = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Referer' => 'https://hgcloud.to/',
                ])->timeout(8)->get($vibuxerUrl)->body();
            }

            if (preg_match('/return p\}\(\x27(.*?)\x27,(\d+),(\d+),\x27(.*?)\x27\.split/s', $html, $pm)) {
                $unpacked = self::unpackJs($pm[1], (int) $pm[2], (int) $pm[3], explode('|', $pm[4]));
                if (preg_match('/var\s+links\s*=\s*(\{.*?\});/s', $unpacked, $lm)) {
                    $links = json_decode($lm[1], true);
                    if (!empty($links['hls2']) && str_starts_with($links['hls2'], 'http')) {
                        return $links['hls2'];
                    }
                }
                if (preg_match_all('/https?:\/\/[^\s\x22\x27,]+\.m3u8[^\s\x22\x27,]*/', $unpacked, $urls)) {
                    return $urls[0][0] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('extractHgCloudDirectM3u8 failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Unpack dhtpre.com packed JS to get direct master.m3u8
     */
    public static function unpackDhtpre(string $embedUrl): ?string
    {
        try {
            $html = Http::withHeaders([
                'Referer' => 'http://198.252.99.106/'
            ])->timeout(8)->get($embedUrl)->body();

            if (preg_match('/return p\}\(\x27(.*?)\x27,(\d+),(\d+),\x27(.*?)\x27\.split/', $html, $m)) {
                $p = $m[1];
                $a = intval($m[2]);
                $c = intval($m[3]);
                $k = explode('|', $m[4]);
                
                $unpacked = self::unpackJs($p, $a, $c, $k);
                if (preg_match_all('/https?:\/\/[^\s\x22\x27,]+\.m3u8[^\s\x22\x27,]*/', $unpacked, $urls)) {
                    return $urls[0][0] ?? null;
                }
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function unpackJs($p, $a, $c, $k)
    {
        $base = function($c, $a) use (&$base) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
            return ($c < $a ? '' : $base(intval($c / $a), $a)) . $chars[$c % $a];
        };
        while ($c > 0) {
            $c--;
            if (!empty($k[$c])) {
                $word = $base($c, $a);
                $p = preg_replace('/\b' . preg_quote($word, '/') . '\b/', $k[$c], $p);
            }
        }
        return $p;
    }
}
