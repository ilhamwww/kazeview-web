<?php

namespace App\Services;

use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlobalSearchService
{
    /**
     * Ultra-Fast Unified Global Search across all KZSTRM sources:
     * - Movies & TV Series (IDLIX via Fast NUC Relay & Rebahin)
     * - Anime (Mirainime & Otakudesu - Parallel Async)
     * - Dracin (Parallel Async Pool: DramaBox, ReelBuzz, Sereal+, FlexTV, ReelShort, DramaWave)
     *
     * @param string $query Keyword to search
     * @param string $category Filter: 'all', 'movie', 'series', 'anime', 'dracin'
     * @param int $limit Maximum results to return
     * @return array List of unified media items
     */
    public static function search(string $query, string $category = 'all', int $limit = 36): array
    {
        $q = trim($query);
        if (empty($q)) {
            return [];
        }

        $category = strtolower(trim($category));
        if (!in_array($category, ['all', 'movie', 'series', 'anime', 'dracin'], true)) {
            $category = 'all';
        }

        $cacheKey = 'kz_global_search_v4_' . md5(strtolower($q) . '_' . $category . '_' . $limit);

        return Cache::remember($cacheKey, 3600, function () use ($q, $category, $limit) {
            $movies = [];
            $series = [];
            $animes = [];
            $dracins = [];

            // =================================================================
            // 1. Fetch Movies & TV Series
            // =================================================================
            if (in_array($category, ['all', 'movie', 'series'], true)) {
                try {
                    $mediaResults = StreamingService::search($q);
                    foreach ($mediaResults as $item) {
                        $isSeries = isset($item['type']) && in_array(strtolower($item['type']), ['tv_series', 'tv', 'series'], true);
                        $itemCat = $isSeries ? 'series' : 'movie';

                        if ($category !== 'all' && $category !== $itemCat) {
                            continue;
                        }

                        $slug = $item['slug'] ?? '';
                        if (empty($slug)) continue;

                        $url = $isSeries
                            ? url('/series/' . $slug)
                            : url('/movie/' . $slug);

                        $formatted = [
                            'id' => (string) ($item['id'] ?? $slug),
                            'title' => (string) ($item['title'] ?? 'Untitled'),
                            'category' => $itemCat,
                            'type' => $isSeries ? 'Series' : 'Film',
                            'badge' => $isSeries ? 'Series' : 'Film',
                            'year' => (string) ($item['year'] ?? ''),
                            'rating' => !empty($item['rating']) && (string)$item['rating'] !== '0.0' ? number_format((float)$item['rating'], 1) : null,
                            'thumbnail' => $item['thumbnail'] ?? $item['poster'] ?? null,
                            'url' => $url,
                            'slug' => $slug,
                            'source_name' => str_starts_with($slug, 'rb-') ? 'Server 2' : 'Server 1',
                        ];

                        if ($isSeries) {
                            $series[] = $formatted;
                        } else {
                            $movies[] = $formatted;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: Movie/Series search failed: ' . $e->getMessage());
                }
            }

            // =================================================================
            // 2. Fetch Anime & Dracin in a Single Mega-Pool for maximum concurrency
            // =================================================================
            $needAnime = in_array($category, ['all', 'anime'], true);
            $needDracin = in_array($category, ['all', 'dracin'], true);

            if ($needAnime || $needDracin) {
                $setting = ScraperSetting::getInstance();
                $apiKey = $setting->dramabuzz_api_key ?? '';

                $poolRequests = [];

                if ($needAnime) {
                    $poolRequests['mirainime'] = fn ($p) => $p->timeout(3.5)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get('https://mirainime.com/anime-list?q=' . urlencode($q));

                    $poolRequests['otakudesu'] = fn ($p) => $p->timeout(3.5)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get('https://otakudesu.blog/?s=' . urlencode($q) . '&post_type=anime');
                }

                if ($needDracin) {
                    $poolRequests['dramabox'] = fn ($p) => $p->timeout(3.5)->withHeaders([
                        'x-token' => $apiKey,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get('https://dramabox.goodbos.online/api/dramabox/search?query=' . urlencode($q) . '&lang=in');

                    $poolRequests['reelbuzz'] = fn ($p) => $p->timeout(3.5)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get('https://reelbuzz.goodbos.online/api/search?q=' . urlencode($q) . '&code=' . $apiKey);

                    $poolRequests['serealplus'] = fn ($p) => $p->timeout(3.5)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get('https://serealplus.goodbos.online/api/search?q=' . urlencode($q) . '&lang=id&page=1&code=' . $apiKey);

                    $poolRequests['flextv'] = fn ($p) => $p->timeout(3.5)->get('https://flextv.goodbos.online/api/drama/search?keyword=' . urlencode($q) . '&page=1');
                    $poolRequests['reelshort'] = fn ($p) => $p->timeout(3.5)->get('https://reelshort.goodbos.online/search?q=' . urlencode($q) . '&lang=in');
                    $poolRequests['dramawave'] = fn ($p) => $p->timeout(3.5)->get('https://dramawave.goodbos.online/api/search?q=' . urlencode($q) . "&lang=in&code={$apiKey}&next=1");
                }

                try {
                    $poolResponses = Http::pool(function ($pool) use ($poolRequests) {
                        $built = [];
                        foreach ($poolRequests as $key => $callback) {
                            $built[$key] = $callback($pool->as($key));
                        }
                        return $built;
                    });

                    // 2a. Parse Mirainime
                    if (isset($poolResponses['mirainime']) && $poolResponses['mirainime']->successful()) {
                        try {
                            $crawler = new \Symfony\Component\DomCrawler\Crawler((string) $poolResponses['mirainime']->body());
                            $crawler->filter('#search-grid a, .ctx-card')->each(function (\Symfony\Component\DomCrawler\Crawler $card) use (&$animes) {
                                $href = $card->attr('href') ?? '';
                                if (!str_contains($href, '/anime/')) return;
                                $slug = basename(trim((string) parse_url($href, PHP_URL_PATH), '/'));
                                if (empty($slug)) return;

                                $title = '';
                                $h = $card->filter('h3, h2, .font-medium');
                                if ($h->count()) $title = trim($h->last()->text());
                                $img = $card->filter('img');
                                if (empty($title) && $img->count()) $title = trim($img->attr('alt') ?? '');
                                if (empty($title)) $title = ucwords(str_replace(['-sub-indo', '-subtitle-indonesia', '-'], ['', '', ' '], $slug));

                                $poster = '';
                                if ($img->count()) $poster = $img->attr('src') ?: ($img->attr('data-src') ?: '');

                                $rating = null;
                                $card->filter('span')->each(function (\Symfony\Component\DomCrawler\Crawler $sp) use (&$rating) {
                                    $t = trim($sp->text());
                                    if (preg_match('/[⭐★]\s*([\d\.]+)/u', $t, $m)) {
                                        $rating = $m[1];
                                    }
                                });

                                $status = 'Anime';
                                $card->filter('span')->each(function (\Symfony\Component\DomCrawler\Crawler $sp) use (&$status) {
                                    $t = strtolower(trim($sp->text()));
                                    if ($t === 'ongoing') $status = 'Ongoing';
                                    elseif ($t === 'complete' || $t === 'completed' || $t === 'tamat') $status = 'Tamat';
                                });

                                $animes[] = [
                                    'id' => 'v1:' . $slug,
                                    'title' => $title,
                                    'category' => 'anime',
                                    'type' => 'Anime',
                                    'badge' => 'Anime',
                                    'year' => $status,
                                    'rating' => $rating,
                                    'thumbnail' => $poster,
                                    'url' => url('/anime/' . $slug),
                                    'slug' => $slug,
                                    'source_name' => 'Server 1',
                                ];
                            });
                        } catch (\Throwable $e) {}
                    }

                    // 2b. Parse Otakudesu
                    if (isset($poolResponses['otakudesu']) && $poolResponses['otakudesu']->successful()) {
                        try {
                            $crawler = new \Symfony\Component\DomCrawler\Crawler((string) $poolResponses['otakudesu']->body());
                            $crawler->filter('ul.chivsrc li')->each(function (\Symfony\Component\DomCrawler\Crawler $li) use (&$animes) {
                                $a = $li->filter('h2 a');
                                if (!$a->count()) return;
                                $title = trim($a->text());
                                $href = $a->attr('href') ?? '';
                                $slug = basename(trim((string) parse_url($href, PHP_URL_PATH), '/'));
                                if (empty($slug)) return;

                                $img = $li->filter('img');
                                $poster = $img->count() ? $img->attr('src') : null;

                                $status = 'Anime';
                                $li->filter('.set')->each(function (\Symfony\Component\DomCrawler\Crawler $s) use (&$status) {
                                    $t = trim($s->text());
                                    if (stripos($t, 'Status') !== false) {
                                        $status = trim(str_ireplace(['Status', ':'], '', $t));
                                    }
                                });

                                $rating = null;
                                $li->filter('.set')->each(function (\Symfony\Component\DomCrawler\Crawler $s) use (&$rating) {
                                    $t = trim($s->text());
                                    if (stripos($t, 'Rating') !== false && preg_match('/([\d\.]+)/', $t, $m)) {
                                        $rating = $m[1];
                                    }
                                });

                                $animes[] = [
                                    'id' => 'otaku:' . $slug,
                                    'title' => $title,
                                    'category' => 'anime',
                                    'type' => 'Anime',
                                    'badge' => 'Anime',
                                    'year' => $status,
                                    'rating' => $rating,
                                    'thumbnail' => $poster,
                                    'url' => url('/anime/' . $slug),
                                    'slug' => $slug,
                                    'source_name' => 'Server 2',
                                ];
                            });
                        } catch (\Throwable $e) {}
                    }

                    // 2c. Parse Dracin Responses
                    if ($needDracin) {
                        // DramaBox
                        if (isset($poolResponses['dramabox']) && $poolResponses['dramabox']->successful()) {
                            $items = $poolResponses['dramabox']->json('data') ?? $poolResponses['dramabox']->json('items') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['bookId'] ?? $it['id'] ?? '');
                                    $title = (string)($it['bookName'] ?? $it['name'] ?? '');
                                    if (!$id || !$title) continue;
                                    $eps = isset($it['chapterCount']) ? (int)$it['chapterCount'] : null;
                                    $dracins[] = [
                                        'id' => 'dracin:dramabox:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => $eps ? "{$eps} Eps" : 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['coverWap'] ?? $it['cover'] ?? null,
                                        'url' => url('/dracin/dramabox/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'DramaBox',
                                    ];
                                }
                            }
                        }

                        // ReelBuzz
                        if (isset($poolResponses['reelbuzz']) && $poolResponses['reelbuzz']->successful()) {
                            $items = $poolResponses['reelbuzz']->json('results') ?? $poolResponses['reelbuzz']->json('dramas') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['drama_id'] ?? $it['id'] ?? '');
                                    $title = (string)($it['title'] ?? $it['name'] ?? '');
                                    if (!$id || !$title) continue;
                                    $dracins[] = [
                                        'id' => 'dracin:reelbuzz:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['cover'] ?? $it['poster'] ?? null,
                                        'url' => url('/dracin/reelbuzz/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'ReelBuzz',
                                    ];
                                }
                            }
                        }

                        // SerealPlus
                        if (isset($poolResponses['serealplus']) && $poolResponses['serealplus']->successful()) {
                            $items = $poolResponses['serealplus']->json('data.list') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['contentId'] ?? $it['id'] ?? '');
                                    $title = (string)($it['contentName'] ?? $it['name'] ?? '');
                                    if (!$id || !$title) continue;
                                    $eps = isset($it['chapterNumber']) ? (int)$it['chapterNumber'] : null;
                                    $dracins[] = [
                                        'id' => 'dracin:serealplus:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => $eps ? "{$eps} Eps" : 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['contentCoverUrl'] ?? $it['cover'] ?? null,
                                        'url' => url('/dracin/serealplus/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'Sereal+',
                                    ];
                                }
                            }
                        }

                        // FlexTV
                        if (isset($poolResponses['flextv']) && $poolResponses['flextv']->successful()) {
                            $items = $poolResponses['flextv']->json('data.dramas') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['series_id'] ?? '');
                                    $title = (string)($it['name'] ?? '');
                                    if (!$id || !$title) continue;
                                    $eps = isset($it['sections']) ? (int)$it['sections'] : null;
                                    $dracins[] = [
                                        'id' => 'dracin:flextv:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => $eps ? "{$eps} Eps" : 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['cover'] ?? null,
                                        'url' => url('/dracin/flextv/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'FlexTV',
                                    ];
                                }
                            }
                        }

                        // ReelShort
                        if (isset($poolResponses['reelshort']) && $poolResponses['reelshort']->successful()) {
                            $items = $poolResponses['reelshort']->json('results') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['id'] ?? '');
                                    $title = (string)($it['title'] ?? '');
                                    if (!$id || !$title) continue;
                                    $eps = isset($it['chapters']) ? (int)$it['chapters'] : null;
                                    $dracins[] = [
                                        'id' => 'dracin:reelshort:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => $eps ? "{$eps} Eps" : 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['pic'] ?? null,
                                        'url' => url('/dracin/reelshort/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'ReelShort',
                                    ];
                                }
                            }
                        }

                        // DramaWave
                        if (isset($poolResponses['dramawave']) && $poolResponses['dramawave']->successful()) {
                            $items = $poolResponses['dramawave']->json('list') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    $id = (string)($it['playlet_id'] ?? $it['id'] ?? '');
                                    $title = (string)($it['name'] ?? $it['title'] ?? '');
                                    if (!$id || !$title) continue;
                                    $eps = isset($it['episode_count']) ? (int)$it['episode_count'] : null;
                                    $dracins[] = [
                                        'id' => 'dracin:dramawave:' . $id,
                                        'title' => $title,
                                        'category' => 'dracin',
                                        'type' => 'Dracin',
                                        'badge' => 'Dracin',
                                        'year' => $eps ? "{$eps} Eps" : 'Dracin',
                                        'rating' => null,
                                        'thumbnail' => $it['cover'] ?? null,
                                        'url' => url('/dracin/dramawave/drama/' . $id),
                                        'slug' => $id,
                                        'source_name' => 'DramaWave',
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: Async pool search failed: ' . $e->getMessage());
                }
            }

            // Deduplicate items per category by normalized title
            $dedupe = function (array $list) {
                $seen = [];
                $out = [];
                foreach ($list as $item) {
                    $key = strtolower(preg_replace('/[^a-z0-9]/', '', $item['title'] ?? ''));
                    if (empty($key)) {
                        $out[] = $item;
                        continue;
                    }
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $out[] = $item;
                    }
                }
                return $out;
            };

            $movies = $dedupe($movies);
            $series = $dedupe($series);
            $animes = $dedupe($animes);
            $dracins = $dedupe($dracins);

            // Interleave results if 'all' to give a balanced mix
            if ($category === 'all') {
                $combined = [];
                $maxLen = max(count($movies), count($series), count($animes), count($dracins));
                for ($i = 0; $i < $maxLen; $i++) {
                    if (isset($movies[$i])) $combined[] = $movies[$i];
                    if (isset($series[$i])) $combined[] = $series[$i];
                    if (isset($animes[$i])) $combined[] = $animes[$i];
                    if (isset($dracins[$i])) $combined[] = $dracins[$i];
                }
                return array_slice($combined, 0, $limit);
            }

            $group = match ($category) {
                'movie' => $movies,
                'series' => $series,
                'anime' => $animes,
                'dracin' => $dracins,
                default => array_merge($movies, $series, $animes, $dracins),
            };

            return array_slice($group, 0, $limit);
        });
    }
}
