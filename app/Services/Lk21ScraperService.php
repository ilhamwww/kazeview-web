<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Lk21ScraperService
{
    protected string $baseUrl;

    /**
     * Category IDs for 18+ / Adult content on LK21
     * 2693: Adult / JAV
     * 2703: Semi Japan
     * 2702: Semi Philipina (Vivamax)
     * 2694: Semi Korea
     * 3270: Semi Barat
     * 2704: Semi India
     */
    protected array $adultCategoryIds = [2693, 2703, 2702, 2694, 3270, 2704];

    public function __construct()
    {
        $this->baseUrl = rtrim(env('LK21_BASE_URL', 'http://198.252.99.106'), '/');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get list of categories/genres available for Server LK (18+ / Adult Only)
     */
    public function getCategories(): array
    {
        return [
            ['id' => null, 'slug' => 'all', 'name' => 'Semua 18+'],
            ['id' => 2693, 'slug' => 'adult', 'name' => 'Adult (JAV)'],
            ['id' => 2703, 'slug' => 'semi-japan', 'name' => 'Semi Japan'],
            ['id' => 2702, 'slug' => 'semi-philipina', 'name' => 'Semi Philipina (Vivamax)'],
            ['id' => 2694, 'slug' => 'semi-korea', 'name' => 'Semi Korea'],
            ['id' => 3270, 'slug' => 'semi-barat', 'name' => 'Semi Barat'],
            ['id' => 2704, 'slug' => 'semi-india', 'name' => 'Semi India'],
        ];
    }

    protected function secureImageUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        if (str_starts_with($url, 'http://')) {
            return url('/api/proxy-image?url=' . urlencode($url));
        }
        return $url;
    }

    /**
     * Get paginated movies from LK21 API
     *
     * @param int $page
     * @param int|null $categoryId
     * @param string $search
     * @param int $perPage
     * @return array
     */
    public function getMovies(int $page = 1, ?int $categoryId = null, string $search = '', int $perPage = 24): array
    {
        $page = max(1, $page);
        $perPage = min(36, max(6, $perPage));
        $cacheKey = "lk21_adult_movies_p{$page}_cat" . ($categoryId ?? 'all') . "_s" . md5(strtolower(trim($search))) . "_{$perPage}";

        return Cache::remember($cacheKey, 600, function () use ($page, $categoryId, $search, $perPage) {
            try {
                $params = [
                    'page' => $page,
                    'per_page' => $perPage,
                    '_embed' => 1,
                ];

                if (!empty($search)) {
                    $params['search'] = trim($search);
                }

                if (!empty($categoryId) && is_numeric($categoryId)) {
                    $params['categories'] = (int) $categoryId;
                } else {
                    // Strict: ONLY 18+ / Adult categories!
                    $params['categories'] = implode(',', $this->adultCategoryIds);
                }

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])
                ->timeout(12)
                ->connectTimeout(5)
                ->get("{$this->baseUrl}/wp-json/wp/v2/posts", $params);

                if (!$response->successful()) {
                    Log::warning("Lk21ScraperService::getMovies failed HTTP {$response->status()}");
                    return [
                        'items' => [],
                        'totalPages' => 1,
                        'totalItems' => 0,
                        'currentPage' => $page,
                    ];
                }

                $totalPages = (int) ($response->header('X-WP-TotalPages') ?: 1);
                $totalItems = (int) ($response->header('X-WP-Total') ?: 0);
                $posts = $response->json();

                if (!is_array($posts)) {
                    return [
                        'items' => [],
                        'totalPages' => 1,
                        'totalItems' => 0,
                        'currentPage' => $page,
                    ];
                }

                $items = [];
                foreach ($posts as $post) {
                    $slug = $post['slug'] ?? '';
                    if (empty($slug)) continue;

                    $title = html_entity_decode($post['title']['rendered'] ?? 'Untitled', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    // Extract poster image
                    $poster = null;
                    if (!empty($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                        $poster = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
                    }

                    // Extract genres
                    $genres = [];
                    if (!empty($post['_embedded']['wp:term'])) {
                        foreach ($post['_embedded']['wp:term'] as $taxGroup) {
                            if (!is_array($taxGroup)) continue;
                            foreach ($taxGroup as $term) {
                                if (($term['taxonomy'] ?? '') === 'category') {
                                    $genres[] = html_entity_decode($term['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                }
                            }
                        }
                    }

                    // Extract year from title if present
                    $year = null;
                    if (preg_match('/\((\d{4})\)/', $title, $mYear)) {
                        $year = $mYear[1];
                    }

                    $synopsis = strip_tags(html_entity_decode($post['excerpt']['rendered'] ?? $post['content']['rendered'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $synopsis = trim(preg_replace('/\s+/', ' ', $synopsis));

                    $items[] = [
                        'id' => (string) ($post['id'] ?? $slug),
                        'title' => $title,
                        'slug' => $slug,
                        'poster' => $this->secureImageUrl($poster),
                        'genres' => array_slice($genres, 0, 3),
                        'year' => $year ?: date('Y', strtotime($post['date'] ?? 'now')),
                        'quality' => 'HD',
                        'synopsis' => $synopsis,
                        'url' => url('/server-lk/' . $slug),
                    ];
                }

                return [
                    'items' => $items,
                    'totalPages' => max(1, $totalPages),
                    'totalItems' => $totalItems,
                    'currentPage' => $page,
                ];
            } catch (\Throwable $e) {
                Log::error("Lk21ScraperService::getMovies exception: " . $e->getMessage());
                return [
                    'items' => [],
                    'totalPages' => 1,
                    'totalItems' => 0,
                    'currentPage' => $page,
                ];
            }
        });
    }

    /**
     * Get single movie detail and all stream mirrors
     *
     * @param string $slug
     * @return array|null
     */
    public function getMovieDetail(string $slug): ?array
    {
        $cleanSlug = trim(strip_tags($slug));
        if (empty($cleanSlug)) {
            return null;
        }

        $cacheKey = "lk21_detail_" . md5($cleanSlug);

        return Cache::remember($cacheKey, 21600, function () use ($cleanSlug) {
            try {
                $url = "{$this->baseUrl}/{$cleanSlug}/";
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->timeout(6)
                ->connectTimeout(3)
                ->get($url);

                if (!$response->successful()) {
                    Log::warning("Lk21ScraperService::getMovieDetail failed HTTP {$response->status()} for slug: {$cleanSlug}");
                    return null;
                }

                $html = $response->body();

                // 1. Title
                preg_match("/<h1[^>]*class=[\x22\x27]entry-title[\x22\x27][^>]*>(.*?)<\/h1>/si", $html, $mTitle);
                $title = html_entity_decode(trim(strip_tags($mTitle[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (empty($title) || str_contains(strtolower($title), '404 not found')) {
                    return null;
                }

                // 2. Rating & Votes
                preg_match("/<span[^>]*itemprop=[\x22\x27]ratingValue[\x22\x27][^>]*>(.*?)<\/span>/si", $html, $mRating);
                $rating = trim(strip_tags($mRating[1] ?? ''));
                if (empty($rating)) {
                    preg_match("/<div[^>]*class=[\x22\x27]gmr-rating-item[\x22\x27][^>]*>.*?([\d\.]+)/si", $html, $mRating2);
                    $rating = trim($mRating2[1] ?? '');
                }

                preg_match("/<span[^>]*itemprop=[\x22\x27]ratingCount[\x22\x27][^>]*>(.*?)<\/span>/si", $html, $mVotes);
                $votes = trim(strip_tags($mVotes[1] ?? ''));

                // 3. Duration, Release, Country, Director
                preg_match("/<strong>Duration:\s*<\/strong><span[^>]*>(.*?)<\/span>/si", $html, $mDuration);
                $duration = trim(strip_tags($mDuration[1] ?? ''));

                preg_match("/<strong>Release:<\/strong><span><time[^>]*>(.*?)<\/time>/si", $html, $mRelease);
                $release = trim(strip_tags($mRelease[1] ?? ''));

                preg_match("/<strong>Country:<\/strong><span[^>]*itemprop=[\x22\x27]contentLocation[\x22\x27][^>]*><a[^>]*>(.*?)<\/a>/si", $html, $mCountry);
                $country = trim(strip_tags($mCountry[1] ?? ''));

                preg_match("/<strong>Director:<\/strong><span[^>]*itemprop=[\x22\x27]director[\x22\x27][^>]*><span[^>]*itemprop=[\x22\x27]name[\x22\x27]><a[^>]*>(.*?)<\/a>/si", $html, $mDirector);
                $director = trim(strip_tags($mDirector[1] ?? ''));

                // 4. Casts
                preg_match_all("/<span[^>]*itemprop=[\x22\x27]actors[\x22\x27][^>]*><span[^>]*itemprop=[\x22\x27]name[\x22\x27]><a[^>]*>(.*?)<\/a>/si", $html, $mCasts);
                $casts = array_filter(array_map(fn($c) => html_entity_decode(trim(strip_tags($c)), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $mCasts[1] ?? []));

                // 5. Genres
                preg_match("/<strong>Genre:\s*<\/strong>(.*?)<\/div>/si", $html, $mGenreBlock);
                $genres = [];
                if (!empty($mGenreBlock[1])) {
                    preg_match_all("/<a[^>]*>(.*?)<\/a>/si", $mGenreBlock[1], $mG);
                    foreach ($mG[1] ?? [] as $g) {
                        $cleanG = trim(strip_tags($g));
                        if (!empty($cleanG)) {
                            $genres[] = html_entity_decode($cleanG, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        }
                    }
                }

                // 6. Synopsis
                preg_match("/<div[^>]*itemprop=[\x22\x27]description[\x22\x27][^>]*>\s*<p>(.*?)<\/p>/si", $html, $mSynopsis);
                $synopsis = html_entity_decode(trim(strip_tags($mSynopsis[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // 7. Poster
                preg_match("/<figure[^>]*class=[\x22\x27]pull-left[\x22\x27]>.*?<img[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/si", $html, $mPoster);
                $poster = $mPoster[1] ?? null;
                if ($poster) {
                    // Try to get clean non-scaled image
                    $poster = preg_replace('/-\d+x\d+(\.[a-zA-Z]+)$/', '$1', $poster);
                }

                // 8. Trailer
                $trailerUrl = null;
                if (preg_match("/<a[^>]*href=[\x22\x27](https:\/\/(?:www\.)?youtube\.com\/watch\?v=[^\x22\x27]+)[\x22\x27][^>]*class=[\x22\x27][^\x22\x27]*gmr-trailer-popup/si", $html, $mTrailer)) {
                    $trailerUrl = $mTrailer[1];
                } elseif (preg_match("/<a[^>]*class=[\x22\x27][^\x22\x27]*gmr-trailer-popup[^\x22\x27]*[\x22\x27][^>]*href=[\x22\x27](https:\/\/(?:www\.)?youtube\.com\/watch\?v=[^\x22\x27]+)[\x22\x27]/si", $html, $mTrailer2)) {
                    $trailerUrl = $mTrailer2[1];
                }

                // 9. Stream Servers / Mirrors
                $servers = [];
                // Server 1
                preg_match("/<div[^>]*class=[\x22\x27]gmr-embed-responsive[\x22\x27]><iframe[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/si", $html, $mIframe1);
                if (!empty($mIframe1[1])) {
                    $servers[] = [
                        'name' => 'Server 1',
                        'embed_url' => trim($mIframe1[1]),
                    ];
                }

                // Check other player tabs (?player=2, ?player=3, ?player=4)
                preg_match_all("/href=[\x22\x27][^\x22\x27]*\?player=(\d+)[\x22\x27]/si", $html, $mPlayerNums);
                $playerNums = array_unique($mPlayerNums[1] ?? []);
                sort($playerNums);

                foreach ($playerNums as $pNum) {
                    try {
                        $pRes = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                        ])
                        ->timeout(3)
                        ->get("{$url}?player={$pNum}");

                        if ($pRes->successful()) {
                            preg_match("/<iframe[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/si", $pRes->body(), $mPlayerIframe);
                            if (!empty($mPlayerIframe[1])) {
                                $servers[] = [
                                    'name' => "Server {$pNum}",
                                    'embed_url' => trim($mPlayerIframe[1]),
                                ];
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignore individual player mirror failure
                    }
                }

                // 10. Related Movies
                $related = [];
                preg_match_all("/<article[^>]*class=[\x22\x27]item[^>]*>.*?<a[^>]*href=[\x22\x27](?:http:\/\/[^\/]+)?\/([^\x22\x27\/]+)\/[\x22\x27][^>]*title=[\x22\x27]Permalink to: ([^\x22\x27]+)[\x22\x27].*?<img[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/si", $html, $mRel, PREG_SET_ORDER);
                foreach (array_slice($mRel, 0, 8) as $r) {
                    $rSlug = $r[1];
                    $rTitle = html_entity_decode(trim($r[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $rPoster = preg_replace('/-\d+x\d+(\.[a-zA-Z]+)$/', '$1', $r[3]);
                    $related[] = [
                        'slug' => $rSlug,
                        'title' => $rTitle,
                        'poster' => $this->secureImageUrl($rPoster),
                        'url' => url('/server-lk/' . $rSlug),
                    ];
                }

                // Year
                $year = null;
                if (preg_match('/\((\d{4})\)/', $title, $mY)) {
                    $year = $mY[1];
                } elseif (!empty($release) && preg_match('/\b(19\d{2}|20\d{2})\b/', $release, $mY2)) {
                    $year = $mY2[1];
                }

                return [
                    'slug' => $cleanSlug,
                    'title' => $title,
                    'rating' => !empty($rating) ? number_format((float) $rating, 1) : null,
                    'rating_count' => $votes,
                    'duration' => $duration ?: null,
                    'release' => $release ?: null,
                    'year' => $year ?: date('Y'),
                    'country' => $country ?: null,
                    'director' => $director ?: null,
                    'casts' => $casts,
                    'genres' => $genres,
                    'synopsis' => $synopsis,
                    'poster' => $this->secureImageUrl($poster),
                    'trailer_url' => $trailerUrl,
                    'servers' => $servers,
                    'related' => $related,
                ];
            } catch (\Throwable $e) {
                Log::error("Lk21ScraperService::getMovieDetail exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Search movies for global search integration
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search(string $query, int $limit = 12): array
    {
        $res = $this->getMovies(page: 1, categoryId: null, search: $query, perPage: $limit);
        return $res['items'] ?? [];
    }
}
