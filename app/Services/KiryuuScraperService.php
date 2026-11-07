<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class KiryuuScraperService
{
    protected string $baseUrl = 'https://v7.kiryuu.to';
    protected string $cfHelperPath;

    public function __construct()
    {
        $this->cfHelperPath = app_path('Services/fetch_cf.py');
    }

    /**
     * Ambil konten HTML menggunakan bypass TLS Chrome (fetch_cf.py).
     */
    public function fetchHtml(string $url, int $cacheSeconds = 1800): string
    {
        $fullUrl = str_starts_with($url, 'http') ? $url : rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
        $cacheKey = 'kiryuu_html_' . md5($fullUrl);

        $cached = Cache::get($cacheKey);
        if ($cached && is_string($cached) && strlen($cached) > 200) {
            return $cached;
        }

        try {
            $cmd = escapeshellcmd($this->cfHelperPath) . ' ' . escapeshellarg($fullUrl);
            $output = shell_exec($cmd);

            if (!empty($output) && strlen($output) > 200 && !str_contains($output, 'Attention Required!') && !str_contains($output, 'Just a moment...')) {
                Cache::put($cacheKey, $output, $cacheSeconds);
                return $output;
            }
        } catch (\Throwable $e) {
            Log::error('Kiryuu fetchHtml error: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Ambil komik populer dari Kiryuu.
     */
    public function getPopular(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $path = 'manga/';
        if ($page > 1) {
            $path .= "page/{$page}/";
        }
        $params = ['order' => 'popular'];
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['the_type'] = strtolower($format);
        }

        $url = $path . '?' . http_build_query($params);
        $html = $this->fetchHtml($url, 7200);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Ambil rilis komik terbaru dari Kiryuu.
     */
    public function getLatest(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $path = 'manga/';
        if ($page > 1) {
            $path .= "page/{$page}/";
        }
        $params = ['order' => 'update'];
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['the_type'] = strtolower($format);
        }

        $url = $path . '?' . http_build_query($params);
        $html = $this->fetchHtml($url, 1800);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Cari komik di Kiryuu.
     */
    public function search(string $query, int $page = 1, int $pageSize = 24): array
    {
        $path = 'manga/';
        if ($page > 1) {
            $path .= "page/{$page}/";
        }
        $url = $path . '?' . http_build_query(['search_term' => $query]);
        $html = $this->fetchHtml($url, 3600);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Parse HTML halaman katalog / list komik Kiryuu.
     */
    protected function parseListingHtml(string $html, int $page): array
    {
        if (empty($html)) {
            return [
                'data' => [],
                'page' => $page,
                'total_page' => 1,
                'total_record' => 0,
                'has_next' => false,
            ];
        }

        $crawler = new Crawler($html);
        $items = [];

        $crawler->filter("a[href*='/manga/']")->each(function (Crawler $node) use (&$items) {
            $href = $node->attr('href') ?? '';
            if (preg_match('#/manga/([^/]+)/?$#', $href, $m)) {
                $slug = $m[1];
                if ($slug === 'page' || isset($items[$slug])) {
                    return;
                }

                $parent = $node->closest('div');
                $card = $parent ? $parent->closest('.overflow-hidden, .group, .flex') : null;

                $imgNode = $node->filter('img')->first();
                $rawCover = $imgNode->count() ? ($imgNode->attr('src') ?: $imgNode->attr('data-src') ?: '') : '';
                if (empty($rawCover) || str_contains($rawCover, '.svg') || str_contains($rawCover, 'logo')) {
                    return;
                }

                // Format (Manga / Manhwa / Manhua)
                $format = 'Manga';
                if ($node->filter('img[alt="manhwa"]')->count() || ($card && $card->filter('img[alt="manhwa"]')->count())) {
                    $format = 'Manhwa';
                } elseif ($node->filter('img[alt="manhua"]')->count() || ($card && $card->filter('img[alt="manhua"]')->count())) {
                    $format = 'Manhua';
                }

                // Title
                $titleNode = $node->filter('h1, h2, h3, h4')->first();
                if ($titleNode->count()) {
                    $title = trim($titleNode->text());
                } else {
                    $title = $imgNode->attr('alt') ?: ucwords(str_replace('-', ' ', $slug));
                }

                // Rating
                $rating = null;
                $scoreNode = $card ? $card->filter('.numscore')->first() : null;
                if ($scoreNode && $scoreNode->count()) {
                    $val = (float) trim($scoreNode->text());
                    if ($val > 0) $rating = number_format($val, 1);
                }

                // Latest chapter
                $chapter = null;
                if ($card) {
                    $chNode = $card->filter('a[href*="/chapter-"]')->first();
                    if ($chNode->count()) {
                        $chText = trim($chNode->text());
                        if (preg_match('/chapter\s*([\d.]+)/i', $chText, $cm)) {
                            $chapter = $cm[1];
                        }
                    }
                }

                // Status
                $status = 'Ongoing';
                if ($card && str_contains(strtolower($card->text()), 'completed')) {
                    $status = 'Completed';
                }

                $items[$slug] = [
                    'id' => 'kiryuu__' . $slug,
                    'title' => $title,
                    'cover' => $rawCover ? '/api/proxy-image?url=' . urlencode($rawCover) : '',
                    'format' => $format,
                    'genres' => [],
                    'rating' => $rating,
                    'latest_chapter' => $chapter,
                    'latest_chapter_id' => null,
                    'status' => $status,
                    'provider' => 'kiryuu',
                ];
            }
        });

        $hasNext = str_contains($html, 'class="flex items-center justify-center') && str_contains($html, "page/" . ($page + 1));

        return [
            'data' => array_values($items),
            'page' => $page,
            'total_page' => $hasNext ? $page + 1 : $page,
            'total_record' => count($items),
            'has_next' => $hasNext,
        ];
    }

    /**
     * Ambil detail lengkap komik dari Kiryuu.
     */
    public function getDetail(string $slug): ?array
    {
        $cleanSlug = preg_replace('/^kiryuu__/', '', $slug);
        $url = "manga/{$cleanSlug}/";
        $html = $this->fetchHtml($url, 14400);

        if (empty($html)) {
            return null;
        }

        $crawler = new Crawler($html);

        // Title
        $titleNode = $crawler->filter('h1')->first();
        if (!$titleNode->count()) {
            return null;
        }
        $title = trim($titleNode->text());

        // Cover
        $cover = '';
        $crawler->filter('img')->each(function (Crawler $node) use (&$cover, $title) {
            $src = $node->attr('src') ?? '';
            $alt = $node->attr('alt') ?? '';
            if (empty($cover) && str_contains($src, '/uploads/') && !str_contains($src, 'logo') && !str_contains($src, '.gif')) {
                if (!empty($alt) && stripos($alt, substr($title, 0, 10)) !== false) {
                    $cover = $src;
                }
            }
        });
        if (empty($cover)) {
            $firstImg = $crawler->filter('img[src*="/uploads/"]')->first();
            if ($firstImg->count()) {
                $cover = $firstImg->attr('src') ?? '';
            }
        }

        // Synopsis
        $description = '';
        $crawler->filter('p.leading-relaxed, [itemprop="description"], .text-gray-300')->each(function (Crawler $n) use (&$description) {
            $t = trim($n->text());
            if (strlen($t) > strlen($description) && !str_contains($t, 'Home »')) {
                $description = $t;
            }
        });

        // Genres
        $genres = [];
        $crawler->filter("a[href*='/genres/'], .ring-accent\/80 span")->each(function (Crawler $n) use (&$genres) {
            $g = trim($n->text());
            if (!empty($g) && !in_array($g, $genres, true) && strlen($g) < 25) {
                $genres[] = $g;
            }
        });

        // Status & Format
        $fullText = strtolower($html);
        $status = str_contains($fullText, 'completed') ? 'Completed' : 'Ongoing';
        $format = 'Manga';
        if (str_contains($fullText, 'manhwa')) {
            $format = 'Manhwa';
        } elseif (str_contains($fullText, 'manhua')) {
            $format = 'Manhua';
        }

        $chapters = $this->getChapters($cleanSlug);

        return [
            'id' => 'kiryuu__' . $cleanSlug,
            'title' => $title,
            'cover' => $cover ? '/api/proxy-image?url=' . urlencode($cover) : '',
            'description' => $description,
            'genres' => array_slice($genres, 0, 5),
            'format' => $format,
            'status' => $status,
            'rating' => '8.8',
            'latest_chapter' => $chapters[0]['number'] ?? null,
            'chapters_count' => count($chapters),
            'provider' => 'kiryuu',
        ];
    }

    /**
     * Ambil daftar seluruh chapter komik Kiryuu.
     */
    public function getChapters(string $slug): array
    {
        $cleanSlug = preg_replace('/^kiryuu__/', '', $slug);
        $url = "manga/{$cleanSlug}/";
        $html = $this->fetchHtml($url, 3600);

        if (empty($html)) {
            return [];
        }

        $crawler = new Crawler($html);
        $chapters = [];

        $crawler->filter('#chapter-list div[data-chapter-number]')->each(function (Crawler $node) use (&$chapters, $cleanSlug) {
            $num = $node->attr('data-chapter-number') ?? '';
            $a = $node->filter('a')->first();
            if ($a->count()) {
                $link = $a->attr('href') ?? '';
                $path = trim(parse_url($link, PHP_URL_PATH), '/');
                $parts = explode('/', $path);
                $chSlug = end($parts);

                $chapters[] = [
                    'id' => $chSlug,
                    'number' => (float) $num,
                    'label' => 'Chapter ' . $num,
                    'url' => $link,
                ];
            }
        });

        return $chapters;
    }

    /**
     * Ambil seluruh lembaran gambar chapter untuk reader long-strip.
     */
    public function getChapterPages(string $chapterPath, ?string $comicSlug = null): ?array
    {
        $cleanPath = trim(ltrim($chapterPath, '/'), '/');
        if (!str_contains($cleanPath, '/') && !empty($comicSlug)) {
            $cleanComic = preg_replace('/^kiryuu__/', '', $comicSlug);
            $cleanPath = "manga/{$cleanComic}/{$cleanPath}";
        }
        $html = $this->fetchHtml($cleanPath, 86400 * 7);

        if (empty($html)) {
            return null;
        }

        $crawler = new Crawler($html);
        $images = [];

        $crawler->filter('img')->each(function (Crawler $node) use (&$images) {
            $src = $node->attr('src') ?: ($node->attr('data-src') ?: '');
            if (
                !empty($src) &&
                (
                    str_contains($src, 'uqni.net') ||
                    str_contains($src, '88cdn.cloud') ||
                    str_contains($src, 'kiryuu.io') ||
                    str_contains($src, '/users/') ||
                    str_contains($src, '/v2/')
                ) &&
                !str_contains($src, '.gif') &&
                !str_contains($src, 'logo')
            ) {
                $encodedSrc = str_replace(' ', '%20', trim($src));
                if (!in_array($encodedSrc, $images, true)) {
                    $images[] = $encodedSrc;
                }
            }
        });

        $pages = [];
        foreach ($images as $idx => $imgUrl) {
            $pages[] = [
                'index' => $idx + 1,
                'url' => $imgUrl,
            ];
        }

        return [
            'chapter_id' => $cleanPath,
            'pages_count' => count($pages),
            'pages' => $pages,
        ];
    }
}
