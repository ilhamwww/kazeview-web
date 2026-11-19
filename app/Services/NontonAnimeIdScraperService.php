<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NontonAnimeIdScraperService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct()
    {
        $this->baseUrl = config('services.nontonanimeid.base_url', 'https://nontonanimeid.my.id');
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
     * Transform a direct CDN stream URL into a VPS relay URL
     */
    public function formatRelayUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $path = ltrim($parsed['path'] ?? '', '/');
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        if (!empty($host) && !empty($path)) {
            return "/stream-relay/p/{$host}/{$path}{$query}";
        }

        return $url;
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
     * Scrape Homepage / Latest updates
     */
    public function scrapeHomepage(int $page = 1): array
    {
        $url = $page > 1 ? "{$this->baseUrl}/page/{$page}/" : "{$this->baseUrl}/";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $items = [];
            $crawler->filter('article, .bsx')->each(function (Crawler $node) use (&$items) {
                $a = $node->filter('a')->first();
                $titleEl = $node->filter('h2[itemprop="headline"], h2, .tt, .title')->first();
                $epEl = $node->filter('.epx, .bt .ep, .ep')->first();
                $imgEl = $node->filter('img')->first();
                $typeEl = $node->filter('.typez')->first();

                $title = $titleEl->count() ? trim($titleEl->text()) : '';
                $rawUrl = $a->count() ? $a->attr('href') : '';
                $img = $imgEl->count() ? ($imgEl->attr('src') ?: $imgEl->attr('data-src')) : '';
                $ep = $epEl->count() ? trim($epEl->text()) : '';
                $type = $typeEl->count() ? trim($typeEl->text()) : 'Anime';

                if (!empty($rawUrl)) {
                    $slug = basename(rtrim($rawUrl, '/'));
                    
                    // Ekstrak nama serial anime dari judul episode jika ada
                    $animeTitle = preg_replace('/Episode\s+\d+.*$/i', '', $title);
                    $animeTitle = trim(preg_replace('/^(?:♡|\s)+/u', '', $animeTitle));

                    $items[$slug] = [
                        'title' => $title ?: $animeTitle,
                        'anime_title' => $animeTitle ?: $title,
                        'slug' => $slug,
                        'url' => $rawUrl,
                        'thumbnail' => $img,
                        'episode' => $ep,
                        'type' => $type,
                    ];
                }
            });

            // Pagination info
            $hasNext = $crawler->filter('.pagination a.next, .pagination .next, a[rel="next"]')->count() > 0;

            return [
                'latest' => array_values($items),
                'hasNext' => $hasNext,
                'page' => $page,
            ];
        } catch (\Throwable $e) {
            Log::error("NontonAnimeID Scraper Homepage failed: {$e->getMessage()}");
            return [
                'latest' => [],
                'hasNext' => false,
                'page' => $page,
            ];
        }
    }

    /**
     * Search Anime by query
     */
    public function searchAnime(string $query, int $page = 1): array
    {
        $encoded = urlencode(trim($query));
        $url = $page > 1 ? "{$this->baseUrl}/page/{$page}/?s={$encoded}" : "{$this->baseUrl}/?s={$encoded}";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $items = [];
            $crawler->filter('article, .bsx')->each(function (Crawler $node) use (&$items) {
                $a = $node->filter('a')->first();
                $titleEl = $node->filter('h2')->first();
                if (!$titleEl->count()) {
                    $titleEl = $node->filter('.title, .tt')->first();
                }
                $imgEl = $node->filter('img')->first();
                $typeEl = $node->filter('.typez')->first();
                $statusEl = $node->filter('.status')->first();

                $rawUrl = $a->count() ? $a->attr('href') : '';
                $title = $titleEl->count() ? trim($titleEl->text()) : '';
                $img = $imgEl->count() ? ($imgEl->attr('src') ?: $imgEl->attr('data-src')) : '';
                $type = $typeEl->count() ? trim($typeEl->text()) : 'Anime';
                $status = $statusEl->count() ? trim($statusEl->text()) : '';

                if (!empty($rawUrl)) {
                    $slug = basename(rtrim($rawUrl, '/'));
                    $isSeries = str_contains($rawUrl, '/anime/');

                    $cleanTitle = trim(preg_replace('/^(?:♡|\s)+/u', '', $title));

                    $items[$slug] = [
                        'title' => $cleanTitle ?: $title,
                        'slug' => $slug,
                        'url' => $rawUrl,
                        'thumbnail' => $img,
                        'type' => $type,
                        'status' => $status,
                        'is_series' => $isSeries,
                    ];
                }
            });

            return array_values($items);
        } catch (\Throwable $e) {
            Log::error("NontonAnimeID Scraper Search failed: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Scrape Catalog Anime List
     */
    public function scrapeAnimeList(int $page = 1, string $status = '', string $order = 'latest'): array
    {
        $params = [];
        if (!empty($status)) {
            $params['status'] = $status;
        }
        if ($order === 'popular') {
            $params['order'] = 'popular';
        }

        $qs = !empty($params) ? '?' . http_build_query($params) : '';
        $url = $page > 1 ? "{$this->baseUrl}/anime/page/{$page}/{$qs}" : "{$this->baseUrl}/anime/{$qs}";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $items = [];
            $crawler->filter('article, .bsx')->each(function (Crawler $node) use (&$items) {
                $a = $node->filter('a')->first();
                $titleEl = $node->filter('h2')->first();
                if (!$titleEl->count()) {
                    $titleEl = $node->filter('.title, .tt')->first();
                }
                $imgEl = $node->filter('img')->first();
                $typeEl = $node->filter('.typez')->first();
                $statusEl = $node->filter('.status')->first();

                $rawUrl = $a->count() ? $a->attr('href') : '';
                $title = $titleEl->count() ? trim($titleEl->text()) : '';
                $img = $imgEl->count() ? ($imgEl->attr('src') ?: $imgEl->attr('data-src')) : '';
                $type = $typeEl->count() ? trim($typeEl->text()) : 'Anime';
                $status = $statusEl->count() ? trim($statusEl->text()) : '';

                if (!empty($rawUrl)) {
                    $slug = basename(rtrim($rawUrl, '/'));
                    $cleanTitle = trim(preg_replace('/^(?:♡|\s)+/u', '', $title));

                    $items[$slug] = [
                        'title' => $cleanTitle ?: $title,
                        'slug' => $slug,
                        'url' => $rawUrl,
                        'thumbnail' => $img,
                        'type' => $type,
                        'status' => $status,
                    ];
                }
            });

            $hasNext = $crawler->filter('.pagination a.next, .pagination .next, a[rel="next"]')->count() > 0;

            return [
                'items' => array_values($items),
                'hasNext' => $hasNext,
            ];
        } catch (\Throwable $e) {
            Log::error("NontonAnimeID Scraper AnimeList failed: {$e->getMessage()}");
            return [
                'items' => [],
                'hasNext' => false,
            ];
        }
    }

    /**
     * Scrape Anime Series Detail
     */
    public function scrapeAnimeSeriesDetail(string $slug): ?array
    {
        $cleanSlug = trim($slug, '/');
        // Decode URI component jika slug ter-encode URL (seperti ♡)
        $url = "{$this->baseUrl}/anime/{$cleanSlug}/";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $titleEl = $crawler->filter('h1.entry-title, h1')->first();
            $title = $titleEl->count() ? trim($titleEl->text()) : '';
            $title = trim(preg_replace('/^(?:♡|\s)+/u', '', $title));

            $synopsisEl = $crawler->filter('.entry-content, .synopsis')->first();
            $synopsis = $synopsisEl->count() ? trim($synopsisEl->text()) : '';

            $thumbEl = $crawler->filter('.thumb img, .anmsa img, .content-thumb img')->first();
            $thumb = $thumbEl->count() ? ($thumbEl->attr('src') ?: $thumbEl->attr('data-src')) : '';

            // Metadata parsing
            $status = '';
            $type = 'Anime';
            $studio = '';
            $duration = '';
            $season = '';
            $episodesCount = 'Unknown';
            $score = '';

            $crawler->filter('.info-content span, .spe span')->each(function (Crawler $span) use (&$status, &$type, &$studio, &$duration, &$season, &$episodesCount) {
                $text = trim($span->text());
                if (stripos($text, 'Status:') !== false) $status = trim(str_ireplace('Status:', '', $text));
                elseif (stripos($text, 'Tipe:') !== false || stripos($text, 'Type:') !== false) $type = trim(str_ireplace(['Tipe:', 'Type:'], '', $text));
                elseif (stripos($text, 'Studio:') !== false) $studio = trim(str_ireplace('Studio:', '', $text));
                elseif (stripos($text, 'Durasi:') !== false || stripos($text, 'Duration:') !== false) $duration = trim(str_ireplace(['Durasi:', 'Duration:'], '', $text));
                elseif (stripos($text, 'Season:') !== false) $season = trim(str_ireplace('Season:', '', $text));
                elseif (stripos($text, 'Episode:') !== false) $episodesCount = trim(str_ireplace('Episode:', '', $text));
            });

            $scoreEl = $crawler->filter('.rating strong, .rating-prc .rtb, .numscore, .score')->first();
            if ($scoreEl->count()) {
                if (preg_match('/(\d+\.?\d*)/', $scoreEl->text(), $sm)) {
                    $score = $sm[1];
                }
            }

            // Genres
            $genres = [];
            $crawler->filter('.genxed a, .genres a, a[href*="/genres/"]')->each(function (Crawler $g) use (&$genres) {
                $name = trim($g->text());
                if (!empty($name) && !in_array($name, $genres, true)) {
                    $genres[] = $name;
                }
            });

            // Episodes list
            $episodes = [];
            $crawler->filter('ul.eplister li, .eplister li')->each(function (Crawler $li) use (&$episodes) {
                $a = $li->filter('a')->first();
                $num = $li->filter('.epl-num')->first();
                $epTitle = $li->filter('.epl-title')->first();
                $date = $li->filter('.epl-date')->first();

                if ($a->count()) {
                    $epUrl = $a->attr('href');
                    $epSlug = basename(rtrim($epUrl, '/'));
                    $episodes[] = [
                        'number' => $num->count() ? trim($num->text()) : '1',
                        'title' => $epTitle->count() ? trim($epTitle->text()) : 'Episode',
                        'slug' => $epSlug,
                        'url' => $epUrl,
                        'date' => $date->count() ? trim($date->text()) : '',
                    ];
                }
            });

            return [
                'title' => $title,
                'slug' => $cleanSlug,
                'synopsis' => $synopsis,
                'thumbnail' => $thumb,
                'status' => $status ?: 'Ongoing',
                'type' => $type,
                'studio' => $studio,
                'duration' => $duration,
                'season' => $season,
                'score' => $score,
                'genres' => $genres,
                'total_episodes' => $episodesCount !== 'Unknown' ? $episodesCount : count($episodes),
                'episodes' => $episodes,
            ];
        } catch (\Throwable $e) {
            Log::error("NontonAnimeID Scraper AnimeSeriesDetail failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Scrape Episode Stream Detail & Mirrors
     */
    public function scrapeEpisodeDetail(string $episodeSlug): ?array
    {
        $cleanSlug = trim($episodeSlug, '/');
        $url = "{$this->baseUrl}/{$cleanSlug}/";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $titleEl = $crawler->filter('h1.entry-title, h1')->first();
            $title = $titleEl->count() ? trim($titleEl->text()) : 'Episode';
            $title = trim(preg_replace('/^(?:♡|\s)+/u', '', $title));

            // Extract episode number
            $episodeNumber = '1';
            if (preg_match('/Episode\s+(\d+)/i', $title, $m)) {
                $episodeNumber = $m[1];
            }

            // Navigation
            $prevLink = $crawler->filter('.naveps .nvs:not(.rght) a, a[rel="prev"]')->first();
            $nextLink = $crawler->filter('.naveps .nvs.rght a, a[rel="next"]')->first();
            $allLink = $crawler->filter('.naveps .nvs.nvsc a')->first();

            $prevSlug = $prevLink->count() ? basename(rtrim($prevLink->attr('href'), '/')) : null;
            $nextSlug = $nextLink->count() ? basename(rtrim($nextLink->attr('href'), '/')) : null;
            
            $animeSlug = '';
            if ($allLink->count()) {
                $animeSlug = basename(rtrim($allLink->attr('href'), '/'));
            } else {
                $animeSlug = preg_replace('/-episode-\d+.*$/i', '', $cleanSlug);
            }

            // Scrape and decode mirrors
            $mirrors = [];
            $crawler->filter('select.mirror option')->each(function (Crawler $opt) use (&$mirrors) {
                $label = trim($opt->text());
                $val = $opt->attr('value');
                if (empty($val)) {
                    return;
                }

                $decoded = base64_decode($val);
                $streamUrl = '';
                $type = 'embed';

                if (preg_match("/<video[^>]*>.*?<source[^>]+src=[\x27\x22]([^\x27\x22]+)[\x27\x22]/is", $decoded, $m)) {
                    $streamUrl = $m[1];
                    $type = str_contains($streamUrl, '.mp4') ? 'mp4' : 'hls';
                } elseif (preg_match("/<iframe[^>]+src=[\x27\x22]([^\x27\x22]+)[\x27\x22]/i", $decoded, $m)) {
                    $streamUrl = $m[1];
                    $type = str_contains($streamUrl, '.tar') || str_contains($streamUrl, '.m3u8') ? 'hls' : 'iframe';
                }

                if (!empty($streamUrl)) {
                    $quality = '720p HD';
                    if (str_contains($label, '1080')) $quality = '1080p FHD';
                    elseif (str_contains($label, '480')) $quality = '480p SD';
                    elseif (str_contains($label, '360')) $quality = '360p';

                    $relayUrl = $this->formatRelayUrl($streamUrl);

                    $mirrors[] = [
                        'name' => $label,
                        'label' => $label,
                        'server' => $label,
                        'quality' => $quality,
                        'type' => $type,
                        'direct_url' => $streamUrl,
                        'relay_url' => $relayUrl,
                        'stream_url' => $relayUrl, // default relay through VPS
                    ];
                }
            });

            // Sort mirrors: 1080p first, then 720p, 480p, 360p
            usort($mirrors, function ($a, $b) {
                $order = ['1080' => 1, '720' => 2, '480' => 3, '360' => 4];
                $getRank = function ($item) use ($order) {
                    foreach ($order as $k => $rank) {
                        if (str_contains($item['label'], $k)) return $rank;
                    }
                    return 99;
                };
                return $getRank($a) <=> $getRank($b);
            });

            $defaultStream = !empty($mirrors) ? $mirrors[0] : [
                'name' => 'Server Default',
                'label' => 'Server Default',
                'server' => 'Server Default',
                'quality' => '720p HD',
                'type' => 'mp4',
                'direct_url' => '',
                'relay_url' => '',
                'stream_url' => '',
            ];

            return [
                'title' => $title,
                'episode_number' => $episodeNumber,
                'episode_slug' => $cleanSlug,
                'anime_slug' => $animeSlug,
                'prev_episode_slug' => $prevSlug,
                'next_episode_slug' => $nextSlug,
                'mirrors' => $mirrors,
                'default_stream' => $defaultStream,
                'downloads' => [],
            ];
        } catch (\Throwable $e) {
            Log::error("NontonAnimeID Scraper EpisodeDetail failed: {$e->getMessage()}");
            return null;
        }
    }
}
