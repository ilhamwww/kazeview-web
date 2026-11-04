<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WurmzScraperService
{
    protected string $baseUrl = 'https://wurmz.net';
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language' => 'id,en-US;q=0.9,en;q=0.8',
    ];

    /**
     * Fetch HTML with direct Guzzle / Http facade
     */
    protected function fetchHtml(string $url): ?string
    {
        try {
            $resp = Http::withHeaders($this->headers)
                ->timeout(12)
                ->get($url);

            if ($resp->successful()) {
                return $resp->body();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Wurmz fetchHtml error: {$e->getMessage()} for {$url}");
        }
        return null;
    }

    /**
     * Parse HTML komik cards from Wurmz
     */
    protected function parseCards(string $html): array
    {
        $items = [];
        preg_match_all('/<article class="comic-card group">(.*?)<\/article>/s', $html, $matches);

        foreach ($matches[1] as $cardHtml) {
            // Link & Slug
            if (!preg_match('/<a href="\/detail\/([^"\/]+)\/([^"]+)"/i', $cardHtml, $linkMatch)) {
                continue;
            }
            $type = strtolower($linkMatch[1]); // manga / manhwa / manhua
            $cleanSlug = $linkMatch[2];
            $id = 'wurmz__' . $type . '__' . $cleanSlug;

            // Title
            $title = '';
            if (preg_match('/<h2 class="comic-title[^"]*">(.*?)<\/h2>/s', $cardHtml, $titleMatch)) {
                $title = html_entity_decode(trim($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (preg_match('/aria-label="([^"]+)"/i', $cardHtml, $ariaMatch)) {
                $title = html_entity_decode(trim($ariaMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Cover
            $cover = '';
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $cardHtml, $imgMatch)) {
                $cover = $imgMatch[1];
                if (str_starts_with($cover, '/')) {
                    $cover = $this->baseUrl . $cover;
                }
            }

            // Type badge
            if (preg_match('/<span class="type-badge[^"]*">(.*?)<\/span>/s', $cardHtml, $typeMatch)) {
                $type = strtolower(trim(strip_tags($typeMatch[1])));
            }

            // Latest Chapter
            $latestChapter = '';
            if (preg_match('/<span class="ch-num">(.*?)<\/span>/s', $cardHtml, $chMatch)) {
                $rawCh = trim(preg_replace('/<!--.*?-->/', '', strip_tags($chMatch[1])));
                // Buang prefiks 'Ch.' atau 'Chapter' jika ada agar bersih
                $latestChapter = trim(preg_replace('/^(ch\.?|chapter)\s*/i', '', $rawCh));
            }

            // Chapter Time
            $latestChapterTime = '';
            if (preg_match('/<span class="ch-time">(.*?)<\/span>/s', $cardHtml, $timeMatch)) {
                $latestChapterTime = trim(strip_tags($timeMatch[1]));
            }

            $items[] = [
                'id' => $id,
                'slug' => $cleanSlug,
                'title' => $title,
                'cover' => $cover,
                'type' => $type,
                'format' => $type,
                'status' => 'Ongoing',
                'description' => '',
                'latest_chapter' => $latestChapter,
                'latest_chapter_time' => $latestChapterTime,
                'rating' => null,
            ];
        }

        return $items;
    }

    /**
     * Get Popular Comics
     */
    public function getPopular(int $page = 1, int $limit = 60, ?string $format = null): array
    {
        $cacheKey = "wurmz_popular_v3_p{$page}_l{$limit}_f" . ($format ?? 'all');
        return Cache::remember($cacheKey, 1800, function () use ($page, $limit, $format) {
            $items = [];
            $totalPage = 388;

            // Fetch 2 halaman Wurmz (masing-masing ~30 item) per 1 halaman KZSTRM agar grid padat (50-60 item)
            $startPage = ($page - 1) * 2 + 1;
            for ($p = $startPage; $p <= $startPage + 1; $p++) {
                $params = [
                    'sort' => 'popular_all',
                ];
                if ($p > 1) {
                    $params['page'] = $p;
                }
                if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
                    $params['type'] = strtolower($format);
                }

                $url = $this->baseUrl . '/semua-komik?' . http_build_query($params);
                $html = $this->fetchHtml($url);
                if ($html) {
                    $parsed = $this->parseCards($html);
                    $items = array_merge($items, $parsed);

                    if (preg_match('/<span class="text-silver font-semibold">\d+<\/span>\s*\/\s*<!-- -->(\d+)/', $html, $tpMatch)) {
                        $totalPage = (int) $tpMatch[1];
                    }
                }
            }

            // Deduplicate berdasarkan ID
            $unique = [];
            $filtered = [];
            foreach ($items as $it) {
                if (!isset($unique[$it['id']])) {
                    $unique[$it['id']] = true;
                    $filtered[] = $it;
                }
            }

            $adjustedTotalPage = max(1, (int) ceil($totalPage / 2));

            return [
                'data' => array_slice($filtered, 0, $limit),
                'total_page' => $adjustedTotalPage,
                'has_next' => $page < $adjustedTotalPage,
            ];
        });
    }

    /**
     * Get Latest Updated Comics
     */
    public function getLatest(int $page = 1, int $limit = 60, ?string $format = null): array
    {
        $cacheKey = "wurmz_latest_v3_p{$page}_l{$limit}_f" . ($format ?? 'all');
        return Cache::remember($cacheKey, 600, function () use ($page, $limit, $format) {
            $items = [];
            $totalPage = 387;

            // Fetch 2 halaman Wurmz per 1 halaman KZSTRM
            $startPage = ($page - 1) * 2 + 1;
            for ($p = $startPage; $p <= $startPage + 1; $p++) {
                $params = [
                    'sort' => 'update',
                ];
                if ($p > 1) {
                    $params['page'] = $p;
                }
                if ($format && in_array(strtolower($format), ['manhwa', 'manga', 'manhua'])) {
                    $params['type'] = strtolower($format);
                }

                $url = $this->baseUrl . '/semua-komik?' . http_build_query($params);
                $html = $this->fetchHtml($url);
                if ($html) {
                    $parsed = $this->parseCards($html);
                    $items = array_merge($items, $parsed);

                    if (preg_match('/<span class="text-silver font-semibold">\d+<\/span>\s*\/\s*<!-- -->(\d+)/', $html, $tpMatch)) {
                        $totalPage = (int) $tpMatch[1];
                    }
                }
            }

            $unique = [];
            $filtered = [];
            foreach ($items as $it) {
                if (!isset($unique[$it['id']])) {
                    $unique[$it['id']] = true;
                    $filtered[] = $it;
                }
            }

            $adjustedTotalPage = max(1, (int) ceil($totalPage / 2));

            return [
                'data' => array_slice($filtered, 0, $limit),
                'total_page' => $adjustedTotalPage,
                'has_next' => $page < $adjustedTotalPage,
            ];
        });
    }

    /**
     * Search Comics
     */
    public function search(string $query, int $page = 1, int $limit = 24): array
    {
        $url = $this->baseUrl . '/search?q=' . urlencode($query);
        $html = $this->fetchHtml($url);
        if (!$html) {
            return ['data' => [], 'total_page' => 1, 'has_next' => false];
        }

        $items = $this->parseCards($html);
        return [
            'data' => array_slice($items, 0, $limit),
            'total_page' => 1,
            'has_next' => false,
        ];
    }

    /**
     * Get Comic Detail
     */
    public function getDetail(string $idOrSlug): ?array
    {
        // format slug: wurmz__manga__aplikasi-jebakan or aplikasi-jebakan
        $type = 'manga';
        $slug = $idOrSlug;
        if (str_starts_with($idOrSlug, 'wurmz__')) {
            $parts = explode('__', $idOrSlug);
            $type = $parts[1] ?? 'manga';
            $slug = $parts[2] ?? $idOrSlug;
        }

        $cacheKey = "wurmz_detail_{$type}_{$slug}";
        return Cache::remember($cacheKey, 3600, function () use ($type, $slug, $idOrSlug) {
            $url = "{$this->baseUrl}/detail/{$type}/{$slug}";
            $html = $this->fetchHtml($url);
            if (!$html) {
                return null;
            }

            // Title
            $title = $slug;
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $tMatch)) {
                $title = html_entity_decode(trim(strip_tags($tMatch[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Cover
            $cover = '';
            if (preg_match('/<img[^>]+src="(\/covers\/[^"]+)"/i', $html, $cMatch)) {
                $cover = $this->baseUrl . $cMatch[1];
            }

            // Description / Synopsis
            $description = '';
            if (preg_match('/<h3[^>]*>Sinopsis<\/h3>\s*<p[^>]*>(.*?)<\/p>/s', $html, $dMatch)) {
                $description = html_entity_decode(trim(strip_tags($dMatch[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Alt Title
            $altTitle = '';
            if (preg_match('/<h1[^>]*>.*?<\/h1>\s*<p class="text-sm text-faint mt-1">(.*?)<\/p>/s', $html, $altMatch)) {
                $altTitle = html_entity_decode(trim(strip_tags($altMatch[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Genres
            $genres = [];
            preg_match_all('/<a href="\/genre\/[^"]+" class="chip">(.*?)<\/a>/s', $html, $gMatches);
            if (!empty($gMatches[1])) {
                foreach ($gMatches[1] as $g) {
                    $genres[] = trim(strip_tags($g));
                }
            }

            // Chapters list
            $chapters = [];
            preg_match_all('/<a href="\/detail\/([^\/]+)\/([^\/]+)\/chapter\/([^"]+)"[^>]*title="([^"]+)" class="chap-cell[^"]*">(.*?)<\/a>/s', $html, $chMatches, PREG_SET_ORDER);
            foreach ($chMatches as $ch) {
                $chNum = $ch[3];
                $chLabel = $ch[4] ?: ('Chapter ' . $chNum);
                $chapters[] = [
                    'id' => $chNum,
                    'number' => (float) $chNum,
                    'label' => $chLabel,
                    'released_at' => '',
                ];
            }

            return [
                'id' => str_starts_with($idOrSlug, 'wurmz__') ? $idOrSlug : "wurmz__{$type}__{$slug}",
                'slug' => $slug,
                'title' => $title,
                'alt_title' => $altTitle,
                'cover' => $cover,
                'banner' => $cover,
                'description' => $description,
                'type' => $type,
                'format' => $type,
                'status' => 'Ongoing',
                'rating' => null,
                'genres' => $genres,
                'author' => '',
                'chapters' => $chapters,
            ];
        });
    }

    /**
     * Get Chapter Pages / Images
     */
    public function getChapterPages(string $comicId, string $chapterId): array
    {
        $type = 'manga';
        $slug = $comicId;
        if (str_starts_with($comicId, 'wurmz__')) {
            $parts = explode('__', $comicId);
            $type = $parts[1] ?? 'manga';
            $slug = $parts[2] ?? $comicId;
        }

        $cacheKey = "wurmz_pages_{$type}_{$slug}_ch{$chapterId}";
        return Cache::remember($cacheKey, 86400 * 7, function () use ($type, $slug, $chapterId) {
            $url = "{$this->baseUrl}/detail/{$type}/{$slug}/chapter/{$chapterId}";
            $html = $this->fetchHtml($url);
            if (!$html) {
                return [];
            }

            // Ekstrak gambar panel
            $pages = [];
            preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $imgMatches);
            if (!empty($imgMatches[1])) {
                $idx = 1;
                foreach ($imgMatches[1] as $img) {
                    // Filter gambar panel yang valid (bukan logo, icon, avatar, dsb)
                    if (
                        str_contains($img, 'cdn.natsu.id') ||
                        str_contains($img, 'imgsvr.my.id') ||
                        str_contains($img, '/file/comic/') ||
                        str_contains($img, '/img/') ||
                        preg_match('/\/(?:chapter|ch|comic|images)\//i', $img)
                    ) {
                        $pages[] = [
                            'index' => $idx++,
                            'url' => $img,
                        ];
                    }
                }
            }

            // Fallback: Jika tidak ketemu dari tag <img>, ekstrak dari script Next.js
            if (empty($pages)) {
                preg_match_all('/https?:\/\/[^\s"\'\\\\]+\.(?:jpg|jpeg|png|webp|avif)/i', $html, $scriptImgs);
                if (!empty($scriptImgs[0])) {
                    $idx = 1;
                    $seen = [];
                    foreach ($scriptImgs[0] as $img) {
                        if (
                            !isset($seen[$img]) &&
                            !str_contains($img, '/covers/') &&
                            !str_contains($img, 'logo') &&
                            !str_contains($img, 'icon') &&
                            (str_contains($img, 'imgsvr') || str_contains($img, 'natsu') || str_contains($img, '/chapter') || str_contains($img, '/comic/'))
                        ) {
                            $seen[$img] = true;
                            $pages[] = [
                                'index' => $idx++,
                                'url' => $img,
                            ];
                        }
                    }
                }
            }

            return $pages;
        });
    }

    /**
     * Get Chapters list directly
     */
    public function getChapters(string $idOrSlug): array
    {
        $detail = $this->getDetail($idOrSlug);
        return $detail['chapters'] ?? [];
    }
}

