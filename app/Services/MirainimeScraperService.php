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
            $crawler = new Crawler($html);

            $animes = [];
            $seen = [];

            $crawler->filter('a[href*="/anime/"]')->each(function (Crawler $a) use (&$animes, &$seen) {
                $href = $a->attr('href') ?? '';
                if (empty($href) || str_contains($href, '/download') || str_contains($href, '/episode/') || $href === 'https://mirainime.com/anime') {
                    return;
                }

                $path = parse_url($href, PHP_URL_PATH) ?? '';
                $slug = basename(trim($path, '/'));
                if (empty($slug) || isset($seen[$slug])) {
                    return;
                }

                $title = trim($a->text());
                $title = preg_replace('/\s*[-–|]?\s*(?:Sub\s*Indo.*|Subtitle\s*Indonesia.*)$/i', '', $title);
                $title = trim($title);

                if (empty($title)) {
                    $title = ucwords(str_replace('-', ' ', $slug));
                }

                $imgNode = $a->filter('img');
                $thumbnail = '';
                if ($imgNode->count() > 0) {
                    $thumbnail = $imgNode->attr('src') ?? $imgNode->attr('data-src') ?? '';
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

            return $animes;
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
     * Parse cards from list / genre pages
     */
    protected function parseAnimeCards(string $html): array
    {
        $crawler = new Crawler($html);
        $animes = [];
        $seen = [];

        $crawler->filter('a[href*="/anime/"]')->each(function (Crawler $a) use (&$animes, &$seen) {
            $href = $a->attr('href') ?? '';
            if (empty($href) || str_contains($href, '/download') || str_contains($href, '/episode/') || $href === 'https://mirainime.com/anime') {
                return;
            }

            $path = parse_url($href, PHP_URL_PATH) ?? '';
            $slug = basename(trim($path, '/'));
            if (empty($slug) || isset($seen[$slug])) {
                return;
            }

            // Extract thumbnail
            $thumbnail = '';
            $imgNode = $a->filter('img');
            if ($imgNode->count() > 0) {
                $thumbnail = $imgNode->attr('src') ?? $imgNode->attr('data-src') ?? '';
            }

            // Extract title
            $title = '';
            $titleNode = $a->filter('h2, h3, h4, .title, span.font-semibold, span.font-bold');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->first()->text());
            } else {
                $title = trim($a->text());
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
                'status' => 'Ongoing',
            ];
        });

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
     * Scrape anime series detail
     */
    public function scrapeAnimeSeriesDetail(string $slug): ?array
    {
        $url = 'anime/' . trim($slug, '/');

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
            $title = preg_replace('/\s*[-–|:]?\s*(?:Episode\s*\d+.*|Sub\s*Indo.*|Mirainime.*)$/i', '', $title);
            $title = trim($title);

            if (empty($title)) {
                $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));
            }

            // Thumbnail
            $thumbnail = '';
            $ogImage = $crawler->filter('meta[property="og:image"]');
            if ($ogImage->count() > 0) {
                $thumbnail = trim($ogImage->first()->attr('content') ?? '');
            }

            // Description / Synopsis
            $description = '';
            $ogDesc = $crawler->filter('meta[property="og:description"]');
            if ($ogDesc->count() > 0) {
                $description = trim($ogDesc->first()->attr('content') ?? '');
                $description = preg_replace('/Mirainime(?:\.[a-z]+)?/i', '', $description);
                $description = preg_replace('/(?:\bdan\s+)?download\s*(?:gratis)?/i', '', $description);
                $description = preg_replace('/\s+/', ' ', $description);
                $description = trim($description);
            }

            // Genres
            $genres = [];
            $crawler->filter('a[href*="/genre/"]')->each(function (Crawler $a) use (&$genres) {
                $t = trim($a->text());
                if ($t && !in_array($t, $genres)) {
                    $genres[] = $t;
                }
            });
            $genres = array_slice($genres, 0, 6);

            // Episodes list
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

            return [
                'title' => $title,
                'slug' => $slug,
                'thumbnail' => $thumbnail,
                'description' => $description,
                'genres' => $genres,
                'episodes' => $episodes,
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
}
