<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OtakudesuScraperService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct()
    {
        $this->baseUrl = config('services.otakudesu.base_url', 'https://otakudesu.blog');
        $this->client = new Client([
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Referer' => $this->baseUrl . '/',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            'verify' => false,
        ]);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Fetch HTML using Guzzle with error handling
     */
    protected function fetchHtml(string $url): string
    {
        $response = $this->client->get($url);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to fetch {$url}, HTTP status {$response->getStatusCode()}");
        }
        return $response->getBody()->getContents();
    }

    /**
     * Scrape all available genres
     */
    public function scrapeGenres(): array
    {
        return Cache::remember('otakudesu_genres_list', 86400, function () {
            try {
                $html = $this->fetchHtml($this->baseUrl . '/genre-list/');
                $crawler = new Crawler($html);

                $genres = [];
                $crawler->filter('.genres a, ul.genres li a')->each(function (Crawler $node) use (&$genres) {
                    $name = trim($node->text());
                    $href = $node->attr('href');

                    if ($name && $href) {
                        $slug = basename(rtrim($href, '/'));
                        $genres[] = [
                            'name' => $name,
                            'slug' => $slug,
                            'url' => $href,
                        ];
                    }
                });

                return $genres;
            } catch (\Exception $e) {
                Log::error('Otakudesu scrapeGenres error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Search anime by query keyword
     */
    public function searchAnime(string $query): array
    {
        $cacheKey = 'otakudesu_search_' . md5($query);
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            try {
                $url = $this->baseUrl . '/?s=' . urlencode($query) . '&post_type=anime';
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                $results = [];
                $crawler->filter('ul.chivsrc li')->each(function (Crawler $node) use (&$results) {
                    $h2 = $node->filter('h2 a');
                    if ($h2->count() === 0) return;

                    $title = trim($h2->text());
                    $animeUrl = $h2->attr('href');
                    
                    $img = $node->filter('img');
                    $thumb = $img->count() ? ($img->attr('src') ?: $img->attr('data-src')) : '';

                    $status = '';
                    $rating = '';
                    $genres = [];

                    $node->filter('.set')->each(function (Crawler $setNode) use (&$status, &$rating, &$genres) {
                        $text = $setNode->text();
                        if (str_contains($text, 'Status')) {
                            $status = trim(str_replace(['Status', ':'], '', $text));
                        } elseif (str_contains($text, 'Rating')) {
                            $rating = trim(str_replace(['Rating', ':'], '', $text));
                        } elseif (str_contains($text, 'Genres')) {
                            $setNode->filter('a')->each(function (Crawler $gNode) use (&$genres) {
                                $genres[] = trim($gNode->text());
                            });
                        }
                    });

                    $results[] = [
                        'title' => $title,
                        'url' => $animeUrl,
                        'thumbnail' => $thumb,
                        'status' => $status ?: 'Ongoing',
                        'type' => 'TV',
                        'episode' => $rating ? "★ {$rating}" : 'Anime',
                        'rating' => $rating,
                        'genres' => $genres,
                    ];
                });

                return $results;
            } catch (\Exception $e) {
                Log::error('Otakudesu searchAnime error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Scrape anime by genre slug with pagination
     */
    public function scrapeAnimeByGenre(string $genreSlug, int $page = 1): array
    {
        $cacheKey = "otakudesu_genre_{$genreSlug}_p{$page}";
        return Cache::remember($cacheKey, 86400, function () use ($genreSlug, $page) {
            try {
                $url = $this->baseUrl . '/genres/' . $genreSlug . ($page > 1 ? "/page/{$page}/" : '/');
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                $results = [];
                $crawler->filter('.col-anime')->each(function (Crawler $node) use (&$results) {
                    $titleNode = $node->filter('.col-anime-title a');
                    if ($titleNode->count() === 0) return;

                    $title = trim($titleNode->text());
                    $animeUrl = $titleNode->attr('href');

                    $img = $node->filter('.col-anime-cover img, img');
                    $thumb = $img->count() ? ($img->attr('src') ?: $img->attr('data-src')) : '';

                    $ep = $node->filter('.col-anime-eps')->count() ? trim($node->filter('.col-anime-eps')->text()) : '';
                    $rating = $node->filter('.col-anime-rating')->count() ? trim($node->filter('.col-anime-rating')->text()) : '';

                    $results[] = [
                        'title' => $title,
                        'url' => $animeUrl,
                        'thumbnail' => $thumb,
                        'episode' => $ep ?: 'Anime',
                        'rating' => $rating,
                        'type' => 'TV',
                        'status' => 'Ongoing',
                    ];
                });

                return $results;
            } catch (\Exception $e) {
                Log::error('Otakudesu scrapeAnimeByGenre error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Scrape anime catalog list (Ongoing or Completed) with pagination
     */
    public function scrapeAnimeList(int $page = 1, string $status = ''): array
    {
        $statusKey = $status ? strtolower($status) : 'ongoing';
        $cacheKey = "otakudesu_list_{$statusKey}_p{$page}";

        return Cache::remember($cacheKey, 1800, function () use ($page, $statusKey) {
            try {
                $endpoint = ($statusKey === 'completed' || $statusKey === 'complete')
                    ? '/complete-anime'
                    : '/ongoing-anime';

                $url = $this->baseUrl . $endpoint . ($page > 1 ? "/page/{$page}/" : '/');
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                $results = [];
                $crawler->filter('.detpost')->each(function (Crawler $node) use (&$results, $statusKey) {
                    $titleNode = $node->filter('.jdlflm');
                    $linkNode = $node->filter('.thumb a, a');
                    if ($linkNode->count() === 0) return;

                    $title = $titleNode->count() ? trim($titleNode->text()) : '';
                    $animeUrl = $linkNode->first()->attr('href');

                    $img = $node->filter('img');
                    $thumb = $img->count() ? ($img->attr('src') ?: $img->attr('data-src')) : '';

                    $epNode = $node->filter('.epz');
                    $ep = $epNode->count() ? trim($epNode->text()) : '';

                    $typeNode = $node->filter('.epztipe');
                    $typeOrRating = $typeNode->count() ? trim($typeNode->text()) : '';

                    $dateNode = $node->filter('.newnime');
                    $date = $dateNode->count() ? trim($dateNode->text()) : '';

                    $results[] = [
                        'title' => $title,
                        'url' => $animeUrl,
                        'thumbnail' => $thumb,
                        'episode' => $ep ?: ($statusKey === 'completed' ? 'Tamat' : 'Ongoing'),
                        'type' => 'TV',
                        'status' => ($statusKey === 'completed' || $statusKey === 'complete') ? 'Completed' : 'Ongoing',
                        'rating' => is_numeric($typeOrRating) ? $typeOrRating : null,
                        'date' => $date,
                    ];
                });

                return $results;
            } catch (\Exception $e) {
                Log::error('Otakudesu scrapeAnimeList error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Scrape complete anime series details and episode list
     */
    public function scrapeAnimeSeriesDetail(string $url): ?array
    {
        $cacheKey = 'otakudesu_detail_' . md5($url);
        return Cache::remember($cacheKey, 86400, function () use ($url) {
            try {
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                // Title
                $title = '';
                $titleNode = $crawler->filter('.jdlcontent, h1.entry-title, .infozingle p:first-child');
                if ($titleNode->count() > 0) {
                    $title = trim($titleNode->first()->text());
                    $title = preg_replace('/^(?:Judul\s*:\s*)/i', '', $title);
                }

                // Thumbnail
                $thumb = '';
                $imgNode = $crawler->filter('.fotoanime img, .attachment-post-thumbnail');
                if ($imgNode->count() > 0) {
                    $thumb = $imgNode->first()->attr('src') ?: $imgNode->first()->attr('data-src');
                }

                // Synopsis
                $synopsis = '';
                $synNode = $crawler->filter('.sinopc');
                if ($synNode->count() > 0) {
                    $synopsis = trim($synNode->text());
                }

                // Metadata in infozingle
                $meta = [];
                $genres = [];
                $crawler->filter('.infozingle p')->each(function (Crawler $pNode) use (&$meta, &$genres) {
                    $text = trim($pNode->text());
                    if (str_contains($text, ':')) {
                        [$k, $v] = explode(':', $text, 2);
                        $meta[strtolower(trim($k))] = trim($v);
                    }

                    // Extract genre links
                    if (str_contains($text, 'Genre')) {
                        $pNode->filter('a')->each(function (Crawler $gLink) use (&$genres) {
                            $genres[] = trim($gLink->text());
                        });
                    }
                });

                // Episodes list
                $episodes = [];
                $crawler->filter('.episodelist ul li')->each(function (Crawler $node) use (&$episodes) {
                    $a = $node->filter('a');
                    if ($a->count() === 0) return;

                    $epTitle = trim($a->text());
                    $epUrl = $a->attr('href');

                    $date = '';
                    $dateNode = $node->filter('.zeebr');
                    if ($dateNode->count() > 0) {
                        $date = trim($dateNode->text());
                    }

                    // Extract episode number
                    $epNum = '1';
                    if (preg_match('/(?:episode|ep)\s*(\d+)/i', $epTitle, $m)) {
                        $epNum = $m[1];
                    }

                    $episodes[] = [
                        'episode' => $epNum,
                        'title' => $epTitle,
                        'url' => $epUrl,
                        'date' => $date,
                    ];
                });

                return [
                    'title' => $title,
                    'japanese_title' => $meta['japanese'] ?? null,
                    'score' => $meta['skor'] ?? null,
                    'status' => $meta['status'] ?? 'Ongoing',
                    'type' => $meta['tipe'] ?? 'TV',
                    'total_episodes' => $meta['total episode'] ?? count($episodes),
                    'duration' => $meta['durasi'] ?? null,
                    'release_date' => $meta['tanggal rilis'] ?? null,
                    'studio' => $meta['studio'] ?? null,
                    'genres' => $genres,
                    'synopsis' => $synopsis,
                    'thumbnail' => $thumb,
                    'episodes' => $episodes,
                ];
            } catch (\Exception $e) {
                Log::error('Otakudesu scrapeAnimeSeriesDetail error for ' . $url . ': ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Scrape episode video player URL, all streaming mirrors, and all download options
     */
    public function scrapeEpisodeDetail(string $url): ?array
    {
        $cacheKey = 'otakudesu_episode_full_' . md5($url);
        return Cache::remember($cacheKey, 3600, function () use ($url) {
            try {
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                // Episode title
                $title = '';
                $titleNode = $crawler->filter('h1.posttl, h1.entry-title');
                if ($titleNode->count() > 0) {
                    $title = trim($titleNode->first()->text());
                }

                // Episode number
                $episodeNumber = '1';
                if (preg_match('/(?:episode|ep)\s*(\d+)/i', $title, $m)) {
                    $episodeNumber = $m[1];
                }

                // Anime Series link & navigation (prev/next)
                $animeUrl = '';
                $animeSlug = '';
                $prevUrl = '';
                $nextUrl = '';

                $crawler->filter('.flir a, .prevnext a, .naveps a')->each(function (Crawler $node) use (&$animeUrl, &$prevUrl, &$nextUrl) {
                    $text = strtolower(trim($node->text()));
                    $href = $node->attr('href');
                    if (str_contains($text, 'all') || str_contains($text, 'semua') || str_contains($href, '/anime/')) {
                        $animeUrl = $href;
                    } elseif (str_contains($text, 'prev') || str_contains($text, 'sebelum')) {
                        $prevUrl = $href;
                    } elseif (str_contains($text, 'next') || str_contains($text, 'selanjut')) {
                        $nextUrl = $href;
                    }
                });

                if (!$animeUrl) {
                    // Fallback to breadcrumbs or links containing /anime/
                    $crawler->filter('.crumbs a, a')->each(function (Crawler $node) use (&$animeUrl) {
                        if ($animeUrl) return;
                        $href = $node->attr('href') ?? '';
                        if (str_contains($href, '/anime/') && !str_contains($href, '/genre')) {
                            $animeUrl = $href;
                        }
                    });
                }

                if ($animeUrl) {
                    $animeSlug = basename(rtrim($animeUrl, '/'));
                }

                // 1. Default fallback iframe
                $defaultIframe = '';
                $iframeNode = $crawler->filter('#pembed iframe, .responsive-embed-stream iframe');
                if ($iframeNode->count() > 0) {
                    $defaultIframe = $iframeNode->first()->attr('src') ?? '';
                }

                // 2. Discover all mirrors across qualities
                $mirrors = [];
                $crawler->filter('.mirrorstream ul')->each(function (Crawler $ulNode) use (&$mirrors) {
                    $qualityClass = $ulNode->attr('class') ?? '';
                    $fallbackQ = '720p';
                    if (preg_match('/m?(\d+p)/i', $qualityClass, $qm)) {
                        $fallbackQ = $qm[1];
                    }

                    $ulNode->filter('li a')->each(function (Crawler $aNode) use (&$mirrors, $fallbackQ) {
                        $dc = $aNode->attr('data-content');
                        $serverName = trim($aNode->text());
                        if ($dc) {
                            $decoded = json_decode(base64_decode($dc), true);
                            if ($decoded && is_array($decoded)) {
                                $q = $decoded['q'] ?? $fallbackQ;
                                $serverUpper = ucfirst($serverName);
                                $mirrors[] = [
                                    'server' => $serverName,
                                    'quality' => $q,
                                    'label' => "{$serverUpper} ({$q})",
                                    'id' => $decoded['id'] ?? null,
                                    'i' => $decoded['i'] ?? 0,
                                    'q' => $q,
                                ];
                            }
                        }
                    });
                });

                // Sort mirrors: 720p first, then 480p, then 360p
                usort($mirrors, function ($a, $b) {
                    $order = ['720p' => 1, '1080p' => 2, '480p' => 3, '360p' => 4];
                    $qa = $order[strtolower($a['quality'])] ?? 99;
                    $qb = $order[strtolower($b['quality'])] ?? 99;
                    return $qa <=> $qb;
                });

                // 3. Extract all download links
                $downloads = [];
                $crawler->filter('.download ul li')->each(function (Crawler $node) use (&$downloads) {
                    $strong = $node->filter('strong');
                    $format = $strong->count() ? trim($strong->text()) : 'Download';
                    $size = $node->filter('i')->count() ? trim($node->filter('i')->text()) : '';
                    $links = [];
                    $node->filter('a')->each(function (Crawler $a) use (&$links) {
                        $host = trim($a->text());
                        $dlUrl = $a->attr('href');
                        if ($host && $dlUrl) {
                            $links[] = [
                                'host' => $host,
                                'url' => $dlUrl,
                            ];
                        }
                    });
                    if (!empty($links)) {
                        $downloads[] = [
                            'format' => $format,
                            'size' => $size,
                            'links' => $links,
                        ];
                    }
                });

                // 4. Resolve default stream (prefer first 720p mirror)
                $defaultStream = null;
                if (!empty($mirrors)) {
                    $firstTarget = $mirrors[0];
                    $defaultStream = $this->resolveMirror(
                        $url,
                        $firstTarget['id'],
                        $firstTarget['i'],
                        $firstTarget['q'],
                        $firstTarget['server']
                    );
                }

                if (!$defaultStream || empty($defaultStream['stream_url'])) {
                    $defaultStream = [
                        'server' => 'Default',
                        'quality' => 'Default',
                        'stream_url' => $defaultIframe,
                        'iframe_url' => $defaultIframe,
                        'direct_mp4' => null,
                        'is_direct_mp4' => false,
                    ];
                }

                return [
                    'title' => $title,
                    'episode_number' => $episodeNumber,
                    'episode_url' => $url,
                    'episode_slug' => basename(rtrim($url, '/')),
                    'anime_url' => $animeUrl,
                    'anime_slug' => $animeSlug,
                    'prev_episode_url' => $prevUrl,
                    'prev_episode_slug' => $prevUrl ? basename(rtrim($prevUrl, '/')) : null,
                    'next_episode_url' => $nextUrl,
                    'next_episode_slug' => $nextUrl ? basename(rtrim($nextUrl, '/')) : null,
                    'mirrors' => $mirrors,
                    'downloads' => $downloads,
                    'default_stream' => $defaultStream,
                    'video_url' => $defaultStream['stream_url'] ?? $defaultIframe,
                    'direct_mp4' => $defaultStream['direct_mp4'] ?? null,
                    'iframe_url' => $defaultStream['iframe_url'] ?? $defaultIframe,
                    'quality' => $defaultStream['quality'] ?? 'HD',
                    'mirrors_count' => count($mirrors),
                ];
            } catch (\Exception $e) {
                Log::error('Otakudesu scrapeEpisodeDetail error for ' . $url . ': ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Resolve a specific mirror stream URL by its Otakudesu params
     */
    public function resolveMirror(string $episodeUrl, int $id, int $i, string $q, string $server = ''): ?array
    {
        $cacheKey = "otakudesu_mirror_{$id}_{$i}_{$q}";
        return Cache::remember($cacheKey, 7200, function () use ($episodeUrl, $id, $i, $q, $server) {
            try {
                // Fetch nonce
                $resNonce = $this->client->post($this->baseUrl . '/wp-admin/admin-ajax.php', [
                    'form_params' => ['action' => 'aa1208d27f29ca340c92c66d1926f13f'],
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => $episodeUrl,
                    ],
                    'timeout' => 8,
                ]);
                $nonceData = json_decode($resNonce->getBody()->getContents(), true);
                $nonce = $nonceData['data'] ?? '';

                if (!$nonce) {
                    return null;
                }

                // Request stream payload
                $resStream = $this->client->post($this->baseUrl . '/wp-admin/admin-ajax.php', [
                    'form_params' => [
                        'id' => $id,
                        'i' => $i,
                        'q' => $q,
                        'nonce' => $nonce,
                        'action' => '2a3505c93b0035d3f455df82bf976b84',
                    ],
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => $episodeUrl,
                    ],
                    'timeout' => 8,
                ]);
                $streamData = json_decode($resStream->getBody()->getContents(), true);
                $iframeUrl = '';

                if (!empty($streamData['data'])) {
                    $decodedHtml = base64_decode($streamData['data']);
                    if (preg_match('/src=[\"\']([^\"\']+)[\"\']/', $decodedHtml, $matchIframe)) {
                        $iframeUrl = $matchIframe[1];
                    }
                }

                if (!$iframeUrl) {
                    return null;
                }

                $directMp4 = null;

                // Inspect DesuStream for direct MP4 or CDN video
                if (str_contains($iframeUrl, 'desustream.net')) {
                    try {
                        $resDesu = $this->client->get($iframeUrl, [
                            'headers' => ['Referer' => $this->baseUrl . '/'],
                            'timeout' => 8,
                        ]);
                        $desuHtml = $resDesu->getBody()->getContents();

                        if (preg_match('/(?:videoURL\s*=\s*|file\s*:\s*|<source[^>]+src=)[\"\']([^\"\']+)[\"\']/', $desuHtml, $vm)) {
                            $candidate = html_entity_decode($vm[1]);
                            if (str_contains($candidate, '.mp4') || str_contains($candidate, 'googlevideo.com') || str_contains($candidate, 'odcloud.net') || str_contains($candidate, 'archive.org')) {
                                $directMp4 = $candidate;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed inspecting DesuStream direct video: ' . $e->getMessage());
                    }
                }

                $chosenUrl = $directMp4 ?: $iframeUrl;

                return [
                    'server' => $server,
                    'quality' => $q,
                    'stream_url' => $chosenUrl,
                    'iframe_url' => $iframeUrl,
                    'direct_mp4' => $directMp4 ?: null,
                    'is_direct_mp4' => !empty($directMp4),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed resolving Otakudesu mirror ({$server} {$q}): " . $e->getMessage());
                return null;
            }
        });
    }
}
