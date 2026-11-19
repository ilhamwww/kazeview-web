<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chitogenx (DutaMovie21) provider.
 *
 * Sumber: https://chitogenx.com/api/*
 * Tidak butuh API key. Yang perlu dijaga:
 *  - Jangan memakai User-Agent "curl/*" (proxy video memblokirnya).
 *  - URL video bertanda tangan & cepat kedaluwarsa -> jangan di-cache lama.
 *  - Konten 18+ (adult = true) sengaja difilter di lapisan ini.
 */
class ChitogenxService
{
    public const BASE = 'https://chitogenx.com';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    /** Penanda id internal supaya bisa dibedakan dari provider lain. */
    public const ID_PREFIX = 'chito';

    /**
     * Panggilan JSON ke API chitogenx.
     */
    public static function request(string $path, array $query = [], int $timeout = 15): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::UA,
                'Accept' => 'application/json',
                'Referer' => self::BASE . '/',
            ])->timeout($timeout)->get(self::BASE . $path, $query);

            if (!$response->successful()) {
                Log::warning('Chitogenx request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            Log::warning('Chitogenx request error: ' . $e->getMessage(), ['path' => $path]);

            return null;
        }
    }

    /**
     * Konten 18+ disembunyikan untuk sementara.
     */
    public static function isAdult(array $item): bool
    {
        if (!empty($item['adult'])) {
            return true;
        }

        $genres = $item['genres'] ?? [];

        if (!is_array($genres)) {
            return false;
        }

        foreach ($genres as $genre) {
            $name = is_array($genre) ? ($genre['name'] ?? '') : (string) $genre;
            $name = strtolower(trim($name));

            if ($name === 'adult' || $name === 'erotis' || $name === 'erotic' || $name === '18+') {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisasi satu item katalog ke bentuk internal aplikasi.
     */
    public static function mapItem(array $item): ?array
    {
        if (self::isAdult($item)) {
            return null;
        }

        $slug = trim((string) ($item['slug'] ?? ''));

        if ($slug === '') {
            return null;
        }

        $type = ($item['type'] ?? 'film') === 'serial' ? 'series' : 'movie';
        $cover = (string) ($item['cover'] ?? '');
        $year = !empty($item['releaseDate'])
            ? substr((string) $item['releaseDate'], 0, 4)
            : (string) ($item['year'] ?? '');

        $rating = $item['rating'] ?? null;
        $rating = is_numeric($rating) ? number_format((float) $rating, 1) : '0.0';

        $genres = [];
        foreach ((array) ($item['genres'] ?? []) as $genre) {
            $name = is_array($genre) ? ($genre['name'] ?? '') : (string) $genre;
            if ($name !== '') {
                $genres[] = $name;
            }
        }

        return [
            'id' => self::ID_PREFIX . ':' . $slug,
            'title' => (string) ($item['title'] ?? 'Tanpa Judul'),
            'type' => $type,
            'quality' => 'HD',
            'year' => $year,
            'rating' => $rating,
            'comments' => (int) ($item['views'] ?? 0),
            'duration' => '',
            'genre' => implode(', ', $genres),
            'thumbnail' => $cover,
            'banner' => $cover,
            'logo' => null,
            'description' => (string) ($item['description'] ?? ''),
            'video_url' => '',
            'slug' => $slug,
            'tmdb_id' => null,
            'adult' => false,
            'country' => (string) ($item['country'] ?? ''),
            'genres_list' => $genres,
        ];
    }

    /**
     * Beranda: hero + rak-rak.
     */
    public static function home(): array
    {
        return Cache::remember('chito_home', 1800, function () {
            $raw = self::request('/api/home') ?? [];

            $heroes = [];
            foreach ((array) ($raw['hero'] ?? []) as $item) {
                $mapped = self::mapItem($item);
                if ($mapped) {
                    $heroes[] = $mapped;
                }
            }

            $sections = [];
            foreach ((array) ($raw['sections'] ?? []) as $section) {
                $items = [];
                foreach ((array) ($section['items'] ?? []) as $item) {
                    $mapped = self::mapItem($item);
                    if ($mapped) {
                        $items[] = $mapped;
                    }
                }

                if (empty($items)) {
                    continue;
                }

                $sections[] = [
                    'title' => (string) ($section['title'] ?? 'Lainnya'),
                    'slug' => (string) ($section['slug'] ?? 'section'),
                    'section_id' => (string) ($section['id'] ?? ''),
                    'items' => $items,
                ];
            }

            return [
                'heroes' => $heroes,
                'sections' => $sections,
            ];
        });
    }

    /**
     * Kumpulan judul per tipe, dipakai untuk paginasi katalog.
     * Diambil dari beberapa halaman trending lalu di-dedupe.
     */
    public static function pool(string $type, int $pages = 8): array
    {
        $type = $type === 'series' ? 'series' : 'movie';

        return Cache::remember('chito_pool_' . $type . '_' . $pages, 1800, function () use ($type, $pages) {
            $pool = [];
            $seen = [];

            $add = function (array $item) use (&$pool, &$seen, $type) {
                $isSerial = ($item['type'] ?? 'film') === 'serial';

                if ($isSerial !== ($type === 'series')) {
                    return;
                }

                $mapped = self::mapItem($item);

                if (!$mapped || isset($seen[$mapped['slug']])) {
                    return;
                }

                $seen[$mapped['slug']] = true;
                $pool[] = $mapped;
            };

            // 1) Rak kurasi (katalog terbesar: ratusan judul per sumber).
            foreach (['/api/movie', '/api/home'] as $path) {
                $raw = self::request($path, [], 25);

                foreach ((array) ($raw['sections'] ?? []) as $section) {
                    foreach ((array) ($section['items'] ?? []) as $item) {
                        $add($item);
                    }
                }
            }

            // 2) Halaman trending untuk menambah judul yang belum masuk rak.
            for ($page = 0; $page < $pages; $page++) {
                $raw = self::request('/api/trending', ['page' => $page, 'perPage' => 36], 20);

                if (!$raw || empty($raw['items'])) {
                    break;
                }

                foreach ((array) $raw['items'] as $item) {
                    $add($item);
                }

                if (empty($raw['hasMore'])) {
                    break;
                }
            }

            return $pool;
        });
    }

    /**
     * Katalog berpaginasi (bentuk sama seperti StreamingService::getMovies()).
     */
    public static function listing(string $type, int $page = 1, int $limit = 36): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $all = self::pool($type);
        $total = count($all);
        $offset = ($page - 1) * $limit;
        $slice = array_slice($all, $offset, $limit);

        return [
            'data' => $slice,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }

    /**
     * Trending/hero picks per tipe.
     */
    public static function trending(string $type, int $limit = 10): array
    {
        $type = $type === 'series' ? 'series' : 'movie';

        return Cache::remember('chito_trending_' . $type . '_' . $limit, 900, function () use ($type, $limit) {
            $results = [];
            $raw = self::request('/api/trending', ['page' => 0, 'perPage' => 36], 20) ?? [];

            foreach ((array) ($raw['items'] ?? []) as $item) {
                $isSerial = ($item['type'] ?? 'film') === 'serial';

                if ($isSerial !== ($type === 'series')) {
                    continue;
                }

                $mapped = self::mapItem($item);
                if ($mapped) {
                    $results[] = $mapped;
                }

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        });
    }

    /**
     * Detail film/serial dalam bentuk internal.
     */
    public static function detail(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return Cache::remember('chito_detail_' . md5($slug), 1800, function () use ($slug) {
            $raw = self::request('/api/details/' . rawurlencode($slug), [], 20);

            if (!$raw || empty($raw['slug']) || self::isAdult($raw)) {
                return null;
            }

            $cover = (string) ($raw['cover'] ?? '');
            $isSerial = ($raw['type'] ?? 'film') === 'serial';
            $genres = [];

            foreach ((array) ($raw['genres'] ?? []) as $genre) {
                $name = is_array($genre) ? ($genre['name'] ?? '') : (string) $genre;
                if ($name !== '') {
                    $genres[] = ['name' => $name];
                }
            }

            $rating = is_numeric($raw['rating'] ?? null) ? number_format((float) $raw['rating'], 1) : '0.0';
            $year = !empty($raw['releaseDate']) ? substr((string) $raw['releaseDate'], 0, 4) : '';

            $seasons = [];

            foreach ((array) ($raw['seasons'] ?? []) as $season) {
                $number = (int) ($season['season'] ?? 0);
                $episodeNumbers = array_values(array_filter(array_map('intval', (array) ($season['episodes'] ?? []))));

                if ($number < 1) {
                    continue;
                }

                $seasons[] = [
                    'seasonNumber' => $number,
                    'episodeCount' => count($episodeNumbers),
                    'episodeNumbers' => $episodeNumbers,
                ];
            }

            usort($seasons, fn ($a, $b) => $a['seasonNumber'] <=> $b['seasonNumber']);

            $detail = [
                'id' => self::ID_PREFIX . ':' . $raw['slug'],
                'slug' => (string) $raw['slug'],
                'title' => trim((string) ($raw['title'] ?? 'Tanpa Judul')),
                'type' => $isSerial ? 'series' : 'movie',
                'quality' => 'HD',
                'year' => $year,
                'rating' => $rating,
                'voteAverage' => $rating,
                'duration' => '',
                'runtime' => null,
                'genre' => implode(', ', array_column($genres, 'name')),
                'genres' => $genres,
                'thumbnail' => $cover,
                'banner' => $cover,
                'posterPath' => $cover,
                'backdropPath' => $cover,
                'description' => (string) ($raw['description'] ?? ''),
                'overview' => (string) ($raw['description'] ?? ''),
                'video_url' => '',
                'trailer' => (string) ($raw['trailer'] ?? ''),
                'releaseDate' => (string) ($raw['releaseDate'] ?? ''),
                'firstAirDate' => (string) ($raw['releaseDate'] ?? ''),
                'country' => (string) ($raw['country'] ?? ''),
                'tmdbId' => null,
                'tmdb_id' => null,
                'adult' => false,
                'totalEpisodes' => (int) ($raw['totalEpisodes'] ?? 0),
                'seasons' => $seasons,
                // Kunci pelengkap agar view lama tetap aman.
                'originalTitle' => trim((string) ($raw['title'] ?? '')),
                'numberOfSeasons' => count($seasons),
                'status' => 'Released',
                'networks' => [],
                'cast' => [],
                'poster' => $cover,
                'backdrop' => $cover,
                'logoPath' => null,
                'raw_detail' => $raw,
            ];

            if ($isSerial) {
                $default = $seasons[0] ?? null;

                $detail['defaultSeason'] = [
                    'seasonNumber' => $default['seasonNumber'] ?? 1,
                    'episodes' => $default ? self::buildEpisodes($raw['slug'], $default['seasonNumber'], $default['episodeNumbers'], $cover) : [],
                ];
            } else {
                $detail['defaultSeason'] = null;
                $detail['defaultSeasonNumber'] = 1;
            }

            return $detail;
        });
    }

    /**
     * Detail satu musim.
     */
    public static function season(string $slug, int $seasonNumber): ?array
    {
        $detail = self::detail($slug);

        if (!$detail) {
            return null;
        }

        $target = null;

        foreach ((array) ($detail['seasons'] ?? []) as $season) {
            if ((int) $season['seasonNumber'] === $seasonNumber) {
                $target = $season;
                break;
            }
        }

        if (!$target) {
            return [
                'season' => [
                    'seasonNumber' => $seasonNumber,
                    'episodes' => [],
                ],
            ];
        }

        return [
            'season' => [
                'seasonNumber' => $seasonNumber,
                'episodes' => self::buildEpisodes($slug, $seasonNumber, $target['episodeNumbers'], (string) ($detail['posterPath'] ?? '')),
            ],
        ];
    }

    /**
     * Susun objek episode dengan id internal "chito:slug:season:episode".
     */
    private static function buildEpisodes(string $slug, int $seasonNumber, array $numbers, string $cover): array
    {
        $episodes = [];

        foreach ($numbers as $number) {
            if ($number < 1) {
                continue;
            }

            $episodes[] = [
                'id' => self::episodeId($slug, $seasonNumber, $number),
                'episodeNumber' => $number,
                'seasonNumber' => $seasonNumber,
                'name' => 'Episode ' . $number,
                'overview' => '',
                'stillPath' => $cover,
                'runtime' => null,
                'airDate' => null,
                'voteAverage' => 0,
                'servers' => [],
                'mirror_servers' => [],
            ];
        }

        return $episodes;
    }

    public static function episodeId(string $slug, int $seasonNumber, int $episodeNumber): string
    {
        return self::ID_PREFIX . ':' . $slug . ':' . $seasonNumber . ':' . $episodeNumber;
    }

    /**
     * @return array{slug:string,season:?int,episode:?int}|null
     */
    public static function parseEpisodeId(string $id): ?array
    {
        if (!str_starts_with($id, self::ID_PREFIX . ':')) {
            return null;
        }

        $parts = explode(':', $id);

        if (count($parts) === 2) {
            return ['slug' => $parts[1], 'season' => null, 'episode' => null];
        }

        if (count($parts) === 4) {
            return [
                'slug' => $parts[1],
                'season' => (int) $parts[2],
                'episode' => (int) $parts[3],
            ];
        }

        return null;
    }

    /**
     * Ambil URL pemutaran. Jangan di-cache lama: tanda tangannya kedaluwarsa.
     */
    public static function play(string $slug, ?int $seasonNumber = null, ?int $episodeNumber = null): ?array
    {
        $query = [];

        if ($seasonNumber !== null && $episodeNumber !== null) {
            $query['se'] = $seasonNumber;
            $query['ep'] = $episodeNumber;
        }

        $raw = self::request('/api/play/' . rawurlencode($slug), $query, 25);

        if (!$raw || empty($raw['sources'])) {
            return null;
        }

        $sources = [];

        foreach ((array) $raw['sources'] as $source) {
            if (!empty($source['url'])) {
                $sources[] = [
                    'quality' => (int) ($source['quality'] ?? 0),
                    'url' => (string) $source['url'],
                ];
            }
        }

        if (empty($sources)) {
            return null;
        }

        usort($sources, fn ($a, $b) => $b['quality'] <=> $a['quality']);

        $subtitles = [];
        foreach ((array) ($raw['subtitles'] ?? []) as $subtitle) {
            if (empty($subtitle['url']) || empty($subtitle['lang'])) {
                continue;
            }

            $path = (string) $subtitle['url'];
            $absolute = str_starts_with($path, 'http') ? $path : self::BASE . $path;

            $subtitles[] = [
                'lang' => (string) $subtitle['lang'],
                'label' => (string) ($subtitle['label'] ?? strtoupper((string) $subtitle['lang'])),
                'url' => '/api/streaming/subtitle?url=' . rawurlencode($absolute),
                'default' => in_array((string) $subtitle['lang'], ['id', 'en'], true),
            ];
        }

        return [
            'video_url' => $sources[0]['url'],
            'sources' => $sources,
            'subtitles' => $subtitles,
            'title' => (string) ($raw['title'] ?? ''),
            'season' => $raw['season'] ?? null,
            'episode' => $raw['episode'] ?? null,
            'prev' => $raw['prev'] ?? null,
            'next' => $raw['next'] ?? null,
        ];
    }

    /**
     * Pencarian.
     */
    public static function search(string $query, int $page = 1, int $limit = 20, ?string $type = null): array
    {
        $query = trim($query);

        if ($query === '') {
            return ['data' => [], 'pagination' => ['page' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
        }

        $params = [
            'q' => $query,
            'page' => max(1, $page),
            'perPage' => max(1, min(30, $limit)),
        ];

        if ($type === 'movie') {
            $params['type'] = 'film';
        } elseif ($type === 'series') {
            $params['type'] = 'serial';
        }

        $raw = self::request('/api/search', $params, 20) ?? [];
        $results = [];

        foreach ((array) ($raw['items'] ?? []) as $item) {
            $mapped = self::mapItem($item);
            if ($mapped) {
                $results[] = $mapped;
            }
        }

        return [
            'data' => $results,
            'pagination' => [
                'page' => (int) ($raw['page'] ?? $page),
                'limit' => (int) ($raw['perPage'] ?? $limit),
                'total' => count($results),
                'totalPages' => !empty($raw['hasMore']) ? (int) ($raw['page'] ?? $page) + 1 : (int) ($raw['page'] ?? $page),
            ],
        ];
    }

    /**
     * Drakor: serial dengan negara Korea.
     */
    public static function koreanDrama(int $page = 1, int $limit = 36): array
    {
        $all = array_values(array_filter(self::pool('series'), function ($item) {
            $country = strtolower((string) ($item['country'] ?? ''));

            return str_contains($country, 'korea');
        }));

        $total = count($all);
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'data' => array_slice($all, ($page - 1) * $limit, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }

    /**
     * Koleksi = rak kurasi dari beranda.
     */
    public static function collections(): array
    {
        $home = self::home();
        $result = [];

        foreach ((array) ($home['sections'] ?? []) as $section) {
            $items = (array) ($section['items'] ?? []);
            $first = $items[0] ?? null;

            if (!$first) {
                continue;
            }

            $result[] = [
                'id' => (string) ($section['section_id'] ?? ''),
                'slug' => (string) ($section['slug'] ?? ''),
                'title' => (string) ($section['title'] ?? 'Koleksi'),
                'description' => 'Kurasi ' . count($items) . ' judul pilihan.',
                'kind' => 'section',
                'poster' => $first['thumbnail'] ?? null,
                'banner' => $first['banner'] ?? null,
                'itemCount' => count($items),
                'childCount' => 0,
                'yearRange' => '',
                'firstReleaseDate' => null,
                'lastReleaseDate' => null,
            ];
        }

        return $result;
    }

    /**
     * Isi satu koleksi (rak) dengan paginasi.
     */
    public static function collectionDetail(string $slug, int $page = 1, int $limit = 60): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $section = null;

        foreach ((array) (self::home()['sections'] ?? []) as $candidate) {
            if ((string) ($candidate['slug'] ?? '') === $slug) {
                $section = $candidate;
                break;
            }
        }

        if (!$section || empty($section['section_id'])) {
            return null;
        }

        $raw = self::request('/api/section/' . rawurlencode((string) $section['section_id']), [
            'page' => max(1, $page),
            'perPage' => min(50, max(1, $limit)),
        ], 25);

        $items = [];

        foreach ((array) ($raw['items'] ?? []) as $item) {
            $mapped = self::mapItem($item);

            if (!$mapped) {
                continue;
            }

            $isSeries = $mapped['type'] === 'series';

            $items[] = [
                'id' => $mapped['id'],
                'slug' => $mapped['slug'],
                'title' => $mapped['title'],
                'poster' => $mapped['thumbnail'],
                'thumbnail' => $mapped['thumbnail'],
                'year' => $mapped['year'],
                'rating' => $mapped['rating'],
                'type' => $isSeries ? 'tv_series' : 'movie',
                'is_series' => $isSeries,
                'url' => $isSeries
                    ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $mapped['slug']], panel: 'streaming')
                    : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $mapped['slug']], panel: 'streaming'),
            ];
        }

        $all = (array) ($section['items'] ?? []);

        return [
            'collection' => [
                'id' => (string) ($section['section_id'] ?? ''),
                'slug' => $slug,
                'title' => (string) ($section['title'] ?? 'Koleksi'),
                'description' => 'Kurasi ' . count($all) . ' judul pilihan.',
                'kind' => 'section',
                'poster' => $all[0]['thumbnail'] ?? null,
                'banner' => $all[0]['banner'] ?? null,
                'itemCount' => (int) ($raw['total'] ?? count($all)),
                'childCount' => 0,
                'yearRange' => '',
                'firstReleaseDate' => null,
                'lastReleaseDate' => null,
            ],
            'sections' => [
                [
                    'title' => (string) ($section['title'] ?? 'Koleksi'),
                    'items' => $items,
                ],
            ],
            'pagination' => [
                'page' => (int) ($raw['page'] ?? $page),
                'limit' => (int) ($raw['perPage'] ?? $limit),
                'total' => (int) ($raw['total'] ?? count($items)),
                'totalPages' => !empty($raw['hasMore']) ? (int) ($raw['page'] ?? $page) + 1 : 1,
            ],
        ];
    }
}
