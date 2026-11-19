<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Provider Filmbox (Indocast) — pengganti utama Chitogenx.
 *
 * Kontennya sama (Moviebox/OneRoom, CDN aoneroom/hakunaymatata), tapi diambil
 * langsung dari Indocast sehingga tidak ada markup reseller.
 *
 * Catatan penting:
 * - getplay WAJIB memakai dua parameter: subjectId DAN detailPath. Tanpa
 *   detailPath, upstream selalu membalas "Stream video kosong".
 * - slug publik KZSTRM = detailPath tanpa akhiran hash, supaya URL lama tetap
 *   jalan. contoh: sleeping-with-the-enemy-4YnIn9NqY66 -> sleeping-with-the-enemy
 */
class FilmboxService
{
    protected const BASE = 'https://indocast.site/api/filmbox';

    public const ID_PREFIX = 'fb';

    public static function enabled(): bool
    {
        return trim((string) env('INDOCAST_API_KEY', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) env('INDOCAST_API_KEY', ''));
    }

    public static function request(string $path, array $query = [], int $ttl = 900, int $timeout = 30): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'fb_' . md5($path . '|' . http_build_query($query));

        $result = Cache::remember($cacheKey, $ttl, function () use ($path, $query, $timeout) {
            try {
                $res = Http::withHeaders([
                    'x-api-key' => self::key(),
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
                ])->timeout($timeout)->get(self::BASE . $path, $query);

                if (!$res->ok()) {
                    return null;
                }

                $json = $res->json();

                return is_array($json) ? $json : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        if ($result === null) {
            Cache::forget($cacheKey);
        }

        return $result;
    }

    /**
     * detailPath -> slug publik (buang akhiran hash).
     */
    public static function slugFromPath(string $detailPath): string
    {
        $detailPath = trim($detailPath);

        if ($detailPath === '') {
            return '';
        }

        $slug = preg_replace('/-[A-Za-z0-9]{8,16}$/', '', $detailPath);

        return $slug ?: $detailPath;
    }

    /**
     * Item Filmbox -> bentuk katalog KZSTRM (kompatibel dengan ChitogenxService).
     */
    public static function mapItem(array $item): ?array
    {
        $path = trim((string) ($item['detailPath'] ?? ''));

        if ($path === '') {
            return null;
        }

        $slug = self::slugFromPath($path);
        $type = ((int) ($item['subjectType'] ?? 1)) === 2 ? 'series' : 'movie';
        $cover = (string) ($item['cover_url'] ?? '');
        $year = !empty($item['releaseDate']) ? substr((string) $item['releaseDate'], 0, 4) : '';
        $rating = is_numeric($item['rating'] ?? null) ? number_format((float) $item['rating'], 1) : '0.0';

        $genres = [];
        foreach (preg_split('/\s*,\s*/', (string) ($item['genre'] ?? '')) as $g) {
            if ($g !== '') {
                $genres[] = $g;
            }
        }

        return [
            'id' => self::ID_PREFIX . ':' . $path,
            'title' => (string) ($item['title'] ?? 'Tanpa Judul'),
            'type' => $type,
            'quality' => 'HD',
            'year' => $year,
            'rating' => $rating,
            'comments' => (int) ($item['view'] ?? 0),
            'duration' => '',
            'genre' => implode(', ', $genres),
            'thumbnail' => $cover,
            'banner' => $cover,
            'logo' => null,
            'description' => (string) ($item['description'] ?? ''),
            'video_url' => '',
            'trailer' => (string) ($item['trailer_url'] ?? ''),
            'slug' => $slug,
            'detail_path' => $path,
            'subject_id' => (string) ($item['subjectId'] ?? ''),
            'tmdb_id' => null,
            'adult' => false,
            'country' => (string) ($item['country'] ?? ''),
            'genres_list' => $genres,
        ];
    }

    /**
     * Semua item dari satu payload (hero + sections).
     */
    protected static function collect(?array $payload): array
    {
        $data = $payload['data'] ?? $payload ?? [];
        $items = [];

        foreach ((array) ($data['hero_banners'] ?? []) as $item) {
            $mapped = self::mapItem((array) $item);
            if ($mapped) {
                $items[$mapped['detail_path']] = $mapped;
            }
        }

        foreach ((array) ($data['content_sections'] ?? []) as $section) {
            foreach ((array) ($section['items'] ?? []) as $item) {
                $mapped = self::mapItem((array) $item);
                if ($mapped) {
                    $items[$mapped['detail_path']] = $mapped;
                }
            }
        }

        foreach ((array) ($data['items'] ?? []) as $item) {
            $mapped = self::mapItem((array) $item);
            if ($mapped) {
                $items[$mapped['detail_path']] = $mapped;
            }
        }

        return array_values($items);
    }

    /**
     * Beranda: hero + rak, bentuknya sama dengan ChitogenxService::home().
     */
    public static function home(): array
    {
        return Cache::remember('fb_home_v1', 1800, function () {
            $raw = self::request('/home', [], 1800);
            $data = $raw['data'] ?? [];

            $heroes = [];
            foreach ((array) ($data['hero_banners'] ?? []) as $item) {
                $mapped = self::mapItem((array) $item);
                if ($mapped) {
                    $heroes[] = $mapped;
                }
            }

            $sections = [];
            foreach ((array) ($data['content_sections'] ?? []) as $section) {
                $items = [];
                $seen = [];

                foreach ((array) ($section['items'] ?? []) as $item) {
                    $mapped = self::mapItem((array) $item);

                    if ($mapped && !isset($seen[$mapped['detail_path']])) {
                        $seen[$mapped['detail_path']] = true;
                        $items[] = $mapped;
                    }
                }

                if (empty($items)) {
                    continue;
                }

                $sections[] = [
                    'title' => (string) ($section['section_title'] ?? 'Lainnya'),
                    'slug' => self::slugFromPath((string) ($section['section_path'] ?? 'section')),
                    'section_id' => (string) ($section['section_id'] ?? ''),
                    'items' => $items,
                ];
            }

            return ['heroes' => $heroes, 'sections' => $sections];
        });
    }

    /**
     * Kumpulan judul per tipe untuk paginasi.
     */
    public static function pool(string $type, int $pages = 6): array
    {
        $type = $type === 'series' ? 'series' : 'movie';

        return Cache::remember('fb_pool_' . $type, 1800, function () use ($type) {
            $all = [];
            $seen = [];

            $ingest = function (array $items) use (&$all, &$seen, $type) {
                foreach ($items as $item) {
                    if ($item['type'] !== $type || isset($seen[$item['detail_path']])) {
                        continue;
                    }

                    $seen[$item['detail_path']] = true;
                    $all[] = $item;
                }
            };

            $ingest(self::collect(self::request('/home', [], 1800)));
            $ingest(self::collect(self::request('/trending', [], 1800)));

            if ($type === 'movie') {
                for ($page = 1; $page <= 3; $page++) {
                    $json = self::request('/movie', ['page' => $page], 1800);

                    if (!$json) {
                        break;
                    }

                    $ingest(self::collect($json));
                }
            } else {
                // Serial diambil dari rak beranda (tidak ada endpoint khusus).
                foreach ((array) ((self::request('/home', [], 1800)['data']['content_sections'] ?? [])) as $section) {
                    $ingest(self::collect(['data' => ['content_sections' => [$section]]]));
                }
            }

            return $all;
        });
    }

    /**
     * Rak-rak Indonesia (section ID sama dengan yang dipakai Moviebox).
     */
    public static function indonesiaSections(): array
    {
        return [
            '6528093688173053896', // Trending Indonesia
            '5283462032510044280', // Drama Indonesia Terkini
            '5848753831881965888', // Horor Indonesia
            '7749480172839487888', // Indonesia Comedy
            '3528002473103362040', // Horor & Kriminal
            '3770510954220470720', // Family Time
            '6379320635445106408', // Marriage Stories
            '1004503178596994616', // Sejarah
            '325004885257240608',  // Love Triangle
        ];
    }

    /**
     * Katalog Indonesia: gabungan rak Indonesia + filter negara.
     */
    public static function indonesiaItems(): array
    {
        return Cache::remember('fb_indonesia_items_v1', 1800, function () {
            $items = [];

            foreach (self::indonesiaSections() as $sectionId) {
                for ($page = 1; $page <= 3; $page++) {
                    $json = self::request('/section', ['id' => $sectionId, 'page' => $page], 1800);

                    if (!$json) {
                        break;
                    }

                    $found = 0;
                    foreach ((array) ($json['data']['items'] ?? []) as $item) {
                        $mapped = self::mapItem((array) $item);

                        if (!$mapped) {
                            continue;
                        }

                        if (stripos((string) ($item['country'] ?? ''), 'indonesia') === false) {
                            continue;
                        }

                        $found++;
                        $items[$mapped['detail_path']] = $mapped;
                    }

                    if ($found === 0) {
                        break;
                    }
                }
            }

            // Lengkapi dari pool utama (negara Indonesia).
            foreach (array_merge(self::pool('movie'), self::pool('series')) as $item) {
                if (stripos((string) ($item['country'] ?? ''), 'indonesia') !== false) {
                    $items[$item['detail_path']] ??= $item;
                }
            }

            return array_values($items);
        });
    }

    public static function indonesiaCatalog(string $category = 'all', int $page = 1, int $limit = 24, string $search = ''): array
    {
        $items = self::indonesiaItems();
        $needle = strtolower(trim($search));

        $items = array_values(array_filter($items, function ($item) use ($category, $needle) {
            $okType = match ($category) {
                'movie' => $item['type'] === 'movie',
                'series' => $item['type'] === 'series',
                default => true,
            };

            if (!$okType) {
                return false;
            }

            if ($needle !== '' && !str_contains(strtolower($item['title']), $needle)) {
                return false;
            }

            return true;
        }));

        $total = count($items);
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'data' => array_slice($items, ($page - 1) * $limit, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
                'hasMore' => ($page * $limit) < $total,
            ],
        ];
    }

    public static function listing(string $type, int $page = 1, int $limit = 36): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $all = self::pool($type);
        $total = count($all);

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

    public static function trending(string $type, int $limit = 10): array
    {
        $type = $type === 'series' ? 'series' : 'movie';
        $pool = self::pool($type);

        return array_slice($pool, 0, $limit);
    }

    /**
     * Cari detailPath dari slug publik (atau detailPath penuh).
     */
    public static function resolvePath(string $slugOrPath): ?string
    {
        $slugOrPath = trim($slugOrPath);

        if ($slugOrPath === '') {
            return null;
        }

        return Cache::remember('fb_path_' . md5($slugOrPath), 3600, function () use ($slugOrPath) {
            $map = self::slugIndex();

            if (isset($map[$slugOrPath])) {
                return $map[$slugOrPath];
            }

            // Kalau yang dikirim detailPath penuh, pakai apa adanya.
            if (str_contains($slugOrPath, '-') && preg_match('/-[A-Za-z0-9]{8,16}$/', $slugOrPath)) {
                return $slugOrPath;
            }

            return null;
        });
    }

    /**
     * Peta slug publik -> detailPath.
     */
    public static function slugIndex(): array
    {
        return Cache::remember('fb_slug_index_v1', 1800, function () {
            $map = [];

            foreach (array_merge(self::pool('movie'), self::pool('series')) as $item) {
                $map[$item['slug']] ??= $item['detail_path'];
            }

            return $map;
        });
    }

    /**
     * Detail judul, bentuknya mengikuti ChitogenxService::detail().
     */
    public static function detail(string $slugOrPath): ?array
    {
        $path = self::resolvePath($slugOrPath);

        if (!$path) {
            return null;
        }

        return Cache::remember('fb_detail_' . md5($path), 1800, function () use ($path) {
            $raw = self::request('/details', ['detailPath' => $path], 1800);

            $data = $raw['data'] ?? null;

            if (!$data || empty($data['title'])) {
                return null;
            }

            $isSeries = ((int) ($data['subjectType'] ?? 1)) === 2;
            $genres = [];
            foreach (preg_split('/\s*,\s*/', (string) ($data['genre'] ?? '')) as $g) {
                if ($g !== '') {
                    $genres[] = ['name' => $g];
                }
            }

            $seasons = [];
            foreach ((array) ($data['seasons_list'] ?? []) as $season) {
                $number = (int) ($season['season'] ?? 0);
                $episodes = array_values(array_map('intval', (array) ($season['episodes'] ?? [])));

                if ($number < 1 || empty($episodes)) {
                    continue;
                }

                $seasons[] = [
                    'seasonNumber' => $number,
                    'episodeCount' => count($episodes),
                    'episodeNumbers' => $episodes,
                ];
            }

            $slug = self::slugFromPath($path);
            $cover = (string) ($data['cover_url'] ?? '');

            return [
                'id' => self::ID_PREFIX . ':' . $path,
                'slug' => $slug,
                'detail_path' => $path,
                'subject_id' => (string) ($data['subjectId'] ?? ''),
                'title' => trim((string) $data['title']),
                'type' => $isSeries ? 'series' : 'movie',
                'quality' => 'HD',
                'year' => !empty($data['releaseDate']) ? substr((string) $data['releaseDate'], 0, 4) : '',
                'rating' => is_numeric($data['rating'] ?? null) ? number_format((float) $data['rating'], 1) : '0.0',
                'genre' => (string) ($data['genre'] ?? ''),
                'genres' => $genres,
                'description' => (string) ($data['description'] ?? ''),
                'thumbnail' => $cover,
                'banner' => $cover,
                'logo' => null,
                'trailer' => (string) ($data['trailer_url'] ?? ''),
                'country' => (string) ($data['country'] ?? ''),
                'duration' => '',
                'seasons' => $seasons,
                'defaultSeason' => $seasons[0] ?? null,
                ];
        });
    }

    /**
     * Daftar episode satu musim.
     */
    public static function season(string $slugOrPath, int $seasonNumber): ?array
    {
        $detail = self::detail($slugOrPath);

        if (!$detail || empty($detail['seasons'])) {
            return null;
        }

        $season = null;
        foreach ($detail['seasons'] as $s) {
            if ((int) $s['seasonNumber'] === (int) $seasonNumber) {
                $season = $s;
                break;
            }
        }

        $season ??= $detail['seasons'][0];

        $episodes = [];
        foreach ((array) ($season['episodeNumbers'] ?? []) as $number) {
            $episodes[] = [
                'episodeNumber' => (int) $number,
                'title' => 'Episode ' . $number,
                'slug' => $detail['slug'],
                'seasonNumber' => (int) $season['seasonNumber'],
            ];
        }

        return [
            'season' => [
                'seasonNumber' => (int) $season['seasonNumber'],
                'episodeCount' => count($episodes),
                'episodes' => $episodes,
            ],
        ];
    }

    /**
     * Stream: butuh subjectId + detailPath (+ se/ep untuk serial).
     */
    public static function play(string $slugOrPath, ?int $seasonNumber = null, ?int $episodeNumber = null): ?array
    {
        $detail = self::detail($slugOrPath);

        if (!$detail || empty($detail['subject_id']) || empty($detail['detail_path'])) {
            return null;
        }

        $query = [
            'subjectId' => $detail['subject_id'],
            'detailPath' => $detail['detail_path'],
        ];

        if ($seasonNumber !== null && $episodeNumber !== null) {
            $query['season'] = $seasonNumber;
            $query['episode'] = $episodeNumber;
        } else {
            // Film tunggal: upstream hanya membalas stream kalau se & ep = 0.
            $query['se'] = 0;
            $query['ep'] = 0;
        }

        $raw = self::request('/getplay', $query, 300, 60);

        if (!$raw || (int) ($raw['code'] ?? -1) !== 0) {
            return null;
        }

        $data = $raw['data'] ?? [];
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $sources = [];

        foreach ((array) ($data['hls'] ?? []) as $entry) {
            $url = (string) ($entry['url_proxy'] ?? $entry['url'] ?? '');

            if ($url === '') {
                continue;
            }

            $sources[] = [
                'quality' => (int) preg_replace('/\D/', '', (string) ($entry['resolutions'] ?? 0)),
                'url' => $url,
            ];
        }

        // Beberapa judul hanya menyediakan file MP4 tunggal.
        if (empty($sources)) {
            $url = (string) ($data['vid_url_proxy'] ?? $data['vid_url'] ?? '');

            if ($url !== '') {
                $sources[] = [
                    'quality' => (int) ($data['quality'] ?? 0),
                    'url' => $url,
                ];
            }
        }

        if (empty($sources)) {
            return null;
        }

        usort($sources, fn ($a, $b) => $b['quality'] <=> $a['quality']);

        $subtitles = [];
        foreach ((array) ($data['subtitles'] ?? []) as $sub) {
            $url = (string) ($sub['url'] ?? $sub['url_proxy'] ?? '');

            if ($url === '') {
                continue;
            }

            $lang = strtolower((string) ($sub['lang'] ?? $sub['language'] ?? ''));
            $subtitles[] = [
                'lang' => $lang === 'in_id' ? 'id' : $lang,
                'label' => $lang === 'id' ? 'Indonesia' : strtoupper($lang),
                'url' => $url,
                'path' => $url,
                'default' => $lang === 'id',
            ];
        }

        return [
            'video_url' => $sources[0]['url'],
            'sources' => $sources,
            'subtitles' => $subtitles,
        ];
    }

    /**
     * Pencarian lokal dari pool (endpoint search Filmbox kosong).
     */
    public static function search(string $query, int $page = 1, int $limit = 20): array
    {
        $needle = strtolower(trim($query));

        if ($needle === '') {
            return ['data' => [], 'pagination' => ['page' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
        }

        $all = array_merge(self::pool('movie'), self::pool('series'));
        $matches = array_values(array_filter($all, fn ($i) => str_contains(strtolower($i['title']), $needle)));
        $total = count($matches);

        return [
            'data' => array_slice($matches, ($page - 1) * $limit, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }
}
