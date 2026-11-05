<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IndoxxiTechService
{
    protected static string $baseUrl = 'https://indoxxi.tech';

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
     * Ambil katalog film/series Indonesia dari IndoXXI Tech (dengan filter sensor otomatis)
     */
    public static function getIndonesiaCatalog(int $page = 1): array
    {
        $cacheKey = "indoxxi_catalog_p{$page}";
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
                preg_match_all('/<article[^>]*id="post-(\d+)"[^>]*>(.*?)<\/article>/s', $html, $matches, PREG_SET_ORDER);

                $items = [];
                foreach ($matches as $m) {
                    $pid = $m[1];
                    $art = $m[2];

                    preg_match('/<h2 class="entry-title"[^>]*>.*?<a href="([^"]+)"[^>]*>([^<]+)<\/a>/s', $art, $tm);
                    $rawTitle = html_entity_decode(trim($tm[2] ?? ''), ENT_QUOTES, 'UTF-8');
                    $watchUrl = trim($tm[1] ?? '');
                    if (empty($rawTitle) || empty($watchUrl)) continue;

                    // Parse tahun dari judul
                    $year = null;
                    if (preg_match('/\((\d{4})\)/', $rawTitle, $ym)) {
                        $year = $ym[1];
                    }

                    $cleanTitle = trim(preg_replace('/\s*-\s*Kelas Bintang.*$/i', '', $rawTitle));
                    $cleanTitle = trim(preg_replace('/\s*Subtitle Indonesia.*$/i', '', $cleanTitle));
                    $cleanTitle = trim(preg_replace('/\s*Sub Indo.*$/i', '', $cleanTitle));
                    $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $cleanTitle));

                    // Parse genres
                    preg_match_all('/rel="category tag">([^<]+)<\/a>/', $art, $gm);
                    $genres = array_map('trim', $gm[1] ?? []);

                    // Parse tags
                    preg_match_all('/rel="tag">([^<]+)<\/a>/', $art, $tgm);
                    $tags = array_map('trim', $tgm[1] ?? []);

                    // Parse poster
                    $poster = null;
                    if (preg_match('/(?:data-src|src)="([^"]+)" class="[^"]*wp-post-image/i', $art, $im)) {
                        $poster = $im[1];
                    }

                    // Parse rating
                    $rating = 7.5;
                    if (preg_match('/class="gmr-rating-item">.*?([\d\.]+)/s', $art, $rm)) {
                        $rating = (float) $rm[1];
                    }

                    // Deteksi tipe (movie vs series)
                    $isSeries = str_contains($watchUrl, '/tv/') ||
                                str_contains($watchUrl, '/eps/') ||
                                str_contains(strtolower($cleanTitle), 'season') ||
                                str_contains(strtolower($cleanTitle), 'series') ||
                                in_array('TV', $tags, true);
                    $type = $isSeries ? 'series' : 'movie';

                    // Buat slug
                    $slugParts = explode('/', trim(parse_url($watchUrl, PHP_URL_PATH), '/'));
                    $rawSlug = end($slugParts);

                    $card = [
                        'id' => 'ix-' . $rawSlug,
                        'title' => $cleanTitle,
                        'slug' => 'ix-' . $rawSlug,
                        'raw_slug' => $rawSlug,
                        'type' => $type,
                        'year' => $year,
                        'rating' => $rating,
                        'voteAverage' => $rating,
                        'quality' => 'HD',
                        'duration' => null,
                        'poster' => $poster,
                        'thumbnail' => $poster,
                        'banner' => $poster,
                        'genres' => $genres ?: ['Indonesia'],
                        'tags' => $tags,
                        'url' => $watchUrl,
                        'watch_url' => $watchUrl,
                        'external_id' => $pid,
                        'isEmbed' => true,
                        'source' => 'indoxxi',
                        'provider_badge' => 'Server 3',
                    ];

                    // SENSOR KONTEN DEWASA & GENRE SEMI
                    if (MediaContentSanitizerService::isAdult($card)) {
                        continue;
                    }

                    $items[] = $card;
                }

                $hasMore = str_contains($html, 'class="next page-numbers"');

                return [
                    'data' => $items,
                    'hasMore' => $hasMore,
                ];
            } catch (\Exception $e) {
                Log::warning("IndoxxiTechService::getIndonesiaCatalog error: " . $e->getMessage());
                return ['data' => [], 'hasMore' => false];
            }
        });
    }

    /**
     * Resolusi iframe embed pemutar video dari post_id IndoXXI
     */
    public static function resolvePlayerEmbed(string $postId): ?string
    {
        $cacheKey = "indoxxi_embed_{$postId}";
        return Cache::remember($cacheKey, 86400, function () use ($postId) {
            try {
                $client = self::getClient();
                $res = $client->post(self::$baseUrl . '/wp-admin/admin-ajax.php', [
                    'form_params' => [
                        'action' => 'muvipro_player_content',
                        'post_id' => $postId,
                        'tab' => 'p1',
                    ],
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                ]);

                if ($res->getStatusCode() === 200) {
                    $body = (string) $res->getBody();
                    if (preg_match('/<iframe[^>]+src=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i', $body, $m)) {
                        return $m[1];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("IndoxxiTechService::resolvePlayerEmbed failed for {$postId}: " . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Ambil detail film dari IndoXXI
     */
    public static function getMovieDetail(string $slug): ?array
    {
        $rawSlug = preg_replace('/^ix-/', '', $slug);
        $cacheKey = "indoxxi_movie_detail_" . md5($rawSlug);

        return Cache::remember($cacheKey, 3600, function () use ($rawSlug, $slug) {
            try {
                // Cari item di database vault
                $vaultItem = \App\Models\IndonesiaMediaItem::where('slug', $slug)->first();
                $watchUrl = $vaultItem?->watch_url;
                $externalId = $vaultItem?->external_id;

                $client = self::getClient();

                // Jika belum punya watch_url, cari via search IndoXXI
                if (!$watchUrl) {
                    $searchUrl = self::$baseUrl . '/?s=' . urlencode(str_replace('-', ' ', $rawSlug));
                    $sRes = $client->get($searchUrl);
                    if ($sRes->getStatusCode() === 200) {
                        $sHtml = (string) $sRes->getBody();
                        if (preg_match('/<article[^>]*id="post-(\d+)"[^>]*>.*?<a href="([^"]+' . preg_quote($rawSlug, '/') . '[^"]*)"/s', $sHtml, $sm)) {
                            $externalId = $sm[1];
                            $watchUrl = $sm[2];
                        }
                    }
                }

                if (!$watchUrl) {
                    return null;
                }

                $res = $client->get($watchUrl);
                if ($res->getStatusCode() !== 200) {
                    return null;
                }

                $html = (string) $res->getBody();

                // Cek data-id untuk ajax player
                if (!$externalId && preg_match('/data-id="(\d+)"/', $html, $pm)) {
                    $externalId = $pm[1];
                }

                $embedUrl = $externalId ? self::resolvePlayerEmbed($externalId) : null;

                preg_match('/<title>(.*?)<\/title>/i', $html, $tm);
                $titleRaw = html_entity_decode(trim($tm[1] ?? $rawSlug), ENT_QUOTES, 'UTF-8');
                $cleanTitle = trim(preg_replace('/\s*-\s*INDOXXI.*$/i', '', $titleRaw));
                $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $cleanTitle));

                $year = '';
                if (preg_match('/\((\d{4})\)/', $titleRaw, $ym)) {
                    $year = $ym[1];
                }

                $synopsis = '';
                if (preg_match('/<div[^>]*itemprop="description"[^>]*>(.*?)<\/div>/s', $html, $dm)) {
                    $synopsis = trim(strip_tags($dm[1]));
                }

                $poster = null;
                if (preg_match('/class="[^"]*wp-post-image[^"]*"[^>]*src="([^"]+)"/i', $html, $im)) {
                    $poster = $im[1];
                }
                if (empty($poster) && $vaultItem) {
                    $poster = $vaultItem->poster;
                }

                $cleanTitle = $vaultItem?->title ?: $cleanTitle;
                $cleanTitle = preg_replace('/^Nonton Film\s*/i', '', $cleanTitle);
                $cleanTitle = preg_replace('/^Nonton\s*/i', '', $cleanTitle);

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
                    'video_url' => '',
                    'isEmbed' => true,
                    'source' => 'indoxxi',
                    'servers' => [
                        [
                            'id' => 'server_indoxxi_embed',
                            'name' => 'Server 3 (Mirror)',
                            'type' => 'embed',
                            'embed_url' => $embedUrl ?: $watchUrl,
                        ]
                    ],
                ];
            } catch (\Exception $e) {
                Log::warning("IndoxxiTechService::getMovieDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }
}
