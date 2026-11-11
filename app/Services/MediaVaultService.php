<?php

namespace App\Services;

use App\Models\MediaVaultItem;
use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MediaVaultService
{
    /**
     * Ambil data Movies dengan pagination super cepat dari database lokal.
     */
    public static function getMovies(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $cacheKey = "media_vault_movies_p{$page}_l{$limit}_s{$sort}";

        return Cache::remember($cacheKey, 300, function () use ($page, $limit, $sort) {
            $query = MediaVaultItem::where('type', 'movie')
                ->where('is_published', true);

            if ($sort === 'rating') {
                $query->orderByDesc('rating')->orderByDesc('upstream_created_at');
            } elseif ($sort === 'year') {
                $query->orderByDesc('year')->orderByDesc('upstream_created_at');
            } else {
                $query->orderByDesc('upstream_created_at')->orderByDesc('id');
            }

            $items = $query->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(fn($m) => self::mapToMediaFormat($m))
                ->all();

            // Jika di database lokal belum ada (misal page lebih tinggi belum ter-scrape), ambil on-demand dari IDLIX & auto-save ke vault!
            if (empty($items)) {
                try {
                    $live = StreamingService::getMovies($page, $limit, $sort);
                    if (!empty($live['data'])) {
                        // Background simpan ke vault agar kunjungan berikutnya instan
                        foreach ($live['data'] as $item) {
                            self::upsertIdlixItem($item, 'movie');
                        }
                        return $live;
                    }
                } catch (\Throwable $e) {
                    Log::warning("On-demand fetch movies page {$page} failed: " . $e->getMessage());
                }
            }

            // Total pages dihitung berdasarkan estimasi IDLIX (~3.850 halaman) atau minimal page saat ini
            $totalEst = max(7700, ($page + 5) * $limit);
            $totalPages = (int)ceil($totalEst / $limit);

            return [
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalEst,
                    'totalPages' => $totalPages,
                ],
            ];
        });
    }

    /**
     * Ambil data TV Series dengan pagination super cepat dari database lokal.
     */
    public static function getSeries(int $page = 1, int $limit = 36, string $sort = 'createdAt'): array
    {
        $cacheKey = "media_vault_series_p{$page}_l{$limit}_s{$sort}";

        return Cache::remember($cacheKey, 300, function () use ($page, $limit, $sort) {
            $query = MediaVaultItem::where('type', 'series')
                ->where('is_published', true);

            if ($sort === 'rating') {
                $query->orderByDesc('rating')->orderByDesc('upstream_created_at');
            } elseif ($sort === 'year') {
                $query->orderByDesc('year')->orderByDesc('upstream_created_at');
            } else {
                $query->orderByDesc('upstream_created_at')->orderByDesc('id');
            }

            $items = $query->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(fn($s) => self::mapToMediaFormat($s))
                ->all();

            // Jika di database lokal belum ada (misal page lebih tinggi belum ter-scrape), ambil on-demand dari IDLIX & auto-save ke vault!
            if (empty($items)) {
                try {
                    $live = StreamingService::getSeries($page, $limit, $sort);
                    if (!empty($live['data'])) {
                        foreach ($live['data'] as $item) {
                            self::upsertIdlixItem($item, 'series');
                        }
                        return $live;
                    }
                } catch (\Throwable $e) {
                    Log::warning("On-demand fetch series page {$page} failed: " . $e->getMessage());
                }
            }

            $totalEst = max(5000, ($page + 5) * $limit);
            $totalPages = (int)ceil($totalEst / $limit);

            return [
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalEst,
                    'totalPages' => $totalPages,
                ],
            ];
        });
    }

    /**
     * Ambil trending items untuk slider/banner atas.
     */
    public static function getTrending(string $type = 'movie', int $limit = 10): array
    {
        $cacheKey = "media_vault_trending_{$type}_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($type, $limit) {
            // Ambil trending resmi jika ada flag is_trending
            $items = MediaVaultItem::where('type', $type)
                ->where('is_published', true)
                ->where('is_trending', true)
                ->orderByDesc('rating')
                ->take($limit)
                ->get()
                ->map(fn($m) => self::mapToMediaFormat($m))
                ->all();

            // Jika kurang dari limit, panggil StreamingService::getTrendingMovies/Series sebagai fallback
            if (count($items) < $limit) {
                try {
                    $liveTrending = $type === 'series'
                        ? StreamingService::getTrendingSeries($limit, '7d')
                        : StreamingService::getTrendingMovies($limit, '7d');

                    if (!empty($liveTrending)) {
                        return $liveTrending;
                    }
                } catch (\Throwable $e) {}

                $more = MediaVaultItem::where('type', $type)
                    ->where('is_published', true)
                    ->whereNotIn('id', collect($items)->pluck('id'))
                    ->orderByDesc('upstream_created_at')
                    ->take($limit - count($items))
                    ->get()
                    ->map(fn($m) => self::mapToMediaFormat($m))
                    ->all();
                $items = array_merge($items, $more);
            }

            return $items;
        });
    }

    /**
     * Konversi Model ke format array standar KZSTRM
     */
    public static function mapToMediaFormat(MediaVaultItem $m): array
    {
        return [
            'id' => $m->upstream_id ?: $m->slug,
            'title' => $m->title,
            'type' => $m->type,
            'quality' => $m->quality ?: 'WEB-DL',
            'year' => $m->year ?: '',
            'rating' => $m->rating ? number_format($m->rating, 1) : '0.0',
            'comments' => $m->comments,
            'duration' => $m->duration ?: '',
            'genre' => $m->genre ?: '',
            'thumbnail' => $m->thumbnail,
            'banner' => $m->banner ?: $m->thumbnail,
            'logo' => $m->logo,
            'description' => $m->description ?: '',
            'slug' => $m->slug,
        ];
    }

    /**
     * Scraping / Sinkronisasi awal dari IDLIX & Rebahin ke Vault lokal.
     */
    public static function syncFromUpstream(int $moviePages = 3, int $seriesPages = 3): array
    {
        $stats = ['movies_saved' => 0, 'series_saved' => 0];
        $domain = ScraperSetting::getInstance()->idlix_domain;
        $helperPath = app_path('Services/fetch_cf.py');

        // 1. Sync Movies dari IDLIX
        for ($p = 1; $p <= $moviePages; $p++) {
            $url = rtrim($domain, '/') . "/api/movies?page={$p}&limit=36&sort=createdAt";
            $cmd = '/usr/bin/python3.10 ' . escapeshellarg($helperPath) . ' ' . escapeshellarg($url);
            $pyRes = shell_exec($cmd);

            if (!empty($pyRes) && !str_contains($pyRes, 'Cloudflare') && !str_contains($pyRes, 'Just a moment...')) {
                $json = json_decode($pyRes, true);
                foreach ($json['data'] ?? [] as $it) {
                    if (self::upsertIdlixItem($it, 'movie')) {
                        $stats['movies_saved']++;
                    }
                }
            }
        }

        // 2. Sync Series dari IDLIX
        for ($p = 1; $p <= $seriesPages; $p++) {
            $url = rtrim($domain, '/') . "/api/series?page={$p}&limit=36&sort=createdAt";
            $cmd = '/usr/bin/python3.10 ' . escapeshellarg($helperPath) . ' ' . escapeshellarg($url);
            $pyRes = shell_exec($cmd);

            if (!empty($pyRes) && !str_contains($pyRes, 'Cloudflare') && !str_contains($pyRes, 'Just a moment...')) {
                $json = json_decode($pyRes, true);
                foreach ($json['data'] ?? [] as $it) {
                    if (self::upsertIdlixItem($it, 'series')) {
                        $stats['series_saved']++;
                    }
                }
            }
        }

        // Bersihkan cache terkait
        Cache::forget('media_vault_movies_p1_l36_screatedAt');
        Cache::forget('media_vault_series_p1_l36_screatedAt');
        Cache::forget('media_vault_trending_movie_10');
        Cache::forget('media_vault_trending_series_10');

        return $stats;
    }

    /**
     * Upsert 1 item dari payload IDLIX ke tabel database lokal.
     */
    public static function upsertIdlixItem(array $content, string $defaultType = 'movie'): bool
    {
        $id = $content['id'] ?? null;
        $slug = $content['slug'] ?? null;
        $title = $content['title'] ?? ($content['name'] ?? null);

        if (!$slug || !$title) return false;

        $type = $content['contentType'] ?? $defaultType;
        if ($type === 'tv') $type = 'series';

        $posterPath = $content['posterPath'] ?? null;
        $backdropPath = $content['backdropPath'] ?? null;
        $logoPath = $content['logoPath'] ?? null;

        $thumbnail = $posterPath ? 'https://image.tmdb.org/t/p/w500' . $posterPath : null;
        $banner = $backdropPath ? 'https://image.tmdb.org/t/p/original' . $backdropPath : $thumbnail;
        $logo = $logoPath ? 'https://image.tmdb.org/t/p/w500' . $logoPath : null;

        $rating = !empty($content['voteAverage']) ? (float)$content['voteAverage'] : null;

        $year = '';
        if (!empty($content['releaseDate'])) {
            $year = substr($content['releaseDate'], 0, 4);
        } elseif (!empty($content['firstAirDate'])) {
            $year = substr($content['firstAirDate'], 0, 4);
        }

        $genres = [];
        if (!empty($content['genres']) && is_array($content['genres'])) {
            foreach ($content['genres'] as $g) {
                if (isset($g['name'])) $genres[] = $g['name'];
            }
        }

        $upstreamCreatedAt = null;
        if (!empty($content['createdAt'])) {
            try {
                $upstreamCreatedAt = \Carbon\Carbon::parse($content['createdAt']);
            } catch (\Throwable $e) {}
        }

        MediaVaultItem::updateOrCreate(
            ['slug' => $slug],
            [
                'upstream_id' => $id,
                'title' => $title,
                'type' => $type,
                'quality' => $content['quality'] ?? 'WEB-DL',
                'year' => $year,
                'rating' => $rating,
                'comments' => (int)($content['commentCount'] ?? 0),
                'duration' => isset($content['runtime']) ? $content['runtime'] . 'm' : null,
                'genre' => implode(', ', $genres),
                'genres' => $genres,
                'thumbnail' => $thumbnail,
                'banner' => $banner,
                'logo' => $logo,
                'description' => $content['overview'] ?? '',
                'is_featured' => !empty($backdropPath),
                'is_trending' => ($rating !== null && $rating >= 7.0),
                'is_published' => true,
                'source' => 'idlix',
                'upstream_created_at' => $upstreamCreatedAt,
                'last_synced_at' => now(),
            ]
        );

        return true;
    }
}
