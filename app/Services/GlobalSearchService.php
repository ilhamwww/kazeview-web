<?php

namespace App\Services;

use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlobalSearchService
{
    /**
     * Unified Global Search across all KZSTRM sources:
     * - Movies & TV Series (Server 1 / Server 2)
     * - Anime (Anime V2 / Anime V3)
     * - Dracin (Drama China Pendek)
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

        $cacheKey = 'kz_global_search_' . md5(strtolower($q) . '_' . $category . '_' . $limit);

        return Cache::remember($cacheKey, 1800, function () use ($q, $category, $limit) {
            $movies = [];
            $series = [];
            $animes = [];
            $dracins = [];

            // 1. Fetch Movies & TV Series
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

            // 2. Fetch Anime (Anime V2 & Anime V3)
            if (in_array($category, ['all', 'anime'], true)) {
                // 2a. Otakudesu (Anime V2)
                try {
                    $otakuScraper = new OtakudesuScraperService();
                    $otakuItems = $otakuScraper->searchAnime($q);
                    foreach ($otakuItems as $otaku) {
                        $rawUrl = $otaku['url'] ?? '';
                        $parts = array_values(array_filter(explode('/', trim((string) parse_url($rawUrl, PHP_URL_PATH), '/'))));
                        $slug = end($parts) ?: '';
                        if (empty($slug)) continue;

                        $animes[] = [
                            'id' => 'v2:' . $slug,
                            'title' => (string) ($otaku['title'] ?? 'Anime'),
                            'category' => 'anime',
                            'type' => 'Anime',
                            'badge' => 'Anime',
                            'year' => (string) ($otaku['status'] ?? 'Anime'),
                            'rating' => !empty($otaku['rating']) && (float)$otaku['rating'] > 0 ? number_format((float)$otaku['rating'], 1) : null,
                            'thumbnail' => $otaku['thumbnail'] ?? null,
                            'url' => url('/anime-v2/' . $slug),
                            'slug' => $slug,
                            'source_name' => 'Anime V2',
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: Otakudesu search failed: ' . $e->getMessage());
                }

                // 2b. Anoboy (Anime V3)
                try {
                    $anoboyScraper = new AnoboyScraperService();
                    $anoboyItems = $anoboyScraper->searchAnime($q);
                    foreach ($anoboyItems as $ano) {
                        $slug = $ano['slug'] ?? '';
                        if (empty($slug) && !empty($ano['url'])) {
                            $parts = array_values(array_filter(explode('/', trim((string) parse_url($ano['url'], PHP_URL_PATH), '/'))));
                            $slug = end($parts) ?: '';
                        }
                        if (empty($slug)) continue;

                        $animes[] = [
                            'id' => 'v3:' . $slug,
                            'title' => (string) ($ano['title'] ?? 'Anime'),
                            'category' => 'anime',
                            'type' => 'Anime',
                            'badge' => 'Anime',
                            'year' => (string) ($ano['episode'] ?? $ano['status'] ?? 'Anime'),
                            'rating' => null,
                            'thumbnail' => $ano['thumbnail'] ?? null,
                            'url' => url('/anime-v3/' . $slug),
                            'slug' => $slug,
                            'source_name' => 'Anime V3',
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: Anoboy search failed: ' . $e->getMessage());
                }
            }

            // 3. Fetch Dracin (Drama China Pendek)
            if (in_array($category, ['all', 'dracin'], true)) {
                $setting = ScraperSetting::getInstance();
                $apiKey = $setting->dramabuzz_api_key ?? '';
                $dbDracins = [];
                $rbDracins = [];
                $spDracins = [];

                // 3a. DramaBox Search
                try {
                    $dbUrl = "https://dramabox.goodbos.online/api/dramabox/search?query=" . urlencode($q) . "&lang=in";
                    $dbRes = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                        'timeout' => 5,
                    ])->withHeaders([
                        'x-token' => $apiKey,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get($dbUrl);

                    if ($dbRes->successful()) {
                        $rawJson = $dbRes->json() ?: [];
                        $items = $rawJson['data'] ?? $rawJson['items'] ?? $rawJson['results'] ?? [];
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                if (!is_array($it)) continue;
                                $dramaId = (string) ($it['bookId'] ?? $it['id'] ?? '');
                                $title = (string) ($it['bookName'] ?? $it['name'] ?? '');
                                if (empty($dramaId) || empty($title)) continue;

                                $episodes = isset($it['chapterCount']) ? (int) $it['chapterCount'] : null;

                                $dbDracins[] = [
                                    'id' => 'dracin:dramabox:' . $dramaId,
                                    'title' => $title,
                                    'category' => 'dracin',
                                    'type' => 'Dracin',
                                    'badge' => 'Dracin',
                                    'year' => $episodes ? "{$episodes} Eps" : 'Dracin',
                                    'rating' => null,
                                    'thumbnail' => $it['coverWap'] ?? $it['cover'] ?? null,
                                    'url' => url('/dracin/dramabox/drama/' . $dramaId),
                                    'slug' => $dramaId,
                                    'source_name' => 'DramaBox',
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: DramaBox search failed: ' . $e->getMessage());
                }

                // 3b. ReelBuzz Search
                try {
                    $rbUrl = "https://reelbuzz.goodbos.online/api/search?q=" . urlencode($q) . "&code=" . $apiKey;
                    $rbRes = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                        'timeout' => 5,
                    ])->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get($rbUrl);

                    if ($rbRes->successful()) {
                        $rawJson = $rbRes->json() ?: [];
                        $items = $rawJson['results'] ?? $rawJson['dramas'] ?? [];
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                if (!is_array($it)) continue;
                                $dramaId = (string) ($it['drama_id'] ?? $it['id'] ?? '');
                                $title = (string) ($it['title'] ?? $it['name'] ?? '');
                                if (empty($dramaId) || empty($title)) continue;

                                $rbDracins[] = [
                                    'id' => 'dracin:reelbuzz:' . $dramaId,
                                    'title' => $title,
                                    'category' => 'dracin',
                                    'type' => 'Dracin',
                                    'badge' => 'Dracin',
                                    'year' => 'Dracin',
                                    'rating' => null,
                                    'thumbnail' => $it['cover'] ?? $it['poster'] ?? null,
                                    'url' => url('/dracin/reelbuzz/drama/' . $dramaId),
                                    'slug' => $dramaId,
                                    'source_name' => 'ReelBuzz',
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: ReelBuzz search failed: ' . $e->getMessage());
                }

                // 3c. Sereal+ Search
                try {
                    $spUrl = "https://serealplus.goodbos.online/api/search?q=" . urlencode($q) . "&lang=id&page=1&code=" . $apiKey;
                    $spRes = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                        'timeout' => 5,
                    ])->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get($spUrl);

                    if ($spRes->successful()) {
                        $rawJson = $spRes->json() ?: [];
                        $items = $rawJson['data']['list'] ?? [];
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                if (!is_array($it)) continue;
                                $dramaId = (string) ($it['contentId'] ?? $it['id'] ?? '');
                                $title = (string) ($it['contentName'] ?? $it['name'] ?? '');
                                if (empty($dramaId) || empty($title)) continue;

                                $episodes = isset($it['chapterNumber']) ? (int) $it['chapterNumber'] : (isset($it['chapterNum']) ? (int) $it['chapterNum'] : null);

                                $spDracins[] = [
                                    'id' => 'dracin:serealplus:' . $dramaId,
                                    'title' => $title,
                                    'category' => 'dracin',
                                    'type' => 'Dracin',
                                    'badge' => 'Dracin',
                                    'year' => $episodes ? "{$episodes} Eps" : 'Dracin',
                                    'rating' => null,
                                    'thumbnail' => $it['contentCoverUrl'] ?? $it['cover'] ?? null,
                                    'url' => url('/dracin/serealplus/drama/' . $dramaId),
                                    'slug' => $dramaId,
                                    'source_name' => 'Sereal+',
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlobalSearch: Sereal+ search failed: ' . $e->getMessage());
                }

                $maxDracin = max(count($dbDracins), count($rbDracins), count($spDracins));
                for ($i = 0; $i < $maxDracin; $i++) {
                    if (isset($dbDracins[$i])) $dracins[] = $dbDracins[$i];
                    if (isset($rbDracins[$i])) $dracins[] = $rbDracins[$i];
                    if (isset($spDracins[$i])) $dracins[] = $spDracins[$i];
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

            // Otherwise return the filtered group
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
