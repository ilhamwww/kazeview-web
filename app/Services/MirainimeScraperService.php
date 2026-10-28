<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class MirainimeScraperService
{
    protected Client $client;
    protected string $baseUrl = 'https://mirainime.com/';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 15,
            'allow_redirects' => true,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function fetchHtml(string $url): string
    {
        $res = $this->client->get($url);
        return (string) $res->getBody();
    }

    /**
     * Ambil poster (og:image) beberapa anime sekaligus secara paralel.
     * Hasil per slug di-cache 7 hari supaya halaman /anime tetap cepat.
     *
     * @param  string[]  $slugs
     * @return array<string, string> slug => poster URL
     */
    public function fetchPosters(array $slugs): array
    {
        $out = [];
        $todo = [];
        foreach (array_unique(array_filter($slugs)) as $slug) {
            $cached = \Illuminate\Support\Facades\Cache::get('v1_poster_' . $slug);
            if ($cached) {
                $out[$slug] = $cached;
            } else {
                $todo[] = $slug;
            }
        }

        if (empty($todo)) {
            return $out;
        }

        $requests = function () use ($todo) {
            foreach ($todo as $slug) {
                yield $slug => new \GuzzleHttp\Psr7\Request('GET', 'anime/' . rawurlencode($slug));
            }
        };

        $pool = new \GuzzleHttp\Pool($this->client, $requests(), [
            'concurrency' => 6,
            'options' => ['timeout' => 8],
            'fulfilled' => function ($response, $slug) use (&$out) {
                if ($response->getStatusCode() !== 200) {
                    return;
                }
                try {
                    $node = (new Crawler((string) $response->getBody()))->filter('meta[property="og:image"]');
                    $poster = $node->count() ? trim($node->first()->attr('content') ?? '') : '';
                } catch (\Throwable $e) {
                    $poster = '';
                }
                if ($poster !== '') {
                    $out[$slug] = $poster;
                    \Illuminate\Support\Facades\Cache::put('v1_poster_' . $slug, $poster, 604800);
                }
            },
            'rejected' => function ($reason, $slug) {
                \Log::warning("V1 poster fetch gagal ({$slug}): " . (is_object($reason) && method_exists($reason, 'getMessage') ? $reason->getMessage() : 'unknown'));
            },
        ]);
        $pool->promise()->wait();

        return $out;
    }

    /**
     * Get genre list
     */
    public function scrapeGenres(): array
    {
        return [
            ['name' => 'Action', 'slug' => 'action'],
            ['name' => 'Adventure', 'slug' => 'adventure'],
            ['name' => 'Comedy', 'slug' => 'comedy'],
            ['name' => 'Drama', 'slug' => 'drama'],
            ['name' => 'Ecchi', 'slug' => 'ecchi'],
            ['name' => 'Fantasy', 'slug' => 'fantasy'],
            ['name' => 'Harem', 'slug' => 'harem'],
            ['name' => 'Historical', 'slug' => 'historical'],
            ['name' => 'Horror', 'slug' => 'horror'],
            ['name' => 'Isekai', 'slug' => 'isekai'],
            ['name' => 'Martial Arts', 'slug' => 'martial-arts'],
            ['name' => 'Mecha', 'slug' => 'mecha'],
            ['name' => 'Mystery', 'slug' => 'mystery'],
            ['name' => 'Psychological', 'slug' => 'psychological'],
            ['name' => 'Romance', 'slug' => 'romance'],
            ['name' => 'School', 'slug' => 'school'],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi'],
            ['name' => 'Seinen', 'slug' => 'seinen'],
            ['name' => 'Shounen', 'slug' => 'shounen'],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life'],
            ['name' => 'Sports', 'slug' => 'sports'],
            ['name' => 'Supernatural', 'slug' => 'supernatural'],
        ];
    }

    /**
     * Search anime
     */
    public function searchAnime(string $query): array
    {
        if (empty(trim($query))) {
            return [];
        }

        try {
            $url = 'anime-list?q=' . urlencode(trim($query));
            $html = $this->fetchHtml($url);
            return $this->parseAnimeCards($html);
        } catch (\Exception $e) {
            \Log::error('Mirainime search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape anime by genre
     */
    public function scrapeAnimeByGenre(string $genreSlug, int $page = 1): array
    {
        $url = $page <= 1 ? "genre/{$genreSlug}" : "genre/{$genreSlug}?page={$page}";

        try {
            $html = $this->fetchHtml($url);
            return $this->parseAnimeCards($html);
        } catch (\Exception $e) {
            \Log::error("Mirainime genre error {$genreSlug} page {$page}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape anime list
     */
    public function scrapeAnimeList(int $page = 1, string $status = ''): array
    {
        $path = ($status === 'ongoing') ? 'ongoing' : 'anime';
        $url = $page <= 1 ? $path : "{$path}?page={$page}";

        try {
            $html = $this->fetchHtml($url);
            return $this->parseAnimeCards($html);
        } catch (\Exception $e) {
            \Log::error("Mirainime list error page {$page}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape advanced anime directory from /anime-list with rich filters
     */
    public function scrapeAnimeDirectory(array $params = []): array
    {
        $filtered = array_filter($params, fn($v) => $v !== null && $v !== '');
        $query = http_build_query($filtered);
        $url = 'anime-list' . ($query ? '?' . $query : '');

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            $cards = $crawler->filter('#search-grid a, .ctx-card');
            $items = [];
            $seen = [];

            $cards->each(function (Crawler $card) use (&$items, &$seen) {
                $href = $card->attr('href') ?? '';
                if (!str_contains($href, '/anime/')) return;
                $path = parse_url($href, PHP_URL_PATH);
                $slug = basename(trim($path, '/'));
                if (empty($slug) || isset($seen[$slug])) return;
                $seen[$slug] = true;

                // Title
                $title = '';
                $h3 = $card->filter('h3, h2, .font-medium');
                if ($h3->count() > 0) {
                    $title = trim($h3->last()->text());
                }
                $img = $card->filter('img');
                if (empty($title) && $img->count() > 0) {
                    $title = trim($img->attr('alt') ?? '');
                }
                if (empty($title)) {
                    $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
                }

                // Poster
                $poster = '';
                if ($img->count() > 0) {
                    $poster = $img->attr('src') ?: ($img->attr('data-src') ?: '');
                }

                // Status
                $status = 'Available';
                $card->filter('span')->each(function (Crawler $sp) use (&$status) {
                    $t = trim($sp->text());
                    if (in_array(strtolower($t), ['ongoing', 'completed', 'complete', 'tamat', 'upcoming'])) {
                        $status = ucfirst(strtolower($t));
                    }
                });

                // Rating
                $rating = '';
                $card->filter('span')->each(function (Crawler $sp) use (&$rating) {
                    $t = trim($sp->text());
                    if (preg_match('/[⭐★]\s*([\d\.]+)/u', $t, $m)) {
                        $rating = $m[1];
                    }
                });

                // Genres
                $genres = [];
                $card->filter('.text-\[10px\]')->each(function (Crawler $g) use (&$genres) {
                    $t = trim(trim($g->text()), ',');
                    if ($t && !str_starts_with($t, '+') && !in_array($t, $genres)) {
                        $genres[] = $t;
                    }
                });

                // Synopsis snippet
                $synopsis = '';
                $synNode = $card->filter('.line-clamp-3, p.text-xs');
                if ($synNode->count() > 0) {
                    $synopsis = trim($synNode->last()->text());
                    if (str_starts_with($synopsis, 'Sinopsis :')) {
                        $synopsis = trim(substr($synopsis, 10));
                    }
                }

                $items[] = [
                    'title' => $title,
                    'slug' => $slug,
                    'url' => $href,
                    'thumbnail' => $poster,
                    'status' => $status,
                    'rating' => $rating,
                    'genres' => $genres,
                    'synopsis' => $synopsis,
                    'type' => 'Anime',
                ];
            });

            return $items;
        } catch (\Exception $e) {
            \Log::error('Mirainime anime-list error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse cards from list / genre / search pages
     */
    protected function parseAnimeCards(string $html): array
    {
        $crawler = new Crawler($html);
        $animes = [];
        $seen = [];

        // 1. Prioritas utama: ekstrak dari kartu .ctx-card yang terstruktur
        $cards = $crawler->filter('.ctx-card');
        if ($cards->count() > 0) {
            $cards->each(function (Crawler $card) use (&$animes, &$seen) {
                // Link serial anime
                $link = $card->filter('a[href*="/anime/"]');
                if (!$link->count()) return;

                $animeHref = '';
                $link->each(function (Crawler $a) use (&$animeHref) {
                    $h = $a->attr('href') ?? '';
                    if (!str_contains($h, '/episode/') && !str_contains($h, '/download') && !preg_match('#/anime/?$#', $h)) {
                        if (empty($animeHref)) $animeHref = $h;
                    }
                });
                if (empty($animeHref)) {
                    $animeHref = $link->first()->attr('href') ?? '';
                }

                $path = parse_url($animeHref, PHP_URL_PATH) ?? '';
                $slug = preg_replace('#^/?anime/#', '', trim($path, '/'));
                if (str_contains($slug, '/episode/')) {
                    $slug = explode('/episode/', $slug)[0];
                }
                if (empty($slug) || isset($seen[$slug])) return;

                // Thumbnail poster
                $thumbnail = '';
                $imgNode = $card->filter('img');
                if ($imgNode->count()) {
                    $thumbnail = $imgNode->first()->attr('src') ?: $imgNode->first()->attr('data-src') ?: '';
                    if (!empty($thumbnail) && str_starts_with($thumbnail, '/')) {
                        $thumbnail = rtrim($this->baseUrl, '/') . $thumbnail;
                    }
                }

                // Judul serial
                $title = '';
                $titleNode = $card->filter('h2 a, h3 a, h2, h3, .title, p.font-semibold, p.text-sm, p.line-clamp-2');
                if ($titleNode->count()) {
                    $title = trim($titleNode->first()->text());
                } elseif ($imgNode->count() && !empty($imgNode->first()->attr('alt'))) {
                    $title = trim($imgNode->first()->attr('alt'));
                }

                $title = preg_replace('/\s*[-–|]?\s*(?:Sub\s*Indo.*|Subtitle\s*Indonesia.*)$/i', '', $title);
                $title = preg_replace('/^(?:Tonton|Watch|Nonton)\s*/i', '', $title);
                $title = trim($title);

                // Jika judul kosong atau cuma teks rating (misal "⭐ 8.7")
                if (empty($title) || preg_match('/^[⭐★\d\.\s]+$/u', $title)) {
                    if ($imgNode->count() && !empty($imgNode->first()->attr('alt'))) {
                        $title = trim($imgNode->first()->attr('alt'));
                    } else {
                        $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
                    }
                }

                $seen[$slug] = true;
                $animes[] = [
                    'title' => $title,
                    'slug' => $slug,
                    'url' => $animeHref,
                    'thumbnail' => $thumbnail,
                    'type' => 'Anime',
                    'status' => 'Available',
                ];
            });
        }

        // 2. Fallback jika halaman tidak memiliki .ctx-card
        if (empty($animes)) {
            $crawler->filter('a[href*="/anime/"]')->each(function (Crawler $a) use (&$animes, &$seen) {
                $href = $a->attr('href') ?? '';
                if (empty($href) || str_contains($href, '/download') || str_contains($href, '/episode/') || preg_match('#/anime/?$#', $href)) {
                    return;
                }

                $path = parse_url($href, PHP_URL_PATH) ?? '';
                $slug = basename(trim($path, '/'));
                if (empty($slug) || isset($seen[$slug])) {
                    return;
                }

                $imgNode = $a->filter('img');
                $thumbnail = '';
                if ($imgNode->count() > 0) {
                    $thumbnail = $imgNode->first()->attr('src') ?: $imgNode->first()->attr('data-src') ?: '';
                    if (!empty($thumbnail) && str_starts_with($thumbnail, '/')) {
                        $thumbnail = rtrim($this->baseUrl, '/') . $thumbnail;
                    }
                }

                $title = '';
                $titleNode = $a->filter('h2, h3, h4, .title, span.font-semibold, span.font-bold');
                if ($titleNode->count() > 0) {
                    $title = trim($titleNode->first()->text());
                } elseif ($imgNode->count() && !empty($imgNode->attr('alt'))) {
                    $title = trim($imgNode->attr('alt'));
                } else {
                    $t = trim($a->text());
                    if (!empty($t) && !preg_match('/^[⭐★\d\.\s]+$/u', $t)) {
                        $title = $t;
                    } else {
                        $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
                    }
                }

                $title = preg_replace('/\s*[-–|]?\s*(?:Sub\s*Indo.*|Subtitle\s*Indonesia.*)$/i', '', $title);
                $title = preg_replace('/^(?:Tonton|Watch|Nonton)\s*/i', '', $title);
                $title = trim($title);

                if (empty($title)) {
                    $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
                }

                $seen[$slug] = true;
                $animes[] = [
                    'title' => $title,
                    'slug' => $slug,
                    'url' => $href,
                    'thumbnail' => $thumbnail,
                    'type' => 'Anime',
                    'status' => 'Available',
                ];
            });
        }

        return $animes;
    }

    /**
     * Scrape homepage sections: hero, latest episodes, trending, current season, top anime.
     */
    public function scrapeHomepage(): array
    {
        $empty = ['hero' => [], 'latest' => [], 'trending' => [], 'season' => [], 'season_title' => '', 'top' => []];

        try {
            $html = $this->fetchHtml('/');
            if ($html === '') {
                return $empty;
            }
            $crawler = new Crawler($html);
        } catch (\Exception $e) {
            \Log::error('V1 homepage error: ' . $e->getMessage());
            return $empty;
        }

        $text = fn (Crawler $c, string $sel) => $c->filter($sel)->count() ? trim(preg_replace('/\s+/', ' ', $c->filter($sel)->first()->text(''))) : '';
        $attr = fn (Crawler $c, string $sel, string $a) => $c->filter($sel)->count() ? (string) $c->filter($sel)->first()->attr($a) : '';
        $slugOf = function (string $href): string {
            $path = trim((string) parse_url($href, PHP_URL_PATH), '/');
            // /anime/{slug} or /anime/{slug}/episode/{num}
            if (preg_match('#^anime/([^/]+)#', $path, $m)) {
                return $m[1];
            }
            return '';
        };
        $clean = fn (string $s) => trim(preg_replace('/\b(mirainime|otakudesu|anoboy)\b/i', '', $s));

        // Hero slider
        $hero = [];
        $crawler->filter('section[x-data^="heroSlider"] div[x-show^="active ==="]')->each(function (Crawler $s) use (&$hero, $text, $attr, $slugOf, $clean) {
            $href = $attr($s, 'a[href*="/anime/"]', 'href');
            $slug = $slugOf($href);
            if ($slug === '') {
                return;
            }
            $meta = [];
            $s->filter('span.text-xs')->each(function (Crawler $sp) use (&$meta) {
                $t = trim($sp->text(''));
                if ($t !== '' && $t !== '·') {
                    $meta[] = $t;
                }
            });
            $genres = [];
            $s->filter('span.rounded-full')->each(function (Crawler $g) use (&$genres) {
                $genres[] = trim($g->text(''));
            });
            $hero[] = [
                'title' => $text($s, 'h1'),
                'alt_title' => $text($s, 'h1 + p'),
                'slug' => $slug,
                'poster' => $attr($s, 'img', 'src'),
                'genres' => array_slice($genres, 0, 3),
                'meta' => array_values(array_diff($meta, $genres)),
                'synopsis' => $clean($text($s, 'p.line-clamp-2')),
            ];
        });

        // Latest episodes
        $latest = [];
        $crawler->filter('#welcome-episodes-grid > a')->each(function (Crawler $a) use (&$latest, $text, $attr, $slugOf) {
            $href = (string) $a->attr('href');
            if (!preg_match('#/episode/([^/?]+)#', $href, $m)) {
                return;
            }
            $meta = [];
            $a->filter('div.text-\[11px\] span')->each(function (Crawler $sp) use (&$meta) {
                $t = trim($sp->text(''));
                if ($t !== '' && $t !== '·') {
                    $meta[] = $t;
                }
            });
            $latest[] = [
                'title' => $text($a, 'h3'),
                'series' => $text($a, 'h3 + p'),
                'slug' => $slugOf($href),
                'episode' => $m[1],
                'thumbnail' => $attr($a, 'img.hls-preview-poster', 'src') ?: $attr($a, 'img', 'src'),
                'poster' => $attr($a, 'div.rounded-full img', 'src'),
                'type' => $meta[0] ?? '',
                'rating' => isset($meta[1]) ? trim(str_replace('★', '', $meta[1])) : '',
                'ago' => end($meta) ?: '',
            ];
        });

        // Trending
        $trending = [];
        $crawler->filter('#trending-scroll > div')->each(function (Crawler $d) use (&$trending, $attr, $slugOf) {
            $slug = $slugOf($attr($d, 'a', 'href'));
            if ($slug === '') {
                return;
            }
            $trending[] = [
                'rank' => trim($d->filter('span')->first()->text('')),
                'title' => $attr($d, 'img', 'alt'),
                'slug' => $slug,
                'poster' => $attr($d, 'img', 'src'),
            ];
        });

        // Current season grid
        $season = [];
        $seasonTitle = '';
        if ($crawler->filter('#ongoing-season-grid')->count()) {
            $seasonTitle = trim($crawler->filterXPath('//div[@id="ongoing-season-grid"]/preceding-sibling::div[1]//h2')->text(''));
            $crawler->filter('#ongoing-season-grid > a')->each(function (Crawler $a) use (&$season, $text, $attr, $slugOf) {
                $slug = $slugOf((string) $a->attr('href'));
                if ($slug === '') {
                    return;
                }
                $season[] = [
                    'title' => $attr($a, 'img', 'alt'),
                    'slug' => $slug,
                    'poster' => $attr($a, 'img', 'src'),
                    'episodes' => $text($a, 'div.top-2.right-2'),
                    'type' => $text($a, 'div.bottom-2.left-2'),
                ];
            });
        }

        // Top anime (all-time)
        $top = [];
        $crawler->filterXPath('//h2[contains(., "Top Anime") and not(contains(., "Airing"))]/following-sibling::div[1]/a')->each(function (Crawler $a) use (&$top, $text, $attr, $slugOf) {
            $slug = $slugOf((string) $a->attr('href'));
            if ($slug === '') {
                return;
            }
            $top[] = [
                'rank' => $text($a, 'span'),
                'title' => $text($a, 'h3'),
                'slug' => $slug,
                'poster' => $attr($a, 'img', 'src'),
                'rating' => trim(str_replace('★', '', $text($a, 'div span.text-yellow-400'))),
            ];
        });

        return [
            'hero' => array_slice($hero, 0, 6),
            'latest' => $latest,
            'trending' => array_slice($trending, 0, 10),
            'season' => $season,
            'season_title' => $seasonTitle,
            'top' => array_slice($top, 0, 10),
        ];
    }

    /**
     * Recently updated episodes from the RSS feed (newest first, grouped per series).
     */
    public function scrapeRecentFeed(int $limit = 20): array
    {
        try {
            $xml = @simplexml_load_string($this->fetchHtml('rss.xml'));
            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }
        } catch (\Exception $e) {
            \Log::error('V1 feed error: ' . $e->getMessage());
            return [];
        }

        $items = [];
        foreach ($xml->channel->item as $it) {
            $link = (string) $it->link;
            if (!preg_match('#/anime/([^/]+)/episode/([^/?]+)#', $link, $m)) {
                continue;
            }
            $slug = $m[1];
            // Satu baris per serial: ambil episode paling baru saja
            if (isset($items[$slug])) {
                continue;
            }
            $title = (string) $it->title;
            $series = trim(preg_replace('/\s*-\s*Episode\s+\S+$/i', '', $title));
            $ts = strtotime((string) $it->pubDate) ?: null;
            $items[$slug] = [
                'series' => $series,
                'slug' => $slug,
                'episode' => $m[2],
                'timestamp' => $ts,
                'ago' => $ts ? \Carbon\Carbon::createFromTimestamp($ts)->locale('id')->diffForHumans() : '',
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return array_values($items);
    }

    /**
     * Scrape anime series detail & comprehensive metadata
     */
    public function scrapeAnimeSeriesDetail(string $slug): ?array
    {
        $url = 'anime/' . trim($slug, '/');

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            // 1. JSON-LD Schema
            $jsonLd = [];
            $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$jsonLd) {
                $raw = trim($node->text());
                $decoded = json_decode($raw, true);
                if (!empty($decoded['@graph'])) {
                    foreach ($decoded['@graph'] as $item) {
                        if (in_array($item['@type'] ?? '', ['TVSeries', 'Movie'])) {
                            $jsonLd = $item;
                            break;
                        }
                    }
                }
            });

            // 2. Title
            $title = $jsonLd['name'] ?? '';
            if (empty($title)) {
                $ogTitle = $crawler->filter('meta[property="og:title"]');
                if ($ogTitle->count() > 0) {
                    $title = trim($ogTitle->first()->attr('content') ?? '');
                } else {
                    $titleNode = $crawler->filter('h1, h2.text-xl, h2.text-2xl, title');
                    if ($titleNode->count() > 0) {
                        $title = trim($titleNode->first()->text());
                    }
                }
                $title = preg_replace('/\s*[-–|:]?\s*(?:Episode\s*\d+.*|Sub\s*Indo.*|Mirainime.*)$/i', '', $title);
                $title = trim($title);
            }
            if (empty($title)) {
                $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
            }

            // 3. Japanese (Kanji) & English Titles
            $japaneseTitle = $jsonLd['alternateName'][0] ?? '';
            $englishTitle = '';
            $crawler->filter('p.text-xs.text-gray-400')->each(function (Crawler $p) use (&$japaneseTitle) {
                $t = trim($p->text());
                if (!empty($t) && preg_match('/[\x{4E00}-\x{9FAF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $t)) {
                    $japaneseTitle = $t;
                }
            });
            $crawler->filter('p.text-xs.text-gray-500.italic')->each(function (Crawler $p) use (&$englishTitle) {
                $parts = explode(',', trim($p->text()));
                $englishTitle = trim($parts[0] ?? '');
            });
            if (empty($englishTitle) && !empty($jsonLd['alternateName'][1])) {
                $parts = explode(',', $jsonLd['alternateName'][1]);
                $englishTitle = trim($parts[0]);
            }

            // 4. Score / Rating
            $score = '';
            $crawler->filter('span')->each(function (Crawler $s) use (&$score) {
                $t = trim($s->text());
                if (preg_match('/[⭐★]\s*([\d\.]+)/u', $t, $m)) {
                    if (empty($score)) $score = $m[1];
                }
            });

            // 5. Studio, Season, Duration, Status
            $studio = $jsonLd['author'][0]['name'] ?? '';
            $season = '';
            $duration = !empty($jsonLd['timeRequired']) ? preg_replace('/[^0-9]/', '', $jsonLd['timeRequired']) : '';
            if ($duration) $duration .= ' Menit';

            $status = 'Ongoing';
            $crawler->filter('span')->each(function (Crawler $s) use (&$status) {
                $t = strtolower(trim($s->text()));
                if ($t === 'ongoing') $status = 'Ongoing';
                elseif ($t === 'completed' || $t === 'tamat') $status = 'Completed';
            });

            $crawler->filter('.grid div')->each(function (Crawler $d) use (&$studio, &$season, &$duration) {
                $label = trim($d->filter('span')->count() ? $d->filter('span')->first()->text() : '');
                $val = trim($d->filter('p')->count() ? $d->filter('p')->first()->text() : '');
                if (stripos($label, 'Studio') !== false && empty($studio)) $studio = $val;
                if (stripos($label, 'Season') !== false && empty($season)) $season = $val;
                if (stripos($label, 'Duration') !== false && (empty($duration) || $duration === 'Menit')) $duration = $val;
            });

            // 6. Next Episode Schedule & Airing Day
            $nextEpisodeSchedule = '';
            $broadcastDay = '';
            $crawler->filter('div')->each(function (Crawler $d) use (&$nextEpisodeSchedule) {
                $h = $d->filter('.font-semibold');
                if ($h->count() && stripos($h->first()->text(), 'Episode Berikutnya') !== false) {
                    $sub = $d->filter('.text-sm, p');
                    if ($sub->count()) $nextEpisodeSchedule = trim(preg_replace('/\s+/', ' ', $sub->first()->text()));
                }
            });

            if ($nextEpisodeSchedule) {
                if (preg_match('/(\d+)\s+([A-Za-z]+)\s+(\d{4})/i', $nextEpisodeSchedule, $dm)) {
                    $months = [
                        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
                        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
                        'january' => 1, 'february' => 2, 'march' => 3, 'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8, 'october' => 10, 'december' => 12
                    ];
                    $mNum = $months[strtolower($dm[2])] ?? 1;
                    try {
                        $dt = \Carbon\Carbon::create((int)$dm[3], $mNum, (int)$dm[1]);
                        $broadcastDay = 'Setiap Hari ' . $dt->locale('id')->isoFormat('dddd');
                    } catch (\Throwable $e) {}
                }
            }

            // 7. Genres (Meta tags priority for clean series genres)
            $genres = [];
            $crawler->filter('meta[property="article:tag"]')->each(function (Crawler $node) use (&$genres) {
                $t = trim($node->attr('content') ?? '');
                if ($t && !in_array($t, $genres)) $genres[] = $t;
            });
            if (empty($genres) && !empty($jsonLd['genre'])) {
                $genres = (array) $jsonLd['genre'];
            }

            // 8. Description / Synopsis (Indonesian)
            $description = '';
            $synNode = $crawler->filter('div[itemprop="description"], .text-gray-300.text-sm.leading-relaxed');
            if ($synNode->count()) {
                $description = trim(preg_replace('/\s+/', ' ', $synNode->first()->text()));
                $description = preg_replace('/^.*?-\s*/', '', $description);
            } elseif (!empty($jsonLd['description'])) {
                $description = trim($jsonLd['description']);
            } else {
                $ogDesc = $crawler->filter('meta[property="og:description"]');
                if ($ogDesc->count() > 0) {
                    $description = trim($ogDesc->first()->attr('content') ?? '');
                    $description = preg_replace('/Mirainime(?:\.[a-z]+)?/i', '', $description);
                    $description = preg_replace('/(?:\bdan\s+)?download\s*(?:gratis)?/i', '', $description);
                    $description = preg_replace('/\s+/', ' ', $description);
                    $description = trim($description);
                }
            }

            // 9. Thumbnail / Poster (High-Res WebP)
            $thumbnail = '';
            $ogImage = $crawler->filter('meta[property="og:image"]');
            if ($ogImage->count() > 0) {
                $thumbnail = trim($ogImage->first()->attr('content') ?? '');
            }
            if (empty($thumbnail) && !empty($jsonLd['image'][0])) {
                $thumbnail = $jsonLd['image'][0];
            }

            // 10. Episodes list
            $episodes = [];
            $seenEp = [];

            $crawler->filter('a[href*="/episode/"]')->each(function (Crawler $a) use (&$episodes, &$seenEp, $slug) {
                $href = $a->attr('href') ?? '';
                if (empty($href) || !str_contains($href, $slug)) {
                    return;
                }

                $parts = explode('/episode/', trim($href, '/'));
                $epNum = end($parts) ?: '';
                if (empty($epNum) || isset($seenEp[$epNum])) {
                    return;
                }

                $seenEp[$epNum] = true;
                $episodes[] = [
                    'episode' => $epNum,
                    'title' => "Episode {$epNum}",
                    'url' => $href,
                ];
            });

            // Sort episodes ascending (Episode 1 first)
            usort($episodes, function ($a, $b) {
                return (int)$a['episode'] <=> (int)$b['episode'];
            });

            // 11. Relations / Franchise (Sequel, Prequel, Side Story, etc.)
            $relations = [];
            $seenRel = [];
            $crawler->filter('h2, h3, h4')->each(function (Crawler $h) use (&$relations, &$seenRel) {
                if (stripos($h->text(), 'Anime Terkait') !== false || stripos($h->text(), 'Related Anime') !== false) {
                    $parent = $h->closest('div, section');
                    if (!$parent) return;

                    $parent->filter('a[href*="/anime/"]')->each(function (Crawler $card) use (&$relations, &$seenRel) {
                        $href = $card->attr('href') ?? '';
                        $path = parse_url($href, PHP_URL_PATH);
                        $relSlug = basename(trim($path, '/'));
                        if (empty($relSlug) || isset($seenRel[$relSlug])) return;
                        $seenRel[$relSlug] = true;

                        $relTitle = '';
                        $h3 = $card->filter('h3, h4, .font-medium, .text-sm');
                        if ($h3->count() > 0) {
                            $relTitle = trim($h3->last()->text());
                        }
                        $img = $card->filter('img');
                        if (empty($relTitle) && $img->count() > 0) {
                            $relTitle = trim($img->attr('alt') ?? '');
                        }
                        if (empty($relTitle)) {
                            $relTitle = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $relSlug));
                        }

                        $relPoster = '';
                        if ($img->count() > 0) {
                            $relPoster = $img->attr('src') ?: ($img->attr('data-src') ?: '');
                        }

                        $relationType = 'RELATED';
                        $relRating = '';
                        $card->filter('.absolute span, span')->each(function (Crawler $sp) use (&$relationType, &$relRating) {
                            $t = trim($sp->text());
                            if (in_array(strtoupper($t), ['PREQUEL', 'SEQUEL', 'SIDE STORY', 'ALTERNATIVE', 'PARENT STORY', 'SUMMARY', 'SPIN-OFF', 'CHARACTER', 'OTHER'])) {
                                $relationType = strtoupper($t);
                            } elseif (preg_match('/[⭐★]\s*([\d\.]+)/u', $t, $m)) {
                                $relRating = $m[1];
                            }
                        });

                        $relations[] = [
                            'relation' => $relationType,
                            'title' => $relTitle,
                            'slug' => $relSlug,
                            'thumbnail' => $relPoster,
                            'rating' => $relRating,
                        ];
                    });
                }
            });

            return [
                'title' => $title,
                'slug' => $slug,
                'japanese_title' => $japaneseTitle,
                'english_title' => $englishTitle,
                'score' => $score,
                'status' => $status,
                'studio' => $studio,
                'season' => $season,
                'duration' => $duration,
                'broadcast_day' => $broadcastDay,
                'next_episode_schedule' => $nextEpisodeSchedule,
                'genres' => $genres,
                'thumbnail' => $thumbnail,
                'banner' => $thumbnail,
                'description' => $description,
                'episodes' => $episodes,
                'relations' => $relations,
                'source_url' => rtrim($this->baseUrl, '/') . '/' . $url,
            ];
        } catch (\Exception $e) {
            \Log::error("Mirainime series detail error {$slug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Scrape episode video detail
     */
    public function scrapeEpisodeDetail(string $animeSlug, string $episodeNum): ?array
    {
        $url = "anime/{$animeSlug}/episode/{$episodeNum}";

        try {
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            // Title
            $title = '';
            $ogTitle = $crawler->filter('meta[property="og:title"]');
            if ($ogTitle->count() > 0) {
                $title = trim($ogTitle->first()->attr('content') ?? '');
            } else {
                $titleNode = $crawler->filter('h1, title');
                if ($titleNode->count() > 0) {
                    $title = trim($titleNode->first()->text());
                }
            }

            // Clean title
            $title = preg_replace('/\s*[-–|]?\s*Mirainime.*$/i', '', $title);
            $title = trim($title);

            // Extract direct video MP4 URL from og:video
            $videoUrl = '';
            $ogVideo = $crawler->filter('meta[property="og:video"], meta[property="og:video:url"], meta[property="og:video:secure_url"]');
            if ($ogVideo->count() > 0) {
                $videoUrl = trim($ogVideo->first()->attr('content') ?? '');
                $videoUrl = htmlspecialchars_decode($videoUrl);
            }

            if (empty($title)) {
                $title = ucwords(str_replace('-', ' ', $animeSlug)) . " Episode {$episodeNum}";
            }

            return [
                'title' => $title,
                'episode_number' => $episodeNum,
                'video_url' => $videoUrl,
                'anime_slug' => $animeSlug,
                'source_url' => rtrim($this->baseUrl, '/') . '/' . $url,
            ];
        } catch (\Exception $e) {
            \Log::error("Mirainime episode detail error {$animeSlug} ep {$episodeNum}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Scrape weekly release schedule from Mirainime (https://mirainime.com/jadwal-tayang-anime)
     */
    public function scrapeWeeklySchedule(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('mirainime_weekly_schedule_v1', 21600, function () {
            try {
                $html = $this->fetchHtml('jadwal-tayang-anime');
                $crawler = new Crawler($html);

                $dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                $byDay = [];
                foreach ($dayOrder as $d) {
                    $byDay[$d] = [];
                }

                $crawler->filter('#schedule-scroll > div')->each(function (Crawler $col) use (&$byDay) {
                    $dayNameNode = $col->filter('.text-base, .text-lg');
                    if (!$dayNameNode->count()) return;
                    $dayName = trim($dayNameNode->first()->text());
                    if (!isset($byDay[$dayName])) return;

                    $col->filter('a.ctx-card')->each(function (Crawler $card) use (&$byDay, $dayName) {
                        $href = $card->attr('href') ?? '';
                        $slug = preg_replace('#^/?anime/#', '', trim($href, '/'));
                        if (empty($slug)) return;

                        $titleNode = $card->filter('h3, h4, .font-medium, .text-sm')->first();
                        $title = $titleNode->count() ? trim($titleNode->text()) : ucwords(str_replace('-', ' ', $slug));

                        $imgNode = $card->filter('img')->first();
                        $poster = $imgNode->count() ? ($imgNode->attr('src') ?: $imgNode->attr('data-src') ?: '') : '';

                        // Jam rilis (misal 19:30 WIB)
                        $parent = $card->closest('div');
                        $timeNode = $parent ? $parent->filter('span.font-bold, .text-\[10px\]') : null;
                        $time = ($timeNode && $timeNode->count()) ? trim($timeNode->text()) : '';

                        // Episode jika ada
                        $episode = '';
                        $card->filter('.text-xs, .text-gray-400, .text-purple-400')->each(function (Crawler $en) use (&$episode) {
                            $t = trim($en->text());
                            if (preg_match('/ep(isode)?\s*\d+/i', $t)) {
                                $episode = $t;
                            }
                        });

                        // Hindari duplikasi jika Mirainime menampilkan 2 kolom untuk hari yang sama
                        $alreadyExists = collect($byDay[$dayName])->contains(fn ($x) => $x['slug'] === $slug);
                        if ($alreadyExists) return;

                        $detailUrl = \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $slug], panel: 'streaming');

                        $byDay[$dayName][] = [
                            'slug' => $slug,
                            'title' => $title,
                            'poster' => $poster,
                            'time' => $time,
                            'episode' => $time ?: ($episode ?: 'Tayang'),
                            'detail_url' => $detailUrl,
                        ];
                    });
                });

                return $byDay;
            } catch (\Throwable $e) {
                \Log::error('Mirainime scrapeWeeklySchedule error: ' . $e->getMessage());
                return [];
            }
        });
    }
}
