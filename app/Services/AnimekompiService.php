<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Provider anime Animekompi (Indocast).
 *
 * Stream yang dikembalikan sudah berupa MP4 lewat proxy Indocast, mendukung
 * Range dan mengirim CORS, jadi bisa diputar langsung tanpa relay tambahan.
 *
 * Endpoint: /home, /list (indeks A-Z), /genres, /genre-detail?genre=&page=,
 * /schedule, /detail?path=, /play?episode_id=.
 */
class AnimekompiService
{
    protected const BASE = 'https://indocast.site/api/animekompi';

    /** Genre yang tidak ditampilkan sama sekali (konten dewasa). */
    public const BLOCKED_GENRES = ['hentai', 'erotica', 'adult', 'yaoi', 'yuri'];

    public const KINDS = ['TV', 'Movie', 'ONA', 'OVA', 'Special'];

    public const STATUSES = [
        'ongoing' => 'Sedang Tayang',
        'completed' => 'Tamat',
        'upcoming' => 'Segera',
        'hiatus' => 'Hiatus',
    ];

    public const SORTS = [
        'terbaru' => 'Terbaru',
        'rating' => 'Rating Tertinggi',
        'az' => 'Judul A-Z',
        'terlama' => 'Terlama',
    ];

    public static function enabled(): bool
    {
        return trim((string) env('INDOCAST_API_KEY', '')) !== '';
    }

    protected static function key(): string
    {
        return trim((string) env('INDOCAST_API_KEY', ''));
    }

    public static function api(string $path, array $query = [], int $ttl = 900, int $timeout = 30): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $cacheKey = 'ak_' . md5($path . '|' . http_build_query($query));

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

    public static function detailUrl(string $path): string
    {
        return '/animek/' . rawurlencode($path);
    }

    public static function watchUrl(string $path, string $episodeId): string
    {
        return '/animek/' . rawurlencode($path) . '/' . rawurlencode($episodeId);
    }

    /**
     * API kadang mengirim list sebagai string repr Python: "['Action', 'Drama']".
     */
    protected static function parseList(mixed $value): array
    {
        if (is_array($value)) {
            $out = [];

            foreach ($value as $v) {
                $out[] = is_array($v) ? (string) ($v['name'] ?? '') : (string) $v;
            }

            return array_values(array_filter(array_map('trim', $out)));
        }

        if (!is_string($value) || trim($value) === '' || trim($value) === '[]') {
            return [];
        }

        preg_match_all("/'([^']+)'|\"([^\"]+)\"/u", $value, $m);

        return array_values(array_filter(array_map(
            fn ($a, $b) => trim($a !== '' ? $a : $b),
            $m[1],
            $m[2]
        )));
    }

    /**
     * Sebagian cover dikirim rusak: "https://host-a.comhttps://host-b.com/x.jpg".
     * Ambil URL terakhir yang utuh.
     */
    protected static function coverUrl(string $url): string
    {
        $url = trim($url);
        $pos = strrpos($url, 'http');

        return $pos > 0 ? substr($url, $pos) : $url;
    }

    protected static function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    protected static function rating(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value > 0 && (float) $value <= 10
            ? round((float) $value, 2)
            : null;
    }

    protected static function episodeLabel(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        return ctype_digit($raw) ? (string) (int) $raw : $raw;
    }

    /**
     * Item Animekompi -> bentuk katalog KZSTRM.
     */
    public static function mapItem(array $item): ?array
    {
        $path = trim((string) ($item['path'] ?? ''));
        $title = trim((string) ($item['title'] ?? ''));

        if ($path === '' || $title === '' || !preg_match('/^[A-Za-z0-9-]+$/', $path)) {
            return null;
        }

        // Judul "Uncen"/18+ tidak ditampilkan.
        if (stripos($title, 'uncen') !== false || stripos($title, '18+') !== false) {
            return null;
        }

        $genres = self::parseList($item['genres'] ?? []);

        foreach ($genres as $g) {
            if (in_array(Str::slug($g), self::BLOCKED_GENRES, true)) {
                return null;
            }
        }

        $episodeId = trim((string) ($item['episode_id'] ?? ''));
        $status = strtolower(trim((string) ($item['status'] ?? '')));
        $cover = ImageProxyService::wrap(self::coverUrl((string) ($item['cover_url'] ?? '')));

        return [
            'path' => $path,
            'slug' => $path,
            'title' => $title,
            'thumbnail' => $cover,
            'banner' => $cover,
            'rating' => self::rating($item['rating'] ?? null),
            'year' => substr(trim((string) ($item['release_year'] ?? '')), 0, 4),
            'episodes' => (string) ($item['jumlah_episode'] ?? ''),
            'episode' => self::episodeLabel((string) ($item['episode'] ?? '')),
            'episode_id' => $episodeId,
            'episode_url' => $episodeId !== '' && preg_match('/^[A-Za-z0-9-]+$/', $episodeId)
                ? self::watchUrl($path, $episodeId)
                : null,
            'kind' => trim((string) ($item['type'] ?? $item['sub_type'] ?? '')),
            'status' => isset(self::STATUSES[$status]) ? $status : '',
            'genres' => $genres,
            'desc' => Str::limit(self::cleanText((string) ($item['deskripsi'] ?? '')), 260),
            'type' => 'anime',
            'provider_badge' => '',
            'url' => self::detailUrl($path),
        ];
    }

    protected static function mapList(array $list): array
    {
        $out = [];

        foreach ($list as $item) {
            $mapped = is_array($item) ? self::mapItem($item) : null;

            if ($mapped && !isset($out[$mapped['path']])) {
                $out[$mapped['path']] = $mapped;
            }
        }

        return array_values($out);
    }

    /**
     * Rak beranda: slider, trending hari ini, update terbaru.
     */
    public static function home(): array
    {
        return Cache::remember('ak_home_v2', 1800, function () {
            $json = self::api('/home', [], 1800);
            $data = (array) ($json['data'] ?? []);

            return [
                'sliders' => self::mapList((array) ($data['sliders'] ?? [])),
                'trending' => self::mapList((array) ($data['trending_today'] ?? [])),
                'latest' => self::mapList((array) ($data['latest_updates'] ?? [])),
            ];
        });
    }

    /**
     * Daftar genre, urut dari yang paling banyak judulnya.
     */
    public static function genres(): array
    {
        return Cache::remember('ak_genres_v1', 21600, function () {
            $json = self::api('/genres', [], 21600);
            $out = [];

            foreach ((array) ($json['data']['genres_list'] ?? []) as $g) {
                $id = strtolower(trim((string) ($g['genre_id'] ?? '')));

                if ($id === '' || !preg_match('/^[a-z0-9-]+$/', $id) || in_array($id, self::BLOCKED_GENRES, true)) {
                    continue;
                }

                $out[] = [
                    'id' => $id,
                    'name' => trim((string) ($g['name'] ?? $id)),
                    'count' => (int) ($g['count'] ?? 0),
                ];
            }

            usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

            return $out;
        });
    }

    public static function genreName(string $id): ?string
    {
        foreach (self::genres() as $g) {
            if ($g['id'] === $id) {
                return $g['name'];
            }
        }

        return null;
    }

    /**
     * Nama genre (dari detail) -> [name, id] supaya bisa ditautkan.
     */
    public static function genreLinks(array $names): array
    {
        $byName = [];

        foreach (self::genres() as $g) {
            $byName[strtolower($g['name'])] = $g['id'];
        }

        $out = [];

        foreach ($names as $name) {
            $id = $byName[strtolower($name)] ?? Str::slug($name);

            if ($id !== '' && !in_array($id, self::BLOCKED_GENRES, true)) {
                $out[] = ['name' => $name, 'id' => $id];
            }
        }

        return $out;
    }

    /**
     * Satu halaman judul untuk sebuah genre (24 per halaman dari API).
     */
    public static function genreItems(string $genre, int $page = 1): array
    {
        $empty = ['name' => null, 'page' => $page, 'has_next' => false, 'items' => []];

        if (!preg_match('/^[a-z0-9-]+$/', $genre) || in_array($genre, self::BLOCKED_GENRES, true)) {
            return $empty;
        }

        $json = self::api('/genre-detail', ['genre' => $genre, 'page' => max(1, $page)], 3600);
        $data = (array) ($json['data'] ?? []);

        if (empty($data)) {
            return $empty;
        }

        $hasNext = $data['has_next_page'] ?? false;

        return [
            'name' => (string) ($data['genre_name'] ?? self::genreName($genre) ?? $genre),
            'page' => (int) ($data['current_page'] ?? $page),
            'has_next' => $hasNext === true || strtolower((string) $hasNext) === 'true',
            'items' => self::mapList((array) ($data['animes'] ?? [])),
        ];
    }

    /**
     * Jadwal tayang Senin-Minggu.
     */
    public static function schedule(): array
    {
        return Cache::remember('ak_schedule_v1', 1800, function () {
            $json = self::api('/schedule', [], 1800);
            $days = is_array($json) ? ($json['data'] ?? (isset($json[0]) ? $json : [])) : [];
            $out = [];

            foreach ((array) $days as $day) {
                $items = [];

                foreach ((array) ($day['animes'] ?? []) as $raw) {
                    $mapped = self::mapItem((array) $raw);

                    if (!$mapped) {
                        continue;
                    }

                    $ts = (int) ($raw['release_timestamp'] ?? 0);
                    $mapped['time'] = $ts > 0
                        ? Carbon::createFromTimestamp($ts, 'Asia/Jakarta')->format('H.i')
                        : '';
                    $items[] = $mapped;
                }

                usort($items, fn ($a, $b) => strcmp($a['time'], $b['time']));

                $out[] = [
                    'day' => (string) ($day['day'] ?? ''),
                    'items' => $items,
                ];
            }

            return $out;
        });
    }

    public static function todayName(): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][now('Asia/Jakarta')->dayOfWeek];
    }

    /**
     * Judul dari genre yang diblokir, untuk disaring dari katalog A-Z
     * (item /list tidak membawa genre).
     */
    protected static function blockedPaths(): array
    {
        return Cache::remember('ak_blocked_paths_v1', 21600, function () {
            $paths = [];

            foreach (self::BLOCKED_GENRES as $genre) {
                for ($page = 1; $page <= 5; $page++) {
                    $json = self::api('/genre-detail', ['genre' => $genre, 'page' => $page], 21600);
                    $data = (array) ($json['data'] ?? []);

                    foreach ((array) ($data['animes'] ?? []) as $a) {
                        if (!empty($a['path'])) {
                            $paths[(string) $a['path']] = true;
                        }
                    }

                    $next = $data['has_next_page'] ?? false;

                    if (!($next === true || strtolower((string) $next) === 'true')) {
                        break;
                    }
                }
            }

            return $paths;
        });
    }

    /**
     * Seluruh judul: rak beranda + indeks A-Z.
     */
    public static function catalog(): array
    {
        return Cache::remember('ak_catalog_v2', 1800, function () {
            $all = [];

            // /list mengembalikan indeks A-Z: tiap baris berisi grup huruf
            // dengan daftar 'animes'. Parameter page tidak memengaruhi hasil.
            $list = self::api('/list', [], 1800);
            $groups = is_array($list) ? ($list['data'] ?? (isset($list[0]) ? $list : [])) : [];

            foreach ((array) $groups as $group) {
                foreach ((array) ($group['animes'] ?? []) as $item) {
                    $mapped = self::mapItem((array) $item);

                    if ($mapped) {
                        $all[$mapped['path']] = $mapped;
                    }
                }
            }

            // Rak beranda punya data episode terbaru; jangan timpa data A-Z
            // yang lebih lengkap, cukup lengkapi field yang kosong.
            $home = self::home();

            foreach (array_merge($home['sliders'], $home['trending'], $home['latest']) as $item) {
                if (!isset($all[$item['path']])) {
                    $all[$item['path']] = $item;
                    continue;
                }

                foreach ($item as $k => $v) {
                    if (($all[$item['path']][$k] ?? null) === '' || ($all[$item['path']][$k] ?? null) === null || ($all[$item['path']][$k] ?? null) === []) {
                        $all[$item['path']][$k] = $v;
                    }
                }
            }

            $blocked = self::blockedPaths();

            return array_values(array_filter($all, fn ($item) => !isset($blocked[$item['path']])));
        });
    }

    public static function search(string $query): array
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return [];
        }

        return array_values(array_filter(
            self::catalog(),
            fn ($item) => str_contains(mb_strtolower($item['title']), $needle)
        ));
    }

    /**
     * Filter + urut + paginasi katalog A-Z.
     *
     * @param  array{q?:string,kind?:string,status?:string,year?:string,sort?:string}  $f
     */
    public static function browse(array $f, int $page = 1, int $perPage = 36): array
    {
        $items = self::catalog();
        $q = mb_strtolower(trim((string) ($f['q'] ?? '')));
        $kind = (string) ($f['kind'] ?? '');
        $status = (string) ($f['status'] ?? '');
        $year = (string) ($f['year'] ?? '');
        $sort = (string) ($f['sort'] ?? 'terbaru');

        $items = array_filter($items, function ($i) use ($q, $kind, $status, $year) {
            if ($q !== '' && !str_contains(mb_strtolower($i['title']), $q)) {
                return false;
            }
            if ($kind !== '' && strcasecmp($i['kind'], $kind) !== 0) {
                return false;
            }
            if ($status !== '' && $i['status'] !== $status) {
                return false;
            }
            if ($year !== '' && $i['year'] !== $year) {
                return false;
            }

            return true;
        });

        $items = array_values($items);

        usort($items, match ($sort) {
            'rating' => fn ($a, $b) => [$b['rating'] ?? 0, $b['year']] <=> [$a['rating'] ?? 0, $a['year']],
            'az' => fn ($a, $b) => strnatcasecmp($a['title'], $b['title']),
            'terlama' => fn ($a, $b) => [$a['year'] ?: '9999', $a['title']] <=> [$b['year'] ?: '9999', $b['title']],
            default => fn ($a, $b) => [$b['year'], $b['rating'] ?? 0] <=> [$a['year'], $a['rating'] ?? 0],
        });

        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        return [
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * Tahun rilis yang tersedia, terbaru dulu.
     */
    public static function years(): array
    {
        return Cache::remember('ak_years_v1', 21600, function () {
            $years = [];

            foreach (self::catalog() as $i) {
                if (preg_match('/^(19|20)\d{2}$/', $i['year'])) {
                    $years[$i['year']] = true;
                }
            }

            $years = array_keys($years);
            rsort($years);

            return array_map('strval', $years);
        });
    }

    /**
     * Rak turunan katalog untuk beranda anime.
     */
    public static function rails(): array
    {
        return Cache::remember('ak_rails_v1', 1800, function () {
            return [
                'ongoing' => self::browse(['status' => 'ongoing', 'sort' => 'rating'], 1, 18)['items'],
                'top' => self::browse(['sort' => 'rating'], 1, 18)['items'],
                'movies' => self::browse(['kind' => 'Movie', 'sort' => 'rating'], 1, 18)['items'],
                'total' => count(self::catalog()),
            ];
        });
    }

    /**
     * Detail anime + daftar episode (urut naik: episode 1 dulu).
     */
    public static function detail(string $path, int $ttl = 1800): ?array
    {
        $path = trim($path);

        if ($path === '' || !preg_match('/^[A-Za-z0-9-]+$/', $path)) {
            return null;
        }

        $json = self::api('/detail', ['path' => $path], $ttl);

        if (!$json || (int) ($json['code'] ?? -1) !== 0) {
            return null;
        }

        $data = (array) ($json['data'] ?? []);

        if (empty($data['title'])) {
            return null;
        }

        $genres = self::parseList($data['genres'] ?? []);

        foreach ($genres as $g) {
            if (in_array(Str::slug($g), self::BLOCKED_GENRES, true)) {
                return null;
            }
        }

        $episodes = [];

        foreach ((array) ($data['episode_list'] ?? []) as $ep) {
            $id = trim((string) ($ep['episode_id'] ?? ''));

            if ($id === '' || !preg_match('/^[A-Za-z0-9-]+$/', $id)) {
                continue;
            }

            $number = self::episodeLabel((string) ($ep['episode_number'] ?? ''));

            $episodes[] = [
                'id' => $id,
                'number' => $number,
                'title' => (string) ($ep['title'] ?? ''),
                'date' => (string) ($ep['release_date'] ?? ''),
                'url' => self::watchUrl($path, $id),
            ];
        }

        // Pastikan urut naik walau API berubah urutan.
        usort($episodes, function ($a, $b) {
            if (is_numeric($a['number']) && is_numeric($b['number'])) {
                return (float) $a['number'] <=> (float) $b['number'];
            }

            return strnatcasecmp($a['number'], $b['number']);
        });

        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $duration = trim((string) ($data['duration'] ?? ''));
        $season = trim((string) ($data['season'] ?? ''));
        $studio = trim((string) ($data['studio'] ?? ''));

        return [
            'path' => $path,
            'url' => self::detailUrl($path),
            'title' => (string) $data['title'],
            'synopsis' => self::cleanText((string) ($data['synopsis'] ?? '')),
            'poster' => ImageProxyService::wrap(self::coverUrl((string) ($data['cover_url'] ?? ''))),
            'rating' => self::rating($data['rating'] ?? null),
            'views' => (int) ($data['view'] ?? 0),
            'status' => $status,
            'status_label' => self::STATUSES[$status] ?? ucfirst($status),
            'kind' => trim((string) ($data['type'] ?? '')),
            'season' => strcasecmp($season, 'unknown') === 0 ? '' : $season,
            'duration' => strcasecmp($duration, 'unknown') === 0 ? '' : $duration,
            'studio' => strcasecmp($studio, 'unknown') === 0 ? '' : $studio,
            'release_date' => (string) ($data['release_date'] ?? ''),
            'year' => substr((string) ($data['release_date'] ?? ''), 0, 4),
            'genres' => $genres,
            'genre_links' => self::genreLinks($genres),
            'total_episodes' => (int) ($data['total_episodes'] ?? 0) ?: count($episodes),
            'episodes' => $episodes,
        ];
    }

    /**
     * Rekomendasi: API mengirim 'recommendations' kosong, jadi ambil dari
     * genre utama judul ini.
     */
    public static function similar(array $media, int $limit = 12): array
    {
        $out = [];

        foreach (array_slice($media['genre_links'] ?? [], 0, 2) as $g) {
            foreach (self::genreItems($g['id'], 1)['items'] as $item) {
                if ($item['path'] !== $media['path'] && !isset($out[$item['path']])) {
                    $out[$item['path']] = $item;
                }
            }

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_slice(array_values($out), 0, $limit);
    }

    /**
     * Sumber putar satu episode. Server pertama yang punya URL dipakai.
     */
    public static function play(string $episodeId): ?array
    {
        $episodeId = trim($episodeId);

        if ($episodeId === '') {
            return null;
        }

        $json = self::api('/play', ['episode_id' => $episodeId], 120, 45);

        if (!$json || (int) ($json['code'] ?? -1) !== 0) {
            return null;
        }

        $data = (array) ($json['data'] ?? []);
        $servers = [];

        foreach ((array) ($data['servers'] ?? []) as $server) {
            $url = trim((string) ($server['url'] ?? ''));

            if ($url === '' || !str_starts_with($url, 'http')) {
                continue;
            }

            $servers[] = [
                'name' => (string) ($server['name'] ?? 'Server'),
                'url' => str_starts_with($url, 'https://indocast.site/api/animekompi/proxy')
                    ? url('/api/av?u=' . ImageProxyService::encode($url))
                    : $url,
            ];
        }

        if (empty($servers)) {
            return null;
        }

        return [
            'episode_title' => (string) ($data['title'] ?? ''),
            'video_url' => $servers[0]['url'],
            'servers' => $servers,
            'subtitles' => [],
        ];
    }

    /**
     * Dipanggil scheduler: bangun ulang semua cache yang dipakai halaman anime.
     */
    public static function warm(): array
    {
        foreach (['ak_home_v2', 'ak_catalog_v2', 'ak_schedule_v1', 'ak_rails_v1', 'ak_years_v1'] as $key) {
            Cache::forget($key);
        }

        $home = self::home();
        $genres = self::genres();
        $schedule = self::schedule();
        $catalog = self::catalog();
        self::years();
        self::rails();

        return [
            'catalog' => count($catalog),
            'trending' => count($home['trending']),
            'genres' => count($genres),
            'schedule' => array_sum(array_map(fn ($d) => count($d['items']), $schedule)),
        ];
    }
}
