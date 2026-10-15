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
        $domain = ScraperSetting::getInstance()->anoboy_domain ?: 'https://anoboy.quest/';
        $this->baseUrl = rtrim($domain, '/') . '/';

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 20,
            'allow_redirects' => true,
            'http_errors' => false,
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
     * Search anime via WordPress search query
     *
     * @param string $query
     * @return array List of anime data matching the query
     */
    public function searchAnime(string $query): array
    {
        if (empty(trim($query))) {
            return [];
        }

        try {
            $url = '?s=' . urlencode(trim($query));
            $html = $this->fetchHtml($url);

            return $this->parseAnimeList($html);
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
        $genresMap = [
            'action' => 'Action',
            'adventure' => 'Adventure',
            'comedy' => 'Comedy',
            'drama' => 'Drama',
            'ecchi' => 'Ecchi',
            'fantasy' => 'Fantasy',
            'harem' => 'Harem',
            'horror' => 'Horror',
            'isekai' => 'Isekai',
            'magic' => 'Magic',
            'mecha' => 'Mecha',
            'mystery' => 'Mystery',
            'psychological' => 'Psychological',
            'romance' => 'Romance',
            'school' => 'School',
            'sci-fi' => 'Sci-Fi',
            'seinen' => 'Seinen',
            'shounen' => 'Shounen',
            'slice-of-life' => 'Slice of Life',
            'sports' => 'Sports',
            'supernatural' => 'Supernatural',
            'thriller' => 'Thriller',
        ];

        $genres = [];
        foreach ($genresMap as $slug => $name) {
            $genres[] = [
                'name' => $name,
                'slug' => $slug,
                'url' => $this->baseUrl . 'category/' . $slug . '/',
            ];
        }

        return $genres;
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
        $url = $page <= 1 ? "category/{$genreSlug}/" : "category/{$genreSlug}/page/{$page}/";

        try {
            $html = $this->fetchHtml($url);

            return $this->parseAnimeList($html);
        } catch (\Exception $e) {
            \Log::error("Anoboy genre scraper error for {$genreSlug} page {$page}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape anime list from anoboy with status filter support
     *
     * @param int $page Page number (default 1)
     * @param string $status Filter by status (completed, ongoing, or empty)
     * @return array List of anime data
     */
    public function scrapeAnimeList(int $page = 1, string $status = ''): array
    {
        $url = $page <= 1 ? "category/anime/" : "category/anime/page/{$page}/";

        try {
            $html = $this->fetchHtml($url);
            $items = $this->parseAnimeList($html);

            if (filled($status)) {
                $statusLower = strtolower($status);
                $filtered = array_filter($items, function ($item) use ($statusLower) {
                    return strtolower($item['status'] ?? '') === $statusLower;
                });
                return array_values($filtered);
            }

            return $items;
        } catch (\Exception $e) {
            \Log::error('Anoboy scraper error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse anime list from HTML
     * Extracts anime items (.amv cards wrapped in <a>)
     *
     * @param string $html
     * @return array
     */
    protected function parseAnimeList(string $html): array
    {
        $crawler = new Crawler($html);
        $animes = [];
        $seenUrls = [];

        // Select all anime cards with class .amv
        $crawler->filter('.amv')->each(function (Crawler $node) use (&$animes, &$seenUrls) {
            $aNode = $node->closest('a');
            if (!$aNode || $aNode->count() === 0) {
                return;
            }

            $url = $aNode->attr('href') ?? '';
            if (empty($url) || isset($seenUrls[$url])) {
                return;
            }

            // Extract title
            $title = '';
            $titleNode = $node->filter('.amvj h3');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
            } else {
                $title = trim($aNode->attr('title') ?? '');
            }

            if (empty($title)) {
                return;
            }

            // Normalize URL
            if (str_starts_with($url, '/')) {
                $url = rtrim($this->baseUrl, '/') . $url;
            }

            // Extract thumbnail
            $thumbnail = '';
            $imgNode = $node->filter('img');
            if ($imgNode->count() > 0) {
                $thumbnail = $imgNode->attr('src') ?? '';
                if ($thumbnail && str_starts_with($thumbnail, '/')) {
                    $thumbnail = rtrim($this->baseUrl, '/') . $thumbnail;
                }
            }

            // Extract schedule / episode string
            $episode = '';
            $jamupNode = $node->filter('.jamup');
            if ($jamupNode->count() > 0) {
                $episode = trim($jamupNode->text());
            }

            // Status: check if title or labels indicate Completed / Tamat
            $titleLower = strtolower($title);
            $status = (str_contains($titleLower, 'selesai') || str_contains($titleLower, 'tamat'))
                ? 'Completed'
                : 'Ongoing';

            // Clean title (remove trailing Subtitle Indonesia / Sub Indo / anoBoy)
            $cleanTitle = preg_replace('/\s*[-–|]?\s*(?:Subtitle\s+Indonesia|Sub\s+Indo|anoBoy|anoboy)$/i', '', $title);
            $cleanTitle = preg_replace('/anoBoy|anoboy/i', '', $cleanTitle);
            $cleanTitle = trim($cleanTitle);

            $path = parse_url($url, PHP_URL_PATH) ?? '';
            $segments = array_filter(explode('/', trim($path, '/')));
            $slug = end($segments) ?: '';

            $seenUrls[$url] = true;

            $animes[] = [
                'title' => $cleanTitle,
                'slug' => $slug,
                'url' => $url,
                'thumbnail' => $thumbnail,
                'episode' => $episode,
                'type' => str_contains(strtolower($cleanTitle), 'movie') ? 'Movie' : 'Anime',
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
        $path = parse_url($episodeUrl, PHP_URL_PATH);
        if (!$path) {
            return $episodeUrl;
        }

        $segments = array_filter(explode('/', trim($path, '/')));
        $slug = end($segments) ?: '';

        // Remove episode pattern
        $slug = preg_replace('/-episode-\d+(?:-[a-z0-9]+)?$/i', '', $slug);

        if (empty($slug)) {
            return $episodeUrl;
        }

        return rtrim($this->baseUrl, '/') . '/anime/' . $slug . '/';
    }

    /**
     * Scrape anime series detail page for episode list
     *
     * @param string $url Full URL or relative slug URL
     * @return array|null Array with title, episodes list, thumbnail, or null on failure
     */
    public function scrapeAnimeSeriesDetail(string $url): ?array
    {
        try {
            $html = $this->fetchHtml($url);
            $detail = $this->parseAnimeSeriesDetail($html, $url);

            // Fallback: If no episodes found, check if slug can be searched
            if ($detail && empty($detail['episodes'])) {
                $path = parse_url($url, PHP_URL_PATH);
                $segments = array_filter(explode('/', trim($path ?: '', '/')));
                $slug = end($segments) ?: '';
                if ($slug) {
                    $searchQuery = str_replace('-', ' ', $slug);
                    $searchResults = $this->searchAnime($searchQuery);
                    if (!empty($searchResults)) {
                        $candidate = $searchResults[0];
                        if (!empty($candidate['url']) && $candidate['url'] !== $url) {
                            $fallbackHtml = $this->fetchHtml($candidate['url']);
                            $fallbackDetail = $this->parseAnimeSeriesDetail($fallbackHtml, $candidate['url']);
                            if ($fallbackDetail && !empty($fallbackDetail['episodes'])) {
                                return $fallbackDetail;
                            }
                        }
                    }
                }
            }

            return $detail;
        } catch (\Exception $e) {
            \Log::error('Anoboy series detail scraper error for ' . $url . ': ' . $e->getMessage());
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

        // Get title
        $title = '';
        $titleNode = $crawler->filter('h1.entry-title, .entry-title, h1');
        if ($titleNode->count() > 0) {
            $title = trim($titleNode->first()->text());
        } else {
            $titleNode = $crawler->filter('title');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
            }
        }

        // Clean title
        $title = preg_replace('/\s*[-–|]?\s*(?:Subtitle\s+Indonesia|Sub\s+Indo|anoBoy|anoboy).*$/i', '', $title);
        $title = preg_replace('/anoBoy|anoboy/i', '', $title);
        $title = trim($title);

        if (empty($title)) {
            return null;
        }

        // Get thumbnail/poster
        $thumbnail = '';
        $thumbNode = $crawler->filter('.sisi img, .entry-content img, .content img, img[src*="/upload/"], meta[property="og:image"]');
        if ($thumbNode->count() > 0) {
            $first = $thumbNode->first();
            $thumbnail = $first->attr('src') ?? $first->attr('content') ?? '';
            if ($thumbnail && str_starts_with($thumbnail, '/')) {
                $thumbnail = rtrim($this->baseUrl, '/') . $thumbnail;
            }
        }

        // Get description/synopsis
        $description = '';
        $descNode = $crawler->filter('span.entry-content[itemprop="description"], .entry-content, .sisi, .desc');
        if ($descNode->count() > 0 && filled(trim($descNode->first()->text()))) {
            $description = trim($descNode->first()->text());
        } else {
            $metaDesc = $crawler->filter('meta[name="description"], meta[property="og:description"]');
            if ($metaDesc->count() > 0) {
                $description = trim($metaDesc->first()->attr('content') ?? '');
            }
        }
        $description = preg_replace('/anoBoy|anoboy(?:\.[a-z]+)?/i', '', $description);
        $description = preg_replace('/(?:\bdan\s+)?download\s*(?:gratis)?\s*(?:download)?/i', '', $description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);

        // Get genres
        $genres = [];
        $crawler->filter('.sisi a[href*="/category/"], .entry-content a[href*="/category/"]')->each(function (Crawler $node) use (&$genres) {
            $text = trim($node->text());
            if (preg_match('/genre\s+([A-Za-z0-9\-_]+)/i', $text, $m)) {
                $genres[] = ucfirst($m[1]);
            } else {
                $cleaned = preg_replace('/^(?:Nonton\s+Anime\s+bergenre\s+|Nonton\s+anime\s+genre\s+|disini\.?)/i', '', $text);
                $cleaned = trim($cleaned);
                if ($cleaned && !in_array($cleaned, ['Anime', 'Lihat Lebih', 'DonghuaList', 'Movie', 'Tokusatsu'])) {
                    $genres[] = ucfirst($cleaned);
                }
            }
        });
        $genres = array_values(array_unique($genres));

        // Parse episode list
        $episodes = [];
        $seenEpUrls = [];

        // Method 1: Check .singlelink list (standard series posts)
        $epListNodes = $crawler->filter('.singlelink ul.lcp_catlist li a, .singlelink li a, .singlelink a');
        $epListNodes->each(function (Crawler $node) use (&$episodes, &$seenEpUrls) {
            $epUrl = $node->attr('href') ?? '';
            $epTitle = trim($node->text());

            if (empty($epUrl) || str_contains($epTitle, '[Download]') || isset($seenEpUrls[$epUrl])) {
                return;
            }

            if (str_starts_with($epUrl, '/')) {
                $epUrl = rtrim($this->baseUrl, '/') . $epUrl;
            }

            // Extract episode number for compact UI display
            $epNum = '';
            if (preg_match('/Episode\s*([0-9\.]+(?:\s*Selesai|\s*End)?)/i', $epTitle, $m)) {
                $epNum = trim($m[1]);
            } elseif (preg_match('/-episode-([0-9\.]+)/i', $epUrl, $m)) {
                $epNum = trim($m[1]);
            }

            $epPath = parse_url($epUrl, PHP_URL_PATH) ?? '';
            $epSegments = array_filter(explode('/', trim($epPath, '/')));
            $epSlug = end($epSegments) ?: '';

            $seenEpUrls[$epUrl] = true;
            $episodes[] = [
                'episode' => $epNum ?: (string) (count($episodes) + 1),
                'title' => $epTitle,
                'date' => '',
                'url' => $epUrl,
                'slug' => $epSlug,
            ];
        });

        // Method 2: If no .singlelink, check if page contains .amv cards (category/series archive)
        if (empty($episodes)) {
            $crawler->filter('.amv')->each(function (Crawler $node) use (&$episodes, &$seenEpUrls) {
                $a = $node->closest('a');
                if (!$a || $a->count() === 0) return;

                $epUrl = $a->attr('href') ?? '';
                $epTitle = $node->filter('.amvj h3')->count() ? trim($node->filter('.amvj h3')->text()) : trim($a->attr('title') ?? '');

                if (empty($epUrl) || isset($seenEpUrls[$epUrl])) return;

                if (str_starts_with($epUrl, '/')) {
                    $epUrl = rtrim($this->baseUrl, '/') . $epUrl;
                }

                $epNum = '';
                if (preg_match('/Episode\s*([0-9\.]+(?:\s*Selesai|\s*End)?)/i', $epTitle, $m)) {
                    $epNum = trim($m[1]);
                }

                $epPath = parse_url($epUrl, PHP_URL_PATH) ?? '';
                $epSegments = array_filter(explode('/', trim($epPath, '/')));
                $epSlug = end($epSegments) ?: '';

                $seenEpUrls[$epUrl] = true;
                $episodes[] = [
                    'episode' => $epNum ?: (string) (count($episodes) + 1),
                    'title' => $epTitle,
                    'date' => '',
                    'url' => $epUrl,
                    'slug' => $epSlug,
                ];
            });
        }

        // Method 3: If still empty but page has video iframe (single movie or standalone episode)
        if (empty($episodes)) {
            $hasVideo = $crawler->filter('iframe, .vmiror a')->count() > 0;
            if ($hasVideo) {
                $epPath = parse_url($url, PHP_URL_PATH) ?? '';
                $epSegments = array_filter(explode('/', trim($epPath, '/')));
                $epSlug = end($epSegments) ?: '';

                $episodes[] = [
                    'episode' => '1',
                    'title' => $title,
                    'date' => '',
                    'url' => $url,
                    'slug' => $epSlug,
                ];
            }
        }

        // Sort episodes in ascending order (Episode 1 first)
        usort($episodes, function ($a, $b) {
            $numA = (float) filter_var($a['episode'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $numB = (float) filter_var($b['episode'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            return $numA <=> $numB;
        });

        $seriesPath = parse_url($url, PHP_URL_PATH) ?? '';
        $seriesSegments = array_filter(explode('/', trim($seriesPath, '/')));
        $seriesSlug = end($seriesSegments) ?: '';

        return [
            'title' => $title,
            'slug' => $seriesSlug,
            'thumbnail' => $thumbnail,
            'description' => $description,
            'genres' => $genres,
            'episodes' => $episodes,
            'source_url' => $url,
        ];
    }

    /**
     * Scrape episode page for video embed URL
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
            \Log::error('Anoboy episode scraper error for ' . $url . ': ' . $e->getMessage());
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

        // Get title
        $title = '';
        $titleNode = $crawler->filter('h1.entry-title, .entry-title, h1');
        if ($titleNode->count() > 0) {
            $title = trim($titleNode->first()->text());
        } else {
            $titleNode = $crawler->filter('title');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->text());
            }
        }
        $title = preg_replace('/\s*[-–|]?\s*(?:anoBoy|anoboy).*$/i', '', $title);
        $title = preg_replace('/anoBoy|anoboy/i', '', $title);
        $title = trim($title);

        $videoUrl = '';

        // Priority 1: Check existing iframe
        $iframeNode = $crawler->filter('iframe');
        if ($iframeNode->count() > 0) {
            $videoUrl = $iframeNode->first()->attr('src') ?? '';
        }

        // Priority 2: Check .vmiror data-video links
        $mirrors = [];
        $crawler->filter('.vmiror a')->each(function (Crawler $m) use (&$mirrors, &$videoUrl) {
            $v = $m->attr('data-video') ?? '';
            $label = trim($m->text());
            if ($v) {
                if (str_starts_with($v, '/')) {
                    $v = rtrim($this->baseUrl, '/') . $v;
                }
                $mirrors[] = [
                    'label' => $label ?: 'Server ' . (count($mirrors) + 1),
                    'url' => $v,
                ];
            }
        });

        if (empty($videoUrl) && !empty($mirrors)) {
            $videoUrl = $mirrors[0]['url'];
        }

        // Normalize video URL
        if ($videoUrl && str_starts_with($videoUrl, '/')) {
            $videoUrl = rtrim($this->baseUrl, '/') . $videoUrl;
        }

        if (empty($title)) {
            return null;
        }

        // Extract series URL & slug from breadcrumb
        $seriesUrl = '';
        $crawler->filter('.breadcrumb a')->each(function (Crawler $b) use (&$seriesUrl) {
            $href = $b->attr('href') ?? '';
            $text = trim($b->text());
            if (!empty($href) && !in_array(strtolower($text), ['home', 'anime', 'anoboy']) && !str_contains($href, '-episode-')) {
                $seriesUrl = $href;
            }
        });

        if ($seriesUrl && str_starts_with($seriesUrl, '/')) {
            $seriesUrl = rtrim($this->baseUrl, '/') . $seriesUrl;
        }
        $seriesSlug = '';
        if ($seriesUrl) {
            $seriesPath = parse_url($seriesUrl, PHP_URL_PATH) ?? '';
            $seriesSlug = basename(trim($seriesPath, '/'));
        }

        // Episode number
        $epNum = '1';
        if (preg_match('/Episode\s*([0-9\.]+)/i', $title, $m)) {
            $epNum = $m[1];
        }

        // Episode slug
        $epPath = parse_url($url, PHP_URL_PATH) ?? '';
        $epSlug = basename(trim($epPath, '/'));

        return [
            'title' => $title,
            'slug' => $epSlug,
            'video_url' => $videoUrl,
            'mirrors' => $mirrors,
            'source_url' => $url,
            'series_url' => $seriesUrl,
            'series_slug' => $seriesSlug,
            'episode_number' => $epNum,
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
        $url = $page <= 1 ? "category/anime/" : "category/anime/page/{$page}/";

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
     * Check if a page has a "next" button in pagination
     *
     * @param int $currentPage
     * @return bool
     */
    public function hasNextPage(int $currentPage): bool
    {
        try {
            $url = $currentPage <= 1 ? "category/anime/" : "category/anime/page/{$currentPage}/";
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            return $crawler->filter('a.nextpostslink, .wp-pagenavi a.next')->count() > 0;
        } catch (\Exception $e) {
            \Log::error('Anoboy next page check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total pages from wp-pagenavi
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        try {
            $html = $this->fetchHtml('category/anime/');
            $crawler = new Crawler($html);

            $pagesText = $crawler->filter('.wp-pagenavi span.pages')->text('');
            if (preg_match('/of\s+(\d+)/i', $pagesText, $matches)) {
                return (int) $matches[1];
            }

            return 1;
        } catch (\Exception $e) {
            \Log::error('Anoboy pagination error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Scrape all pages sequentially
     */
    public function scrapeAllPages(?callable $onProgress = null, bool $force = false): array
    {
        $currentPage = 1;
        $totalSaved = 0;
        $totalSkipped = 0;
        $pagesScraped = 0;
        $pagesSkipped = 0;
        $totalPages = 1;

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

            if (!$this->hasNextPage($currentPage)) {
                $totalPages = $currentPage;
                break;
            }

            $currentPage++;

            if ($currentPage > 500) {
                \Log::warning('Anoboy scraper reached safety limit of 500 pages');
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
     * Get HTML content from URL
     */
    protected function fetchHtml(string $url): string
    {
        $targetUrl = str_starts_with($url, 'http') ? $url : rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');

        $response = $this->client->get($targetUrl, [
            'headers' => $this->getRandomHeaders(),
        ]);

        $html = (string) $response->getBody();

        // Check if blocked by Cloudflare captcha page
        if (
            str_contains($html, 'Just a moment...') &&
            str_contains($html, 'Enable JavaScript and cookies to continue')
        ) {
            throw new \Exception("Cloudflare anti-bot challenge page detected for: {$targetUrl}");
        }

        return $html;
    }

    /**
     * Get random browser headers
     */
    protected function getRandomHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => $this->baseUrl,
        ];
    }
}
