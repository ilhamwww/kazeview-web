<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class IzanamiScansService
{
    protected string $baseUrl = 'https://izanamiscans.my.id';

    /**
     * Ambil konten HTML dari Izanami Scans.
     */
    public function fetchHtml(string $url, int $cacheSeconds = 1800): string
    {
        $fullUrl = str_starts_with($url, 'http') ? $url : rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
        $cacheKey = 'izanami_html_' . md5($fullUrl);

        $cached = Cache::get($cacheKey);
        if ($cached && is_string($cached) && strlen($cached) > 200) {
            return $cached;
        }

        try {
            $res = Http::withoutVerifying()
                ->timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Referer' => 'https://izanamiscans.my.id/',
                ])
                ->get($fullUrl);

            if ($res->successful() && strlen($res->body()) > 200) {
                Cache::put($cacheKey, $res->body(), $cacheSeconds);
                return $res->body();
            }
        } catch (\Throwable $e) {
            Log::error('Izanami fetchHtml error: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Ambil komik populer dari Izanami Scans.
     */
    public function getPopular(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $params = ['order' => 'popular'];
        if ($page > 1) {
            $params['page'] = $page;
        }
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['type'] = strtolower($format);
        }

        $url = 'manga/?' . http_build_query($params);
        $html = $this->fetchHtml($url, 7200);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Ambil update komik terbaru dari Izanami Scans.
     */
    public function getLatest(int $page = 1, int $pageSize = 24, ?string $format = null): array
    {
        $params = ['order' => 'update'];
        if ($page > 1) {
            $params['page'] = $page;
        }
        if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
            $params['type'] = strtolower($format);
        }

        $url = 'manga/?' . http_build_query($params);
        $html = $this->fetchHtml($url, 1800);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Cari komik di Izanami Scans.
     */
    public function search(string $query, int $page = 1, int $pageSize = 24): array
    {
        $params = ['s' => $query];
        if ($page > 1) {
            $params['page'] = $page;
        }
        $url = '?' . http_build_query($params);
        $html = $this->fetchHtml($url, 3600);

        return $this->parseListingHtml($html, $page);
    }

    /**
     * Parse HTML halaman katalog / list komik Izanami Scans.
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

        $crawler->filter('.listupd .bsx, .bs .bsx')->each(function (Crawler $node) use (&$items) {
            $a = $node->filter('a')->first();
            if (!$a->count()) return;

            $href = $a->attr('href') ?? '';
            $title = $a->attr('title') ?? '';
            $slug = trim(parse_url($href, PHP_URL_PATH), '/');
            $cleanSlug = preg_replace('#^manga/#', '', $slug);

            if (isset($items[$cleanSlug])) return;

            $format = $node->filter('.type')->count() ? trim($node->filter('.type')->text()) : 'Manhua';

            $imgNode = $node->filter('img')->first();
            $rawCover = $imgNode->count() ? ($imgNode->attr('src') ?: ($imgNode->attr('data-src') ?: '')) : '';

            $rating = null;
            if ($node->filter('.numscore')->count()) {
                $val = (float) trim($node->filter('.numscore')->text());
                if ($val > 0) $rating = number_format($val, 1);
            }

            $chapter = null;
            if ($node->filter('.epxs')->count()) {
                $chText = trim($node->filter('.epxs')->text());
                if (preg_match('/chapter\s*([\d.]+)/i', $chText, $cm)) {
                    $chapter = $cm[1];
                }
            }

            $items[$cleanSlug] = [
                'id' => 'izanami__' . $cleanSlug,
                'title' => $title ?: ucwords(str_replace('-', ' ', $cleanSlug)),
                'cover' => $rawCover ? '/api/proxy-image?url=' . urlencode($rawCover) : '',
                'format' => $format,
                'genres' => [],
                'rating' => $rating,
                'latest_chapter' => $chapter,
                'latest_chapter_id' => null,
                'status' => 'Ongoing',
                'provider' => 'izanami',
            ];
        });

        $hasNext = str_contains($html, 'class="r"') || str_contains($html, 'Selanjutnya');

        return [
            'data' => array_values($items),
            'page' => $page,
            'total_page' => $hasNext ? $page + 1 : $page,
            'total_record' => count($items),
            'has_next' => $hasNext,
        ];
    }

    /**
     * Ambil detail lengkap komik dari Izanami Scans.
     */
    public function getDetail(string $slug): ?array
    {
        $cleanSlug = preg_replace('/^izanami__/', '', $slug);
        $url = "manga/{$cleanSlug}/";
        $html = $this->fetchHtml($url, 14400);

        if (empty($html)) {
            return null;
        }

        $crawler = new Crawler($html);

        $titleNode = $crawler->filter('h1.entry-title')->first();
        if (!$titleNode->count()) {
            return null;
        }
        $title = trim($titleNode->text());

        $cover = '';
        $thumbNode = $crawler->filter('.thumb img, .entry-content img')->first();
        if ($thumbNode->count()) {
            $cover = $thumbNode->attr('src') ?? '';
        }

        $desc = '';
        $descNode = $crawler->filter('.entry-content-single p, .entry-content p')->first();
        if ($descNode->count()) {
            $desc = trim($descNode->text());
        }

        $format = 'Manhua';
        if ($crawler->filter('.type')->count()) {
            $format = trim($crawler->filter('.type')->text());
        }

        $genres = [];
        $crawler->filter('.mgen a, .genres a')->each(function (Crawler $n) use (&$genres) {
            $genres[] = trim($n->text());
        });

        $chapters = $this->getChapters($cleanSlug);

        return [
            'id' => 'izanami__' . $cleanSlug,
            'title' => $title,
            'cover' => $cover ? '/api/proxy-image?url=' . urlencode($cover) : '',
            'description' => $desc,
            'genres' => array_slice($genres, 0, 5),
            'format' => $format,
            'status' => 'Ongoing',
            'rating' => '8.5',
            'latest_chapter' => $chapters[0]['number'] ?? null,
            'chapters_count' => count($chapters),
            'provider' => 'izanami',
        ];
    }

    /**
     * Ambil daftar seluruh chapter komik Izanami Scans.
     */
    public function getChapters(string $slug): array
    {
        $cleanSlug = preg_replace('/^izanami__/', '', $slug);
        $url = "manga/{$cleanSlug}/";
        $html = $this->fetchHtml($url, 3600);

        if (empty($html)) {
            return [];
        }

        $crawler = new Crawler($html);
        $chapters = [];

        $crawler->filter('#chapterlist ul li')->each(function (Crawler $node) use (&$chapters) {
            $a = $node->filter('.eph-num a')->first();
            if ($a->count()) {
                $chTitle = $node->filter('.chapternum')->text('');
                $url = $a->attr('href') ?? '';
                $num = (float) ($node->attr('data-num') ?? 0);
                $chSlug = trim(parse_url($url, PHP_URL_PATH), '/');

                $chapters[] = [
                    'id' => $chSlug,
                    'number' => $num,
                    'label' => $chTitle ?: ('Chapter ' . $num),
                    'url' => $url,
                ];
            }
        });

        return $chapters;
    }

    /**
     * Ambil seluruh lembaran gambar chapter untuk reader long-strip.
     */
    public function getChapterPages(string $chapterSlug): ?array
    {
        $cleanSlug = trim(ltrim($chapterSlug, '/'), '/');
        $url = "{$cleanSlug}/";
        $html = $this->fetchHtml($url, 86400 * 7);

        if (empty($html)) {
            return null;
        }

        $images = [];

        // MangaStream ts_reader.run JSON
        if (preg_match('/ts_reader\.run\((.*?)\);/s', $html, $m)) {
            $json = json_decode($m[1], true);
            if ($json && !empty($json['sources'][0]['images'])) {
                $images = $json['sources'][0]['images'];
            }
        }

        // Fallback selector img di #readerarea
        if (empty($images)) {
            $crawler = new Crawler($html);
            $crawler->filter('#readerarea img')->each(function (Crawler $node) use (&$images) {
                $src = $node->attr('src') ?: ($node->attr('data-src') ?: '');
                if (!empty($src) && !str_contains($src, '.svg')) {
                    $images[] = $src;
                }
            });
        }

        $pages = [];
        foreach ($images as $idx => $imgUrl) {
            $pages[] = [
                'index' => $idx + 1,
                'url' => $imgUrl, // Langsung dari Google CDN Blogger!
            ];
        }

        return [
            'chapter_id' => $cleanSlug,
            'pages_count' => count($pages),
            'pages' => $pages,
        ];
    }
}
