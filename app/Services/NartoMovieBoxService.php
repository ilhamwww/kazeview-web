<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NartoMovieBoxService
{
    protected static string $baseUrl = 'https://narto-drama.com';

    /**
     * Cookie nd_ck generator untuk bypass gate ringan Narto Drama
     */
    protected static function generateNdCookie(): string
    {
        $nowB36 = base_convert((string) (int) (microtime(true) * 1000), 10, 36);
        $randB36 = substr(base_convert((string) mt_rand(1000000000, 9999999999), 10, 36), 0, 8);
        return $nowB36 . $randB36;
    }

    protected static function getClient(): Client
    {
        $ndCookie = self::generateNdCookie();
        return new Client([
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Cookie' => "nd_ck={$ndCookie}; path=/",
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
            ],
            'verify' => false,
        ]);
    }

    /**
     * Ambil katalog MovieBox khusus konten Indonesia (Drama, Horor, Populer)
     */
    public static function getIndonesiaCatalog(string $category = 'all', int $page = 1, string $search = ''): array
    {
        $search = trim($search);
        $cacheKey = "mb_indo_cat_{$category}_p{$page}_" . md5(strtolower($search));

        return Cache::remember($cacheKey, 1800, function () use ($category, $page, $search) {
            try {
                $client = self::getClient();

                // Tarik data sections MovieBox
                $url = self::$baseUrl . "/home/providers/sections?provider=moviebox&lang=id-ID&target_lang=id-ID&type_context=movie-series";
                $res = $client->get($url, [
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => self::$baseUrl . '/?lang=id-ID&type=movie-series&tab-provider=moviebox',
                    ],
                ]);

                if ($res->getStatusCode() !== 200) {
                    return ['data' => []];
                }

                $json = json_decode((string) $res->getBody(), true);
                $sections = $json['sections'] ?? [];

                // Filter sections yang relevan dengan Indonesia murni
                $allItems = [];
                $seenSlugs = [];

                // Daftar judul non-Indonesia yang sering muncul di tab umum MovieBox untuk diblokir
                $foreignKeywords = [
                    'the remarried empress', 'dead-end job', '100 days of deception', 'doctor x', 'black clover',
                    'resident evil', 'forgotten island', 'drive - the pretenders', 'spider-man', 'the dog stars',
                    'kung fu soccer', 'runner', 'our sticky love', 'solo leveling', 'that time i got reincarnated',
                    'spooky in love', 'the apothecary diaries', 'coco', 'the conjuring', 'hotel transylvania',
                    'immortal king', 'boruto', 'one piece', 'firefly wedding', 'tokyo revengers',
                    'bass x machina', 'lovers & gangsters', 'khemjira forever', 'the perfect lie', 'love in disguise',
                    'final table', 'reacher', 'minions & monsters', 'the walking dead', 'tigers sniff the rose',
                    'where the mask ends', 'love for you', 'the lament of autumn', 'cerahnya awal musim semi'
                ];

                // Judul Indonesia populer di tab umum (seperti Halloween) yang boleh masuk
                $knownIndoExtra = [
                    'sijjin', 'kkn di desa penari', 'demon\'s slaves', 'pengabdi setan', 'sewu dino', 'dilan', 'layangan putus', 'ivanna', 'makmum'
                ];

                foreach ($sections as $sec) {
                    $tabKey = $sec['tab_key'] ?? '';
                    $tabLabel = $sec['tab_label'] ?? '';

                    // Hanya ambil section yang murni bertema Indonesia ATAU memiliki item Indonesia yang valid
                    $isIndoSection = str_contains(strtolower($tabLabel), 'indonesia') || in_array($tabKey, ['to:1:9', 'to:1:12']);

                    foreach ($sec['items'] ?? [] as $item) {
                        $wUrl = $item['watch_url'] ?? '';
                        $titleRaw = $item['title'] ?? '';
                        if (empty($wUrl) || empty($titleRaw)) continue;

                        $titleLower = strtolower($titleRaw);

                        // Blokir judul foreign
                        $isForeign = false;
                        foreach ($foreignKeywords as $fk) {
                            if (str_contains($titleLower, $fk)) {
                                $isForeign = true;
                                break;
                            }
                        }
                        if ($isForeign) continue;

                        // Jika bukan section Indonesia, hanya izinkan jika judul cocok dengan konten Indonesia yang terverifikasi
                        if (!$isIndoSection) {
                            $isIndoTitle = false;
                            foreach ($knownIndoExtra as $ik) {
                                if (str_contains($titleLower, $ik)) {
                                    $isIndoTitle = true;
                                    break;
                                }
                            }
                            if (!$isIndoTitle) continue;
                        }

                        // Ekstrak slug
                        // Format: /detail/watch/{slug} atau /search/import?provider=moviebox&book_id=...
                        $slug = '';
                        if (preg_match('/\/detail\/watch\/([^\/\?]+)/', $wUrl, $sm)) {
                            $slug = $sm[1];
                        } elseif (str_contains($wUrl, '/search/import')) {
                            // Resolusi redirect 302 otomatis dari Narto-Drama agar dapat slug asli (misal: five-friends-2-mount-klawih)
                            $slug = self::resolveImportSlug($wUrl);
                        }
                        // Fallback jika belum teresolusi
                        if (empty($slug) && preg_match('/[?&]title=([^&]+)/', $wUrl, $sm)) {
                            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', urldecode($sm[1])), '-'));
                        }
                        if (empty($slug) || isset($seenSlugs[$slug])) continue;
                        $seenSlugs[$slug] = true;

                        // Deteksi tahun dari judul
                        $year = '';
                        if (preg_match('/\((\d{4})\)/', $titleRaw, $ym)) {
                            $year = $ym[1];
                        }

                        $cleanTitle = trim(preg_replace('/\s*\(\d{4}\)\s*$/', '', $titleRaw));

                        // Filter pencarian jika ada
                        if (!empty($search)) {
                            if (!str_contains(strtolower($cleanTitle), strtolower($search))) {
                                continue;
                            }
                        }

                        // Deteksi series vs movie
                        $tags = $item['tag_names'] ?? [];
                        $isSeries = str_contains(strtolower($cleanTitle), 'series') ||
                                    str_contains(strtolower($cleanTitle), 'season') ||
                                    in_array('TVSeries', $tags) ||
                                    str_contains(strtolower($tabLabel), 'drama') ||
                                    $tabKey === 'to:1:9';

                        $type = $isSeries ? 'series' : 'movie';

                        // Filter kategori
                        if ($category === 'movie' && $type !== 'movie') continue;
                        if ($category === 'series' && $type !== 'series') continue;
                        if ($category === '2026' && $year !== '2026') continue;
                        if ($category === '2025' && $year !== '2025') continue;
                        if ($category === '2024' && $year !== '2024') continue;
                        if ($category === 'classic' && (!empty($year) && (int)$year > 2023)) continue;

                        $poster = $item['poster_url'] ?? '';
                        if ($poster && str_starts_with($poster, '/')) {
                            $poster = self::$baseUrl . $poster;
                        }

                        $allItems[] = [
                            'id' => 'mb-' . $slug,
                            'title' => $cleanTitle,
                            'slug' => 'mb-' . $slug,
                            'raw_slug' => $slug,
                            'type' => $type,
                            'year' => $year,
                            'rating' => 8.5,
                            'voteAverage' => 8.5,
                            'quality' => '1080p Ultra HD',
                            'duration' => null,
                            'runtime' => null,
                            'poster' => $poster,
                            'thumbnail' => $poster,
                            'banner' => $poster,
                            'isEmbed' => false,
                            'source' => 'moviebox',
                            'provider_badge' => 'Server 1',
                            'genres' => $tags,
                        ];
                    }
                }

                return [
                    'data' => $allItems,
                ];
            } catch (\Exception $e) {
                Log::warning('NartoMovieBoxService::getIndonesiaCatalog error: ' . $e->getMessage());
                return ['data' => []];
            }
        });
    }

    /**
     * Ambil detail film dari MovieBox (Narto)
     */
    public static function getMovieDetail(string $slug, bool $force = false): ?array
    {
        $rawSlug = preg_replace('/^mb-/', '', $slug);

        $aliasMap = [
            'sekawan-limo-2-gunung-klawih' => 'five-friends-2-mount-klawih',
            'sekawan-limo-2' => 'five-friends-2-mount-klawih',
            'badut-gendong' => 'dance-of-the-damned',
            'project-sacrifice' => 'tumbal-proyek',
            'pernikahan-dini-gen-z' => 'playing-adult-pernikahan-dini-gen-z',
            'terikat-janji' => 'bound-by-promise',
            'kuyank' => 'kuyank-indonesian',
        ];
        $targetSlug = $aliasMap[$rawSlug] ?? $rawSlug;
        $cacheKey = "mb_movie_detail_v3_" . md5($slug);

        if ($force) {
            Cache::forget($cacheKey);
        }

        // Cache stream dan detail selama 5 menit (300 detik) agar token HLS selalu segar
        return Cache::remember($cacheKey, 300, function () use ($rawSlug, $targetSlug, $slug) {
            try {
                $client = self::getClient();

                // Kunjungi halaman detail/watch episode 1
                $watchUrl = self::$baseUrl . "/detail/watch/{$targetSlug}/1?lang=id-ID&from=home";
                $res = null;
                try {
                    $res = $client->get($watchUrl);
                } catch (\Throwable $e) {
                    $res = null;
                }

                // Jika gagal/404, coba cari slug asli via API search Narto
                if (!$res || $res->getStatusCode() !== 200) {
                    $realSlug = self::searchRealSlug($targetSlug);
                    if ($realSlug && $realSlug !== $targetSlug) {
                        $targetSlug = $realSlug;
                        $watchUrl = self::$baseUrl . "/detail/watch/{$targetSlug}/1?lang=id-ID&from=home";
                        try {
                            $res = $client->get($watchUrl);
                        } catch (\Throwable $e) {
                            $res = null;
                        }
                    }
                }

                if (!$res || $res->getStatusCode() !== 200) {
                    return null;
                }

                $html = (string) $res->getBody();

                // Parse judul dari <title>
                preg_match('/<title>(.*?)<\/title>/i', $html, $tm);
                $titleRaw = $tm[1] ?? $rawSlug;
                $cleanTitle = trim(preg_replace('/\s*-\s*Streaming Gratis.*$/i', '', $titleRaw));
                $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $cleanTitle));
                if (str_contains(strtolower($cleanTitle), 'five friends 2') || $rawSlug === 'sekawan-limo-2-gunung-klawih') {
                    $cleanTitle = 'Sekawan Limo 2: Gunung Klawih';
                }

                // Parse tahun
                $year = '';
                if (preg_match('/\((\d{4})\)/', $titleRaw, $ym)) {
                    $year = $ym[1];
                }

                // Parse episodeItemsRaw
                preg_match('/const episodeItemsRaw\s*=\s*(.*?);/s', $html, $em);
                $episodes = !empty($em[1]) ? json_decode($em[1], true) : [];
                if (empty($episodes) || !is_array($episodes)) {
                    return null;
                }

                $ep1 = $episodes[0];
                $playUrl = $ep1['play_url'] ?? '';

                // Parse poster & sinopsis dari JSON-LD
                $poster = '';
                $synopsis = '';
                if (preg_match('/<script[^>]*type=[\x27\x22]application\/ld\+json[\x27\x22][^>]*>(.*?)<\/script>/s', $html, $ldm)) {
                    $ld = json_decode($ldm[1], true);
                    if ($ld) {
                        $poster = is_array($ld['thumbnailUrl'] ?? null) ? $ld['thumbnailUrl'][0] : ($ld['thumbnailUrl'] ?? '');
                        $synopsis = $ld['description'] ?? '';
                        // Bersihkan embel-embel Narto Drama di deskripsi
                        $synopsis = trim(preg_replace('/\.?\s*Narto Drama - Tempat Nonton.*$/i', '', $synopsis));
                    }
                }

                if (empty($poster)) {
                    $poster = $ep1['thumb_url'] ?? '';
                }

                // Parse subtitles
                $subtitles = [];
                foreach ($ep1['multi_subtitles'] ?? [] as $sub) {
                    $sUrl = $sub['subtitle_url'] ?? '';
                    if ($sUrl) {
                        if (str_starts_with($sUrl, '/')) {
                            $sUrl = self::$baseUrl . $sUrl;
                        }
                        $subtitles[] = [
                            'url' => $sUrl,
                            'lang' => $sub['language_code'] ?? 'id',
                            'label' => $sub['label'] ?? 'Bahasa Indonesia',
                            'default' => !empty($sub['is_default']),
                        ];
                    }
                }

                // Servers setup: Server 1 = HD HLS, Server 2 = Mirror / Cadangan
                $servers = [
                    [
                        'id' => 'server_moviebox_hd',
                        'name' => 'Server 1 (1080p Ultra HD)',
                        'type' => 'hls',
                        'embed_url' => '',
                        'video_url' => $playUrl,
                    ],
                ];

                // Cek apakah ada di Rebahin sebagai cadangan
                try {
                    $rbMatch = RebahinService::getIndonesiaCatalog('all', 1, $cleanTitle);
                    if (!empty($rbMatch['data'])) {
                        $firstRb = $rbMatch['data'][0];
                        $rbSlug = $firstRb['raw_slug'] ?? '';
                        if ($rbSlug) {
                            $rbDetail = RebahinService::getMovieDetail('rb-' . $rbSlug);
                            if ($rbDetail && !empty($rbDetail['servers'])) {
                                foreach ($rbDetail['servers'] as $idx => $rbs) {
                                    $servers[] = [
                                        'id' => 'server_rebahin_' . ($idx + 1),
                                        'name' => 'Server ' . (count($servers) + 1) . ' (Arsip)',
                                        'type' => 'embed',
                                        'embed_url' => $rbs['embed_url'] ?? '',
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Failover Rebahin silent fallback
                }

                return [
                    'id' => 'mb-' . $rawSlug,
                    'title' => $cleanTitle,
                    'slug' => 'mb-' . $rawSlug,
                    'raw_slug' => $rawSlug,
                    'overview' => $synopsis,
                    'poster' => $poster,
                    'backdrop' => $poster,
                    'thumbnail' => $poster,
                    'voteAverage' => 8.6,
                    'runtime' => null,
                    'releaseDate' => $year,
                    'year' => $year,
                    'genres' => ['Indonesia', 'HD'],
                    'video_url' => $playUrl,
                    'subtitles' => $subtitles,
                    'servers' => $servers,
                    'isEmbed' => false,
                    'source' => 'moviebox',
                ];
            } catch (\Exception $e) {
                Log::warning("NartoMovieBoxService::getMovieDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Ambil detail TV series dari MovieBox (Narto) lengkap per episode
     */
    public static function getSeriesDetail(string $slug, bool $force = false): ?array
    {
        $rawSlug = preg_replace('/^mb-/', '', $slug);
        $cacheKey = "mb_series_detail_v2_" . md5($rawSlug);

        if ($force) {
            Cache::forget($cacheKey);
        }

        // Cache stream dan detail series selama 5 menit (300 detik)
        return Cache::remember($cacheKey, 300, function () use ($rawSlug, $slug) {
            try {
                $client = self::getClient();

                $watchUrl = self::$baseUrl . "/detail/watch/{$rawSlug}/1?lang=id-ID&from=home";
                $res = null;
                try {
                    $res = $client->get($watchUrl);
                } catch (\Throwable $e) {
                    $res = null;
                }

                // Jika gagal/404, coba cari slug asli via API search Narto
                if (!$res || $res->getStatusCode() !== 200) {
                    $realSlug = self::searchRealSlug($rawSlug);
                    if ($realSlug && $realSlug !== $rawSlug) {
                        $rawSlug = $realSlug;
                        $watchUrl = self::$baseUrl . "/detail/watch/{$rawSlug}/1?lang=id-ID&from=home";
                        try {
                            $res = $client->get($watchUrl);
                        } catch (\Throwable $e) {
                            $res = null;
                        }
                    }
                }

                if (!$res || $res->getStatusCode() !== 200) {
                    return null;
                }

                $html = (string) $res->getBody();

                preg_match('/<title>(.*?)<\/title>/i', $html, $tm);
                $titleRaw = $tm[1] ?? $rawSlug;
                $cleanTitle = trim(preg_replace('/\s*-\s*Streaming Gratis.*$/i', '', $titleRaw));
                $cleanTitle = trim(preg_replace('/\s*\(\d{4}\).*$/i', '', $cleanTitle));

                $year = '';
                if (preg_match('/\((\d{4})\)/', $titleRaw, $ym)) {
                    $year = $ym[1];
                }

                preg_match('/const episodeItemsRaw\s*=\s*(.*?);/s', $html, $em);
                $episodesRaw = !empty($em[1]) ? json_decode($em[1], true) : [];
                if (empty($episodesRaw) || !is_array($episodesRaw)) {
                    return null;
                }

                $poster = '';
                $synopsis = '';
                if (preg_match('/<script[^>]*type=[\x27\x22]application\/ld\+json[\x27\x22][^>]*>(.*?)<\/script>/s', $html, $ldm)) {
                    $ld = json_decode($ldm[1], true);
                    if ($ld) {
                        $poster = is_array($ld['thumbnailUrl'] ?? null) ? $ld['thumbnailUrl'][0] : ($ld['thumbnailUrl'] ?? '');
                        $synopsis = $ld['description'] ?? '';
                        $synopsis = trim(preg_replace('/\.?\s*Narto Drama - Tempat Nonton.*$/i', '', $synopsis));
                    }
                }

                if ($poster && str_starts_with($poster, '/')) {
                    $poster = self::$baseUrl . $poster;
                }

                $firstThumb = !empty($episodesRaw[0]['thumb_url']) ? $episodesRaw[0]['thumb_url'] : '';
                if (empty($poster) && $firstThumb) {
                    $poster = $firstThumb;
                }

                // Backdrop: prioritaskan still frame 16:9 episode pertama yang jernih, fallback ke poster
                $backdrop = $firstThumb ?: $poster;

                // Parse tags/genres dari HTML
                preg_match_all('/<a[^>]+class=[\x27\x22][^\x27\x22]*tag[^\x27\x22]*[\x27\x22][^>]*>(.*?)<\/a>/i', $html, $tagMatches);
                $tags = [];
                if (!empty($tagMatches[1])) {
                    foreach ($tagMatches[1] as $t) {
                        $cleanTag = trim(str_replace('#', '', strip_tags($t)));
                        if ($cleanTag && !in_array($cleanTag, $tags)) {
                            $tags[] = $cleanTag;
                        }
                    }
                }
                if (empty($tags)) {
                    $tags = ['Series Lokal', 'Drama'];
                } elseif (!in_array('Series Lokal', $tags)) {
                    array_unshift($tags, 'Series Lokal');
                }

                $episodesFormatted = [];
                foreach ($episodesRaw as $ep) {
                    $epNum = (int) ($ep['number'] ?? $ep['route_episode_number'] ?? 1);
                    $epPlayUrl = $ep['play_url'] ?? '';

                    $subtitles = [];
                    foreach ($ep['multi_subtitles'] ?? [] as $sub) {
                        $sUrl = $sub['subtitle_url'] ?? '';
                        if ($sUrl) {
                            if (str_starts_with($sUrl, '/')) {
                                $sUrl = self::$baseUrl . $sUrl;
                            }
                            $subtitles[] = [
                                'url' => $sUrl,
                                'lang' => $sub['language_code'] ?? 'id',
                                'label' => $sub['label'] ?? 'Bahasa Indonesia',
                                'default' => !empty($sub['is_default']),
                            ];
                        }
                    }

                    $episodesFormatted[] = [
                        'id' => 'mb-' . $rawSlug . '-ep-' . $epNum,
                        'episodeNumber' => $epNum,
                        'name' => 'Episode ' . $epNum,
                        'stillPath' => $ep['thumb_url'] ?? $backdrop,
                        'overview' => "Nonton {$cleanTitle} Episode {$epNum} Subtitle Indonesia.",
                        'stream_url' => $epPlayUrl,
                        'subtitles' => $subtitles,
                    ];
                }

                return [
                    'id' => 'mb-' . $rawSlug,
                    'title' => $cleanTitle,
                    'slug' => 'mb-' . $rawSlug,
                    'raw_slug' => $rawSlug,
                    'overview' => $synopsis,
                    'poster' => $poster,
                    'posterPath' => $poster,
                    'backdrop' => $backdrop,
                    'backdropPath' => $backdrop,
                    'thumbnail' => $poster,
                    'voteAverage' => 8.8,
                    'year' => $year,
                    'firstAirDate' => $year ? ($year . '-01-01') : null,
                    'numberOfSeasons' => 1,
                    'numberOfEpisodes' => count($episodesFormatted),
                    'country' => 'Indonesia',
                    'status' => 'Returning Series',
                    'genres' => array_map(fn($t) => ['name' => $t], $tags),
                    'isEmbed' => false,
                    'source' => 'moviebox',
                    'seasons' => [
                        [
                            'seasonNumber' => 1,
                            'name' => 'Season 1',
                            'episodeCount' => count($episodesFormatted),
                        ]
                    ],
                    'defaultSeason' => [
                        'seasonNumber' => 1,
                        'name' => 'Season 1',
                        'episodes' => $episodesFormatted,
                    ],
                ];
            } catch (\Exception $e) {
                Log::warning("NartoMovieBoxService::getSeriesDetail failed for {$rawSlug}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Resolve stream untuk episode tertentu
     */
    public static function resolveEpisodeStream(string $seriesSlug, int $episodeNumber): ?array
    {
        $detail = self::getSeriesDetail($seriesSlug);
        if (!$detail) return null;

        $episodes = $detail['defaultSeason']['episodes'] ?? [];
        foreach ($episodes as $ep) {
            if ($ep['episodeNumber'] === $episodeNumber) {
                return [
                    'video_url' => $ep['stream_url'],
                    'subtitles' => $ep['subtitles'] ?? [],
                ];
            }
        }

        return null;
    }

    /**
     * Cari slug asli di Narto Drama jika slug hasil judul berbeda dari rute internal
     */
    public static function searchRealSlug(string $rawSlug): ?string
    {
        $cacheKey = 'mb_real_slug_' . md5($rawSlug);
        return Cache::remember($cacheKey, 86400, function () use ($rawSlug) {
            try {
                $query = str_replace('-', ' ', $rawSlug);
                $client = self::getClient();
                $res = $client->get(self::$baseUrl . '/search', [
                    'query' => [
                        'q' => $query,
                        'limit' => 5,
                        'lang' => 'id-ID',
                    ],
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Cookie' => 'nd_ck=' . self::generateNdCookie() . '; path=/',
                    ],
                ]);
                if ($res->getStatusCode() === 200) {
                    $data = json_decode((string) $res->getBody(), true);
                    foreach ($data['items'] ?? [] as $it) {
                        $url = $it['url'] ?? '';
                        if (preg_match('/\/detail\/watch\/([^\/\?]+)/', $url, $m)) {
                            return $m[1];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('NartoMovieBoxService::searchRealSlug failed for ' . $rawSlug . ': ' . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Resolusi URL redirect 302 dari /search/import Narto-Drama untuk mendapatkan slug asli
     */
    public static function resolveImportSlug(string $wUrl): ?string
    {
        $cacheKey = 'mb_import_slug_res_' . md5($wUrl);
        return Cache::remember($cacheKey, 86400 * 30, function () use ($wUrl) {
            try {
                $client = self::getClient();
                $res = $client->get($wUrl, [
                    'allow_redirects' => [
                        'max' => 5,
                        'track_redirects' => true,
                    ],
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Cookie' => 'nd_ck=' . self::generateNdCookie() . '; path=/',
                    ],
                    'timeout' => 5,
                ]);
                $history = $res->getHeader('X-Guzzle-Redirect-History');
                $finalUrl = !empty($history) ? end($history) : (string) $res->getBody();
                if (preg_match('/\/detail\/watch\/([^\/\?]+)/', $finalUrl, $m)) {
                    return $m[1];
                }
            } catch (\Throwable $e) {
                // Ignore and return null
            }
            return null;
        });
    }
}
