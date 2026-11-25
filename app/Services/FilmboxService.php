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
        return trim((string) config('services.indocast.key', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) config('services.indocast.key', ''));
    }

    /**
     * Cache dengan stale-while-revalidate: data lama tetap disajikan seketika
     * sementara pembaruan berjalan di belakang, jadi pengguna tidak pernah
     * menunggu rebuild penuh.
     */
    protected static function rememberFlexible(string $key, callable $callback, int $fresh = 1800, int $stale = 21600)
    {
        try {
            return Cache::flexible($key, [$fresh, $stale], $callback);
        } catch (\Throwable $e) {
            return Cache::remember($key, $stale, $callback);
        }
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
     * Bersihkan judul: buang emoji, nama provider, dan spasi berlebih.
     */
    public static function cleanText(string $text): string
    {
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{2190}-\x{21FF}\x{2700}-\x{27BF}]/u', '', $text) ?? $text;
        $text = str_ireplace(['moviebox', 'one room', 'onerom'], 'Katalog', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text) ?? $text;

        return trim($text, " \t\n\r\0\x0B-–—•|");
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
            'title' => self::cleanText((string) ($item['title'] ?? 'Tanpa Judul')),
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
            // URL final per item supaya katalog tidak menebak rute dari provider.
            'url' => ($type === 'series' ? '/series/' : '/movie/') . $slug,
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
        return self::rememberFlexible('fb_home_v1', function () {
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
            $usedTitles = [];

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

                $title = self::cleanText((string) ($section['section_title'] ?? 'Lainnya'));

                // Jangan tampilkan rak dengan judul sama dua kali.
                if (empty($items) || $title === '' || isset($usedTitles[mb_strtolower($title)])) {
                    continue;
                }

                $usedTitles[mb_strtolower($title)] = true;

                $sections[] = [
                    'title' => $title,
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

        return self::rememberFlexible('fb_pool_' . $type, function () use ($type) {
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
            $ingest(self::collect(self::request('/trending', [], 21600)));

            if ($type === 'movie') {
                for ($page = 1; $page <= 3; $page++) {
                    $json = self::request('/movie', ['page' => $page], 21600);

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
    /**
     * Buang pembungkus proxy indocast kalau isinya URL CDN langsung,
     * supaya browser tidak pernah melihat domain perantara.
     */
    /**
     * Cari item Filmbox berdasarkan slug publik (tanpa akhiran acak).
     */
    public static function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $pools = array_merge(
            self::pool('movie'),
            self::pool('series'),
            self::indonesiaItems()
        );

        foreach ($pools as $item) {
            $itemSlug = (string) ($item['slug'] ?? '');
            $itemPath = (string) ($item['detail_path'] ?? '');

            if ($itemSlug === $slug || $itemPath === $slug) {
                return $item;
            }
        }

        // Terakhir: cari lewat judul (slug → kata).
        $keyword = str_replace('-', ' ', $slug);
        $result = self::search($keyword);

        foreach ((array) ($result['data'] ?? []) as $item) {
            if (($item['slug'] ?? '') === $slug || ($item['detail_path'] ?? '') === $slug) {
                return $item;
            }
        }

        return null;
    }

    protected static function isHlsUrl(string $url): bool
    {
        return str_contains(strtolower(urldecode(urldecode($url))), '.m3u8');
    }

    public static function unwrapProxy(string $url): string
    {
        if ($url === '' || !str_contains($url, 'indocast.site')) {
            return $url;
        }

        if (preg_match('~[?&]url=([^&]+)~', $url, $m)) {
            $inner = urldecode($m[1]);
            if (str_starts_with($inner, 'http://') || str_starts_with($inner, 'https://')) {
                return $inner;
            }
        }

        return $url;
    }

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
        return self::rememberFlexible('fb_indonesia_items_v1', function () {
            $items = [];

            foreach (self::indonesiaSections() as $sectionId) {
                for ($page = 1; $page <= 3; $page++) {
                    $json = self::request('/section', ['id' => $sectionId, 'page' => $page], 21600);

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
                        // Kunci per slug supaya judul yang punya dua detailPath tidak tampil dua kali.
                        $items[$mapped['slug'] ?: $mapped['detail_path']] = $mapped;
                    }

                    if ($found === 0) {
                        break;
                    }
                }
            }

            // Lengkapi dari pool utama (negara Indonesia).
            foreach (array_merge(self::pool('movie'), self::pool('series')) as $item) {
                if (stripos((string) ($item['country'] ?? ''), 'indonesia') !== false) {
                    $items[$item['slug'] ?: $item['detail_path']] ??= $item;
                }
            }

            return array_values($items);
        });
    }

    /**
     * Genre yang benar-benar ada di katalog Indonesia, diurutkan dari yang terbanyak.
     */
    public static function indonesiaGenres(int $limit = 18): array
    {
        $counts = [];

        foreach (self::indonesiaItems() as $item) {
            foreach (explode(',', (string) ($item['genre'] ?? '')) as $genre) {
                $genre = trim($genre);
                if ($genre === '') {
                    continue;
                }
                $key = strtolower($genre);
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        arsort($counts);

        $out = [];
        foreach (array_slice($counts, 0, $limit, true) as $slug => $count) {
            if (in_array($slug, ['hentai', 'erotica', 'adult', 'yaoi', 'yuri'], true)) {
                continue;
            }
            $out[] = ['id' => $slug, 'label' => ucwords($slug), 'count' => $count];
        }

        return $out;
    }

    /**
     * Katalog Filmbox 18+ (/api/filmbox/18plus).
     *
     * @return array{hero:array,sections:array,trending:array}
     */
    public static function adultHome(): array
    {
        $res = self::request('/18plus', [], 900, 40);
        $data = $res['data'] ?? [];

        $map = function (array $items): array {
            $out = [];

            foreach ($items as $raw) {
                $mapped = self::mapItem((array) $raw);

                if ($mapped) {
                    // CDN aoneroom menolak hotlink dari browser, jadi poster
                    // selalu lewat proxy gambar milik sendiri.
                    if (! empty($mapped['thumbnail'])) {
                        $mapped['thumbnail'] = \App\Services\ImageProxyService::wrap((string) $mapped['thumbnail']);
                    }
                    if (! empty($mapped['banner'])) {
                        $mapped['banner'] = \App\Services\ImageProxyService::wrap((string) $mapped['banner']);
                    }

                    $out[] = $mapped;
                }
            }

            return $out;
        };

        $sections = [];

        foreach ((array) ($data['content_sections'] ?? []) as $section) {
            $items = $map((array) ($section['items'] ?? []));

            if (! $items) {
                continue;
            }

            $sections[] = [
                'title' => (string) ($section['section_title'] ?? 'Lainnya'),
                'items' => $items,
            ];
        }

        $trending = [];
        $tRes = self::request('/18plus/trending', [], 900, 40);
        foreach ((array) ($tRes['data']['items'] ?? []) as $raw) {
            $mapped = self::mapItem((array) $raw);

            if ($mapped) {
                if (! empty($mapped['thumbnail'])) {
                    $mapped['thumbnail'] = \App\Services\ImageProxyService::wrap((string) $mapped['thumbnail']);
                }
                $trending[] = $mapped;
            }
        }

        return [
            'hero' => $map((array) ($data['hero_banners'] ?? [])),
            'sections' => $sections,
            'trending' => $trending,
        ];
    }

    public static function indonesiaCatalog(string $category = 'all', int $page = 1, int $limit = 24, string $search = '', string $genre = ''): array
    {
        $items = self::indonesiaItems();
        $needle = strtolower(trim($search));
        $genreNeedle = strtolower(trim($genre));

        $items = array_values(array_filter($items, function ($item) use ($category, $needle, $genreNeedle) {
            $okType = match ($category) {
                'movie' => $item['type'] === 'movie',
                'series' => $item['type'] === 'series',
                default => true,
            };

            if (!$okType) {
                return false;
            }

            // Filter tahun: "2026", "2025", "2024", atau "classic" (2023 ke bawah).
            if (preg_match('/^\d{4}$/', $category)) {
                if ((string) ($item['year'] ?? '') !== $category) {
                    return false;
                }
            } elseif ($category === 'classic') {
                $year = (int) ($item['year'] ?? 0);
                if ($year === 0 || $year > 2023) {
                    return false;
                }
            }

            if ($needle !== '' && !str_contains(strtolower($item['title']), $needle)) {
                return false;
            }

            if ($genreNeedle !== '') {
                $genres = array_map('trim', explode(',', strtolower((string) ($item['genre'] ?? ''))));
                if (!in_array($genreNeedle, $genres, true)) {
                    return false;
                }
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
     * Top 10 dari rak trending Filmbox (campuran film & serial).
     */
    public static function trendingNow(int $limit = 10): array
    {
        $json = self::request('/trending', [], 1800);
        $data = is_array($json) ? ($json['data'] ?? $json) : null;

        // Bentuk respons: { data: { items: [...], pager: {...} } }
        $rows = is_array($data) && isset($data['items']) && is_array($data['items'])
            ? $data['items']
            : (is_array($data) && array_is_list($data) ? $data : null);

        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        $seen = [];

        foreach ($rows as $row) {
            $mapped = self::mapItem((array) $row);

            if (!$mapped || isset($seen[$mapped['slug']])) {
                continue;
            }

            $seen[$mapped['slug']] = true;
            $mapped['is_series'] = ($mapped['type'] ?? '') === 'series';
            $mapped['thumbnail'] = \App\Services\ImageProxyService::wrap((string) $mapped['thumbnail']);
            $out[] = $mapped;

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
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

                $episodeList = [];
                foreach ($episodes as $number2) {
                    $episodeList[] = [
                        'id' => self::ID_PREFIX . ':' . $path . ':' . $number . ':' . $number2,
                        'episodeNumber' => (int) $number2,
                        'title' => 'Episode ' . $number2,
                        'seasonNumber' => $number,
                    ];
                }

                $seasons[] = [
                    'seasonNumber' => $number,
                    'episodeCount' => count($episodes),
                    'episodeNumbers' => $episodes,
                    // Halaman serial membaca kunci 'episodes'.
                    'episodes' => $episodeList,
                ];
            }

            $slug = self::slugFromPath($path);
            $cover = (string) ($data['cover_url'] ?? '');
            $title = trim((string) $data['title']);

            $description = trim((string) ($data['description'] ?? ''));

            // Sebagian judul hanya mengulang judulnya di kolom deskripsi.
            if ($description === '' || mb_strlen($description) < 60 || strcasecmp($description, $title) === 0) {
                $rich = WeTVService::descriptionForTitle($title);

                if ($rich) {
                    $description = $rich;
                }
            }

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
                'description' => $description,
                'overview' => $description,
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

        // Upstream hanya membaca "se"/"ep". Kalau dikirim "season"/"episode",
        // permintaannya diabaikan dan yang dibalas selalu episode 1.
        $query = [
            'subjectId' => $detail['subject_id'],
            'detailPath' => $detail['detail_path'],
            'se' => $seasonNumber ?? 0,
            'ep' => $episodeNumber ?? 0,
        ];

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
            // Pakai proxy upstream (token CDN tetap valid), lalu sembunyikan
            // di balik token domain sendiri.
            $url = (string) ($entry['url_proxy'] ?? $entry['url'] ?? '');

            if ($url === '') {
                continue;
            }

            $sources[] = [
                'quality' => (int) preg_replace('/\D/', '', (string) ($entry['resolutions'] ?? 0)),
                'url' => StreamProxy::url($url, self::isHlsUrl($url) ? 'm3u8' : 'mp4'),
            ];
        }

        // Beberapa judul hanya menyediakan file MP4 tunggal.
        if (empty($sources)) {
            $url = (string) ($data['vid_url_proxy'] ?? $data['vid_url'] ?? '');

            if ($url !== '') {
                $sources[] = [
                    'quality' => (int) ($data['quality'] ?? 0),
                    'url' => StreamProxy::url($url, self::isHlsUrl($url) ? 'm3u8' : 'mp4'),
                ];
            }
        }

        if (empty($sources)) {
            return null;
        }

        usort($sources, fn ($a, $b) => $b['quality'] <=> $a['quality']);

        $subtitles = [];
        foreach ((array) ($data['subtitles'] ?? []) as $sub) {
            $url = (string) ($sub['url_proxy'] ?? $sub['url'] ?? '');

            if ($url === '') {
                continue;
            }

            $lang = strtolower((string) ($sub['lang'] ?? $sub['language'] ?? ''));
            $lang = str_replace(['_', '-'], '', $lang);

            // Sumber memakai kode campur: in_id, id_id, ind, in.
            $lang = match ($lang) {
                'in', 'ind', 'idid', 'inid' => 'id',
                'eng', 'enus' => 'en',
                default => $lang,
            };

            $label = match ($lang) {
                'id' => 'Indonesia',
                'en' => 'English',
                default => strtoupper((string) ($sub['label'] ?? $lang)),
            };

            $subtitles[] = [
                'lang' => $lang,
                'label' => $label,
                'url' => StreamProxy::url($url, self::isHlsUrl($url) ? 'm3u8' : 'mp4'),
                'path' => StreamProxy::url($url),
                'default' => false,
            ];
        }

        return [
            'video_url' => $sources[0]['url'],
            'sources' => $sources,
            'subtitles' => $subtitles,
        ];
    }

    /**
     * Pencarian di katalog Filmbox (POST + JSON {"keyword": "..."}).
     */
    public static function searchRemote(string $query, int $ttl = 900): array
    {
        $query = trim($query);

        if ($query === '' || !self::enabled()) {
            return [];
        }

        $cacheKey = 'fb_search_' . md5(mb_strtolower($query));

        $payload = Cache::remember($cacheKey, $ttl, function () use ($query) {
            try {
                $res = Http::withHeaders([
                    'x-api-key' => self::key(),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
                ])->timeout(30)->post(self::BASE . '/search', ['keyword' => $query]);

                return $res->ok() ? $res->json() : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        $items = (array) (($payload['data']['items'] ?? []));

        // Halaman kedua menambah cakupan (mis. varian tahun berbeda).
        $second = Cache::remember($cacheKey . '_p2', $ttl, function () use ($query) {
            try {
                $res = Http::withHeaders([
                    'x-api-key' => self::key(),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36',
                ])->timeout(30)->post(self::BASE . '/search', ['keyword' => $query, 'page' => 2]);

                return $res->ok() ? $res->json() : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        $items = array_merge($items, (array) (($second['data']['items'] ?? [])));

        if (empty($items)) {
            Cache::forget($cacheKey);
        }

        $out = [];
        $seen = [];

        foreach ($items as $item) {
            $mapped = self::mapItem((array) $item);

            if ($mapped && !isset($seen[$mapped['detail_path']])) {
                $seen[$mapped['detail_path']] = true;
                $out[] = $mapped;
            }
        }

        // Judul kembar (mis. beberapa "Sugar Baby") tidak boleh berbagi URL:
        // yang bertabrakan memakai detailPath penuh sebagai slug.
        $counts = [];

        foreach ($out as $item) {
            $counts[$item['slug']] = ($counts[$item['slug']] ?? 0) + 1;
        }

        foreach ($out as &$item) {
            if (($counts[$item['slug']] ?? 0) > 1) {
                $item['slug'] = $item['detail_path'];
                $item['url'] = ($item['type'] === 'series' ? '/series/' : '/movie/') . $item['detail_path'];
            }
        }
        unset($item);

        return $out;
    }

    /**
     * Pencarian lokal dari pool (cadangan bila API gagal).
     */
    public static function search(string $query, int $page = 1, int $limit = 20): array
    {
        $needle = strtolower(trim($query));

        if ($needle === '') {
            return ['data' => [], 'pagination' => ['page' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0]];
        }

        $remote = self::searchRemote($query);

        if (!empty($remote)) {
            $total = count($remote);
            $page = max(1, $page);
            $limit = max(1, $limit);

            return [
                'data' => array_slice($remote, ($page - 1) * $limit, $limit),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => (int) ceil($total / $limit),
                ],
            ];
        }

        $all = array_merge(self::pool('movie'), self::pool('series'), self::indonesiaItems());
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
