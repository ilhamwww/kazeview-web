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
     * Resolusi seluruh pilihan server dari halaman film IndoXXI
     */
    public static function resolveAllServers(string $postId, string $html): array
    {
        $cacheKey = "indoxxi_servers_v3_{$postId}";
        return Cache::remember($cacheKey, 86400, function () use ($postId, $html) {
            $tabs = [];
            // Cari tab server asli dari halaman IndoXXI (<a href="#p1">Server 1</a>)
            if (preg_match_all('/<a\s+href="#(p\d+)"[^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $tabs[$m[1]] = trim($m[2]);
                }
            }

            if (empty($tabs)) {
                $tabs = [
                    'p1' => 'Server 1',
                    'p2' => 'Server 2',
                    'p3' => 'Server 3',
                    'p4' => 'Server 4',
                ];
            }

            $client = self::getClient();
            $resolvedServers = [];

            foreach ($tabs as $tabKey => $tabLabel) {
                try {
                    $res = $client->post(self::$baseUrl . '/wp-admin/admin-ajax.php', [
                        'form_params' => [
                            'action' => 'muvipro_player_content',
                            'post_id' => $postId,
                            'tab' => $tabKey,
                        ],
                        'headers' => [
                            'X-Requested-With' => 'XMLHttpRequest',
                        ],
                    ]);

                    if ($res->getStatusCode() === 200) {
                        $body = (string) $res->getBody();
                        if (preg_match('/<iframe[^>]+src=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i', $body, $m)) {
                            $src = trim($m[1]);
                            if (!empty($src)) {
                                $host = parse_url($src, PHP_URL_HOST);
                                $dnsAlive = $host ? (filter_var($host, FILTER_VALIDATE_IP) || @checkdnsrr($host, 'A')) : false;
                                $isLikelyDown = !$dnsAlive || str_contains($src, 'playerekusu.stream');

                                // Jika domainnya mati total (NXDOMAIN), jangan masukkan
                                if (!$dnsAlive) {
                                    continue;
                                }

                                $resolvedServers[] = [
                                    'id' => 'server_ix_' . $tabKey,
                                    'name' => 'Server 3 (' . $tabLabel . ')',
                                    'type' => 'embed',
                                    'embed_url' => $src,
                                    'tab_key' => $tabKey,
                                    'is_down' => $isLikelyDown,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("IndoxxiTechService::resolveAllServers tab {$tabKey} failed: " . $e->getMessage());
                }
            }

            // Jika ada server yang diketahui down (misal playerekusu), urutkan server yang aktif/sehat lebih dulu
            usort($resolvedServers, function ($a, $b) {
                if ($a['is_down'] === $b['is_down']) {
                    return 0;
                }
                return $a['is_down'] ? 1 : -1;
            });

            return $resolvedServers;
        });
    }

    /**
     * Parse and map source embed into provider group
     */
    public static function categorizeServerEmbed(string $embedUrl): ?string
    {
        $host = strtolower(parse_url($embedUrl, PHP_URL_HOST) ?? '');
        if (str_contains($host, 'hglink') || str_contains($host, 'hgcloud') || str_contains($host, 'vibuxer') || str_contains($host, 'cloudembed')) {
            return 'hgcloud';
        }
        if (str_contains($host, 'minochinos') || str_contains($host, 'callistanise') || str_contains($host, 'abyssplayer') || str_contains($host, 'morencius') || str_contains($host, 'dramiyos')) {
            return 'abyss';
        }
        return null;
    }

    /**
     * Filter & urutkan server IndoXXI agar fokus pada 2 Provider Terbaik (HGCloud & AbyssPlayer)
     */
    public static function filterTopTwoServers(array $servers): array
    {
        $hgServer = null;
        $abyssServer = null;
        $fallbacks = [];

        foreach ($servers as $s) {
            $url = $s['embed_url'] ?? '';
            $cat = self::categorizeServerEmbed($url);
            if ($cat === 'hgcloud' && !$hgServer) {
                $hgServer = $s;
            } elseif ($cat === 'abyss' && !$abyssServer) {
                $abyssServer = $s;
            } else {
                $fallbacks[] = $s;
            }
        }

        $topServers = [];

        // 1. Server 3 (HD Utama) -> HGCloud / Vibuxer
        if ($hgServer) {
            $topServers[] = [
                'id' => 'server_ix_primary_hg',
                'name' => 'Server 3 (HD Utama)',
                'type' => 'embed',
                'embed_url' => $hgServer['embed_url'],
                'provider' => 'hgcloud',
                'is_down' => false,
            ];
        }

        // 2. Server 3 (Mirror HD) -> AbyssPlayer / Minochinos
        if ($abyssServer) {
            $topServers[] = [
                'id' => 'server_ix_secondary_abyss',
                'name' => 'Server 3 (Mirror HD)',
                'type' => 'embed',
                'embed_url' => $abyssServer['embed_url'],
                'provider' => 'abyss',
                'is_down' => false,
            ];
        }

        // Jika salah satu atau keduanya belum ditemukan di link bawaan, masukkan fallback yang sehat
        if (count($topServers) < 2) {
            foreach ($fallbacks as $f) {
                if (!empty($f['is_down'])) continue;
                $topServers[] = [
                    'id' => $f['id'] ?? ('server_ix_cadangan_' . count($topServers)),
                    'name' => 'Server 3 (Cadangan ' . (count($topServers) + 1) . ')',
                    'type' => 'embed',
                    'embed_url' => $f['embed_url'] ?? '',
                    'is_down' => false,
                ];
                if (count($topServers) >= 2) break;
            }
        }

        return !empty($topServers) ? $topServers : $servers;
    }
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

                $servers = $externalId ? self::resolveAllServers($externalId, $html) : [];

                if (empty($servers)) {
                    $servers[] = [
                        'id' => 'server_indoxxi_embed',
                        'name' => 'Server 3 (Mirror)',
                        'type' => 'embed',
                        'embed_url' => $watchUrl,
                    ];
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
                    'video_url' => '',
                    'isEmbed' => true,
                    'source' => 'indoxxi',
                    'servers' => $servers,
                ];
            } catch (\Exception $e) {
                Log::warning("IndoxxiTechService::getMovieDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }
}
