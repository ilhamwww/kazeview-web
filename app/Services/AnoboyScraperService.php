<?php

namespace App\Services;

use App\Models\ScraperLog;
use App\Models\ScraperSetting;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class AnoboyScraperService
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct()
    {
        // Get domain from settings
        $this->baseUrl = ScraperSetting::getInstance()->anoboy_domain;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => $this->getRandomHeaders(),
        ]);
    }

    /**
     * Get current base URL being used
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Search anime via ajax endpoint
     *
     * @param string $query
     * @return array List of anime data matching the query
     */
    public function searchAnime(string $query): array
    {
        $ajaxUrl = rtrim($this->baseUrl, '/') . '/wp-admin/admin-ajax.php';

        try {
            // WordPress admin-ajax.php typically expects POST requests with application/x-www-form-urlencoded body
            $response = $this->client->post($ajaxUrl, [
                'headers' => array_merge(
                    $this->getRandomHeaders(),
                    [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Origin' => rtrim($this->baseUrl, '/'),
                        'Referer' => rtrim($this->baseUrl, '/') . '/',
                    ]
                ),
                'form_params' => [
                    'action' => 'ts_ac_do_search',
                    'ts_ac_query' => $query,
                ]
            ]);

            $json = json_decode((string) $response->getBody(), true);
            $animes = [];

            if (isset($json['anime']) && is_array($json['anime'])) {
                foreach ($json['anime'] as $group) {
                    if (isset($group['all']) && is_array($group['all'])) {
                        foreach ($group['all'] as $item) {
                            $episode = '';
                            if (!empty($item['post_ep']) || !empty($item['post_latest'])) {
                                $episode = trim(($item['post_ep'] ?? '') . ' ' . ($item['post_latest'] ?? ''));
                            }

                            $animes[] = [
                                'title' => $item['post_title'] ?? '',
                                'url' => $item['post_link'] ?? '',
                                'thumbnail' => $item['post_image'] ?? '',
                                'episode' => $episode,
                                'type' => $item['post_type'] ?? '',
                                'status' => $item['post_sub'] ?? '',
                            ];
                        }
                    }
                }
            }

            return $animes;
        } catch (\Exception $e) {
            \Log::error('Anoboy search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape all available genres
     *
     * @return array List of genres with name, slug, and url
     */
    public function scrapeGenres(): array
    {
        try {
            $html = $this->fetchHtml('anime/');
            $crawler = new Crawler($html);

            $genres = [];
            $crawler->filter('.section ul.genre li a, ul.genre li a')->each(function (Crawler $node) use (&$genres) {
                $url = $node->attr('href') ?? '';
                $name = trim($node->text());

                if (empty($url) || empty($name)) {
                    return;
                }

                $path = parse_url($url, PHP_URL_PATH);
                $slug = '';
                if ($path) {
                    $parts = array_filter(explode('/', trim($path, '/')));
                    $slug = end($parts);
                }

                if ($slug && !isset($genres[$slug])) {
                    $genres[$slug] = [
                        'name' => $name,
                        'slug' => $slug,
                        'url' => $url,
                    ];
                }
            });

            return array_values($genres);
        } catch (\Exception $e) {
            \Log::error('Anoboy scrape genres error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape anime list by genre
     *
     * @param string $genreSlug The genre slug (e.g., 'action')
     * @param int $page Page number (default 1)
     * @return array List of anime data
     */
    public function scrapeAnimeByGenre(string $genreSlug, int $page = 1): array
    {
        $url = $page === 1 ? "genres/{$genreSlug}/" : "genres/{$genreSlug}/page/{$page}/";

        try {
            $html = $this->fetchHtml($url);

            return $this->parseAnimeList($html);
        } catch (\Exception $e) {
            \Log::error("Anoboy genre scraper error for {$genreSlug} page {$page}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape anime list from anoboy.be with status filter support
     *
     * @param int $page Page number (default 1)
     * @param string $status Filter by status (completed, ongoing, or empty)
     * @return array List of anime data
     */
    public function scrapeAnimeList(int $page = 1, string $status = ''): array
    {
        $statusParam = filled($status) ? "&status={$status}" : "";
        $url = "anime/?page={$page}{$statusParam}&sub=&order=update";

        try {
            $html = $this->fetchHtml($url);

            return $this->parseAnimeList($html);
        } catch (\Exception $e) {
            \Log::error('Anoboy scraper error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse anime list from HTML
     * Extracts anime series (not individual episodes) and builds the anime detail URL
     *
     * @param string $html
     * @return array
     */
    protected function parseAnimeList(string $html): array
    {
        $crawler = new Crawler($html);
        $animes = [];
        $seenUrls = [];

        // Select all anime items with class .bsx
        $crawler->filter('.bsx')->each(function (Crawler $node) use (&$animes, &$seenUrls) {
            $linkNode = $node->filter('a')->first();

            if ($linkNode->count() === 0) {
                return;
            }

            $episodeUrl = $linkNode->attr('href') ?? '';
            $title = $linkNode->attr('title') ?? '';

            // Get thumbnail
            $thumbnail = '';
            $imgNode = $node->filter('img');
            if ($imgNode->count() > 0) {
                $thumbnail = $imgNode->attr('src') ?? '';
            }

            // Get episode info
            $episode = '';
            $epxNode = $node->filter('.epx');
            if ($epxNode->count() > 0) {
                $episode = trim($epxNode->text());
            }

            // Get type (TV, Movie, etc)
            $type = '';
            $typezNode = $node->filter('.typez');
            if ($typezNode->count() > 0) {
                $type = trim($typezNode->text());
            }

            // Get status (Sub/Dub)
            $status = '';
            $sbNode = $node->filter('.sb');
            if ($sbNode->count() > 0) {
                $status = trim($sbNode->text());
            }

            // Clean title - remove episode info if present
            $cleanTitle = $title;
            if (preg_match('/^(.+?)\s*Episode\s*\d+/i', $title, $matches)) {
                $cleanTitle = trim($matches[1]);
            }

            // Derive anime series URL from episode URL
            $animeUrl = $this->deriveAnimeSeriesUrl($episodeUrl);

            if (empty($animeUrl) || empty($cleanTitle)) {
                return;
            }

            // Deduplicate by anime series URL
            if (isset($seenUrls[$animeUrl])) {
                return;
            }
            $seenUrls[$animeUrl] = true;

            $animes[] = [
                'title' => $cleanTitle,
                'url' => $animeUrl,
                'thumbnail' => $thumbnail,
                'episode' => $episode,
                'type' => $type,
                'status' => $status,
            ];
        });

        return $animes;
    }

    /**
     * Derive anime series URL from an episode URL
     *
     * @param string $episodeUrl
     * @return string
     */
    protected function deriveAnimeSeriesUrl(string $episodeUrl): string
    {
        // Parse the URL to get the path
        $path = parse_url($episodeUrl, PHP_URL_PATH);

        if (!$path) {
            return '';
        }

        // Remove leading/trailing slashes and get the slug
        $slug = trim($path, '/');

        // Remove episode suffix patterns
        $slug = preg_replace('/-episode-\d+(-subtitle-indonesia)?\/?$/', '', $slug);

        if (empty($slug)) {
            return '';
        }

        // Build the anime series URL
        return rtrim($this->baseUrl, '/') . '/anime/' . $slug . '/';
    }

    /**
     * Scrape anime series detail page for episode list
     * URL format: https://anoboy.be/anime/liar-game/
     *
     * @param string $url Full URL to anime series detail page
     * @return array|null Array with title, episodes list, thumbnail, or null on failure
     */
    public function scrapeAnimeSeriesDetail(string $url): ?array
    {
        try {
            $html = $this->fetchHtml($url);

            return $this->parseAnimeSeriesDetail($html, $url);
        } catch (\Exception $e) {
            \Log::error('Anoboy series detail scraper error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse anime series detail page HTML for episode list
     *
     * @param string $html
     * @param string $url
     * @return array|null
     */
    protected function parseAnimeSeriesDetail(string $html, string $url): ?array
    {
        $crawler = new Crawler($html);

        // Get title from h1.entry-title or title tag
        $title = '';
        $titleNode = $crawler->filter('h1.entry-title, .entry-title, h1');
        if ($titleNode->count() > 0) {
            $title = trim($titleNode->first()->text());
        } else {
            $titleNode = $crawler->filter('title');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
                $title = preg_replace('/\s*[-|]\s*[^-|]+$/', '', $title);
            }
        }

        // Get thumbnail/featured image
        $thumbnail = '';
        $thumbNode = $crawler->filter('.thumb img, .post-thumbnail img, meta[property="og:image"]');
        if ($thumbNode->count() > 0) {
            $thumbnail = $thumbNode->first()->attr('src') ?? $thumbNode->first()->attr('content') ?? '';
        }

        // Get description/synopsis
        $description = '';
        $descNode = $crawler->filter('.entry-content, .desc, .sinopsis, .synopsis');
        if ($descNode->count() > 0) {
            $description = trim($descNode->first()->text());
        }

        // Get genre/info metadata
        $genres = [];
        $genreNodes = $crawler->filter('.genxed a, .genre-info a, .info-content a[rel="tag"]');
        $genreNodes->each(function (Crawler $node) use (&$genres) {
            $genres[] = trim($node->text());
        });

        // Parse episode list from .eplister
        $episodes = [];
        $epListNodes = $crawler->filter('.eplister ul li');
        $epListNodes->each(function (Crawler $node) use (&$episodes) {
            $linkNode = $node->filter('a')->first();
            if ($linkNode->count() === 0) {
                return;
            }

            $epUrl = $linkNode->attr('href') ?? '';
            $epNum = '';
            $epTitle = '';
            $epDate = '';

            $numNode = $node->filter('.epl-num');
            if ($numNode->count() > 0) {
                $epNum = trim($numNode->text());
            }

            $titleNode = $node->filter('.epl-title');
            if ($titleNode->count() > 0) {
                $epTitle = trim($titleNode->text());
            }

            $dateNode = $node->filter('.epl-date');
            if ($dateNode->count() > 0) {
                $epDate = trim($dateNode->text());
            }

            if (!empty($epUrl)) {
                $episodes[] = [
                    'episode' => $epNum,
                    'title' => $epTitle,
                    'date' => $epDate,
                    'url' => $epUrl,
                ];
            }
        });

        if (empty($title)) {
            return null;
        }

        return [
            'title' => $title,
            'thumbnail' => $thumbnail,
            'description' => $description,
            'genres' => $genres,
            'episodes' => $episodes,
            'source_url' => $url,
        ];
    }

    /**
     * Scrape and save anime to database, with logging and skip logic
     *
     * @param int $page
     * @param bool $force Force re-scrape even if already logged
     * @return array ['saved' => int, 'skipped' => int, 'found' => int, 'status' => string]
     */
    public function scrapeAndSave(int $page = 1, bool $force = false): array
    {
        $startTime = microtime(true);
        $url = "anime/?page={$page}&sub=&order=update";

        // Check if page already scraped successfully
        if (!$force && ScraperLog::isPageScraped('anoboy', $page)) {
            $duration = round(microtime(true) - $startTime, 3);
            ScraperLog::create([
                'source' => 'anoboy',
                'page' => $page,
                'url' => $this->baseUrl . $url,
                'items_found' => 0,
                'items_saved' => 0,
                'items_skipped' => 0,
                'status' => 'skipped',
                'duration_seconds' => $duration,
            ]);

            return ['saved' => 0, 'skipped' => 0, 'found' => 0, 'status' => 'skipped'];
        }

        try {
            $animes = $this->scrapeAnimeList($page);
            $saved = 0;
            $skipped = 0;

            foreach ($animes as $animeData) {
                $existing = \App\Models\Anime::where('url', $animeData['url'])->first();

                if (!$existing) {
                    \App\Models\Anime::create($animeData);
                    $saved++;
                } else {
                    $skipped++;
                }
            }

            $duration = round(microtime(true) - $startTime, 3);

            // Log the scraping result (updateOrCreate to handle re-scrapes)
            ScraperLog::updateOrCreate(
                ['source' => 'anoboy', 'page' => $page],
                [
                    'url' => $this->baseUrl . $url,
                    'items_found' => count($animes),
                    'items_saved' => $saved,
                    'items_skipped' => $skipped,
                    'status' => 'success',
                    'duration_seconds' => $duration,
                    'error_message' => null,
                ]
            );

            return ['saved' => $saved, 'skipped' => $skipped, 'found' => count($animes), 'status' => 'success'];
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 3);

            ScraperLog::updateOrCreate(
                ['source' => 'anoboy', 'page' => $page],
                [
                    'url' => $this->baseUrl . $url,
                    'items_found' => 0,
                    'items_saved' => 0,
                    'items_skipped' => 0,
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                    'duration_seconds' => $duration,
                ]
            );

            \Log::error('Anoboy scraper error: ' . $e->getMessage());
            return ['saved' => 0, 'skipped' => 0, 'found' => 0, 'status' => 'error'];
        }
    }

    /**
     * Check if a page has a "next" button (meaning there are more pages)
     *
     * @param int $currentPage
     * @return bool
     */
    public function hasNextPage(int $currentPage): bool
    {
        try {
            $url = "anime/?page={$currentPage}&status=&type=&order=";
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            // Check for "next" link in pagination
            // Common patterns: a.next, a with text "Next", a with "›", a with "»"
            $nextLinks = $crawler->filter('.pagination a.next, .page-numbers a.next, a.next.page-numbers');
            if ($nextLinks->count() > 0) {
                return true;
            }

            // Also check for any link pointing to page+1
            $nextPage = $currentPage + 1;
            $allLinks = $crawler->filter('.pagination a, .page-numbers a, .hpage a');
            $hasNext = false;

            $allLinks->each(function (Crawler $node) use ($nextPage, &$hasNext) {
                $href = $node->attr('href') ?? '';
                $text = trim($node->text());

                // Check if link points to next page
                if (preg_match('/[?&]page=' . $nextPage . '/', $href)) {
                    $hasNext = true;
                }

                // Check for common "next" text patterns
                if (in_array(strtolower($text), ['next', 'next »', '»', '›', '→', 'selanjutnya'])) {
                    $hasNext = true;
                }
            });

            return $hasNext;
        } catch (\Exception $e) {
            \Log::error('Anoboy next page check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total pages by following next buttons from page 1
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        try {
            $url = 'anime/?page=1&status=&type=&order=';
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            // Method 1: Find the highest page number in pagination links
            $maxPage = 1;
            $allLinks = $crawler->filter('.pagination a, .page-numbers a, .hpage a');

            $allLinks->each(function (Crawler $node) use (&$maxPage) {
                $href = $node->attr('href') ?? '';
                $text = trim($node->text());

                // Extract page number from URL
                if (preg_match('/[?&]page=(\d+)/', $href, $matches)) {
                    $maxPage = max($maxPage, (int) $matches[1]);
                }

                // Also check if the text itself is a page number
                if (is_numeric($text)) {
                    $maxPage = max($maxPage, (int) $text);
                }
            });

            // Method 2: If we found a "next" button but no high page numbers,
            // the last page link might be the highest numbered link
            return $maxPage;
        } catch (\Exception $e) {
            \Log::error('Anoboy pagination error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Scrape all pages from first to last, following the "next" button
     * Skips already-scraped pages unless $force is true
     *
     * @param callable|null $onProgress Callback function(int $currentPage, array $result) for progress updates
     * @param bool $force Force re-scrape even if already logged
     * @return array ['total_saved' => int, 'total_skipped' => int, 'total_pages' => int, 'pages_scraped' => int, 'pages_skipped' => int]
     */
    public function scrapeAllPages(?callable $onProgress = null, bool $force = false): array
    {
        $currentPage = 1;
        $totalSaved = 0;
        $totalSkipped = 0;
        $totalPages = 1;
        $pagesScraped = 0;
        $pagesSkipped = 0;

        while (true) {
            $result = $this->scrapeAndSave($currentPage, $force);

            if ($result['status'] === 'skipped') {
                $pagesSkipped++;
            } else {
                $pagesScraped++;
                $totalSaved += $result['saved'];
                $totalSkipped += $result['skipped'];
            }

            if ($onProgress) {
                $onProgress($currentPage, $result);
            }

            // Check if there's a next page
            if (!$this->hasNextPage($currentPage)) {
                $totalPages = $currentPage;
                break;
            }

            $currentPage++;

            // Safety limit to prevent infinite loops
            if ($currentPage > 1000) {
                \Log::warning('Anoboy scraper reached safety limit of 1000 pages');
                $totalPages = $currentPage;
                break;
            }
        }

        return [
            'total_saved' => $totalSaved,
            'total_skipped' => $totalSkipped,
            'total_pages' => $totalPages,
            'pages_scraped' => $pagesScraped,
            'pages_skipped' => $pagesSkipped,
        ];
    }


    /**
     * Scrape episode page for video URL
     * URL format: https://anoboy.be/liar-game-episode-1-subtitle-indonesia/
     *
     * @param string $url Full URL to episode page
     * @return array|null Array with title, video_url, or null on failure
     */
    public function scrapeEpisodeDetail(string $url): ?array
    {
        try {
            $html = $this->fetchHtml($url);

            return $this->parseEpisodeDetail($html, $url);
        } catch (\Exception $e) {
            \Log::error('Anoboy episode scraper error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse episode page HTML for video URL
     *
     * @param string $html
     * @param string $url
     * @return array|null
     */
    protected function parseEpisodeDetail(string $html, string $url): ?array
    {
        $crawler = new Crawler($html);

        // Get title from h1.entry-title or title tag
        $title = '';
        $titleNode = $crawler->filter('h1.entry-title, .entry-title, h1');
        if ($titleNode->count() > 0) {
            $title = trim($titleNode->first()->text());
        } else {
            $titleNode = $crawler->filter('title');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
                $title = preg_replace('/\s*[-|]\s*[^-|]+$/', '', $title);
            }
        }

        // Get video URL from iframe
        $videoUrl = '';
        $iframeNode = $crawler->filter('iframe');
        if ($iframeNode->count() > 0) {
            $videoUrl = $iframeNode->first()->attr('src') ?? '';
        }

        // Try to find video in common video player containers
        if (empty($videoUrl)) {
            $videoNode = $crawler->filter('video source, video');
            if ($videoNode->count() > 0) {
                $videoUrl = $videoNode->first()->attr('src') ?? '';
            }
        }

        // Try to extract from script tags
        if (empty($videoUrl)) {
            $crawler->filter('script')->each(function (Crawler $node) use (&$videoUrl) {
                $script = $node->text();
                if (preg_match('/["\']?(https?:\/\/[^\s"\']+?(?:embed|player|video)[^\s"\']*?)["\']?/i', $script, $matches)) {
                    $videoUrl = $matches[1];
                }
            });
        }

        if (empty($title)) {
            return null;
        }

        return [
            'title' => $title,
            'video_url' => $videoUrl,
            'source_url' => $url,
        ];
    }

    /**
     * Get HTML content from URL, with fallback to Puppeteer if blocked or challenge detected.
     */
    protected function fetchHtml(string $url): string
    {
        try {
            $response = $this->client->get($url, [
                'headers' => $this->getRandomHeaders(),
            ]);
            $html = (string) $response->getBody();

            // Check if HTML contains Cloudflare challenge page
            if (
                str_contains($html, 'Just a moment...') || 
                str_contains($html, 'Attention Required!') || 
                str_contains($html, 'Cloudflare') || 
                str_contains($html, 'enable JavaScript')
            ) {
                throw new \Exception("Cloudflare or anti-bot challenge page detected.");
            }

            return $html;
        } catch (\Exception $e) {
            \Log::warning('Guzzle failed or blocked (' . $e->getMessage() . '). Falling back to Puppeteer for: ' . $url);
            return $this->fetchWithPuppeteer($url);
        }
    }

    /**
     * Fetch HTML using Puppeteer headless browser
     */
    protected function fetchWithPuppeteer(string $url): string
    {
        $fullUrl = str_starts_with($url, 'http') ? $url : rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
        $scriptPath = base_path('puppeteer_scraper.js');

        // Escape arguments carefully for Windows execution
        $cmd = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($fullUrl);

        $output = [];
        $returnVar = 0;
        
        // Execute the command
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Puppeteer execution failed with exit code {$returnVar}. Output: " . implode("\n", $output));
        }

        return implode("\n", $output);
    }

    /**
     * Get modern HTTP headers representing a random browser profile
     */
    protected function getRandomHeaders(): array
    {
        $profiles = [
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'none',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => '1',
            ],
            [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"macOS"',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'none',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => '1',
            ],
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id,en-US;q=0.7,en;q=0.3',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
            ],
        ];

        return $profiles[array_rand($profiles)];
    }
}
