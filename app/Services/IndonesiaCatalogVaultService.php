<?php

namespace App\Services;

use App\Models\IndonesiaMediaItem;
use App\Services\MediaContentSanitizerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndonesiaCatalogVaultService
{
    /**
     * Ambil katalog Indonesia dari Database Vault
     */
    public static function getCatalog(
        string $category = 'all',
        int $page = 1,
        int $perPage = 24,
        string $search = '',
        string $provider = 'all'
    ): array {
        $query = IndonesiaMediaItem::query()->where('is_published', true);

        // Filter Pencarian
        if (!empty($search)) {
            $query->where('title', 'ilike', '%' . trim($search) . '%');
        }

        // Filter Tipe / Tahun Kategori
        if ($category === 'movie') {
            $query->where('type', 'movie');
        } elseif ($category === 'series') {
            $query->where('type', 'series');
        } elseif (in_array($category, ['2026', '2025', '2024'], true)) {
            $query->where('year', $category);
        } elseif ($category === 'classic') {
            $query->where(function ($q) {
                $q->where('year', '<=', '2023')
                  ->orWhereNull('year');
            });
        }

        // Filter Sumber Server
        if ($provider === 'moviebox') {
            $query->where('source', 'moviebox');
        } elseif ($provider === 'rebahin') {
            $query->where('source', 'server2');
        }

        // Urutan: Server 1 (MovieBox HD) diutamakan di baris atas, lalu tahun terbaru, lalu id terbaru
        $query->orderByRaw("CASE WHEN source = 'moviebox' THEN 0 ELSE 1 END")
              ->orderByRaw("COALESCE(year, '2000') DESC")
              ->orderBy('id', 'DESC');

        $total = $query->count();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $items = $query->skip(($page - 1) * $perPage)
                       ->take($perPage)
                       ->get()
                       ->map(fn($item) => $item->toCard())
                       ->all();

        return [
            'data' => $items,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $total,
                'hasMore' => $page < $totalPages,
            ],
        ];
    }

    /**
     * Ambil konten trending (koleksi Server 1 pilihan teratas)
     */
    public static function getTrending(int $limit = 10): array
    {
        return IndonesiaMediaItem::where('is_published', true)
            ->orderByRaw("CASE WHEN source = 'moviebox' THEN 0 ELSE 1 END")
            ->orderByRaw("COALESCE(year, '2000') DESC")
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->get()
            ->map(fn($item) => $item->toCard())
            ->all();
    }

    /**
     * Ambil film terbaru
     */
    public static function getLatestMovies(int $limit = 15): array
    {
        return IndonesiaMediaItem::where('is_published', true)
            ->where('type', 'movie')
            ->orderByRaw("CASE WHEN source = 'moviebox' THEN 0 ELSE 1 END")
            ->orderByRaw("COALESCE(year, '2000') DESC")
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->get()
            ->map(fn($item) => $item->toCard())
            ->all();
    }

    /**
     * Ambil serial terbaru
     */
    public static function getLatestSeries(int $limit = 15): array
    {
        return IndonesiaMediaItem::where('is_published', true)
            ->where('type', 'series')
            ->orderByRaw("CASE WHEN source = 'moviebox' THEN 0 ELSE 1 END")
            ->orderByRaw("COALESCE(year, '2000') DESC")
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->get()
            ->map(fn($item) => $item->toCard())
            ->all();
    }

    /**
     * Simpan / Sync item dari MovieBox ke database
     */
    public static function syncFromMovieBox(): int
    {
        try {
            $mbRes = NartoMovieBoxService::getIndonesiaCatalog('all', 1);
            $items = $mbRes['data'] ?? [];
            $count = 0;

            foreach ($items as $it) {
                $slug = $it['slug'] ?? '';
                if (empty($slug)) continue;

                // Sensor konten dewasa
                if (MediaContentSanitizerService::isAdult($it)) {
                    continue;
                }

                $rawSlug = $it['raw_slug'] ?? preg_replace('/^mb-/', '', $slug);

                IndonesiaMediaItem::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'raw_slug' => $rawSlug,
                        'target_slug' => $it['target_slug'] ?? $rawSlug,
                        'title' => $it['title'] ?? 'Film Indonesia',
                        'type' => $it['type'] ?? 'movie',
                        'year' => $it['year'] ?? null,
                        'rating' => (float) ($it['rating'] ?? 8.5),
                        'quality' => $it['quality'] ?? '1080p Ultra HD',
                        'duration' => $it['duration'] ?? null,
                        'poster' => $it['poster'] ?? null,
                        'backdrop' => $it['banner'] ?? ($it['poster'] ?? null),
                        'genres' => $it['genres'] ?? ['Indonesia'],
                        'source' => 'moviebox',
                        'provider_badge' => 'Server 1',
                        'is_published' => true,
                        'last_synced_at' => Carbon::now(),
                    ]
                );
                $count++;
            }

            return $count;
        } catch (\Throwable $e) {
            Log::warning('IndonesiaCatalogVaultService::syncFromMovieBox failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Simpan / Sync item dari Rebahin ke database
     */
    public static function syncFromRebahin(int $maxPages = 3): int
    {
        $count = 0;
        try {
            for ($page = 1; $page <= $maxPages; $page++) {
                $rbRes = RebahinService::getIndonesiaCatalog('all', $page);
                $items = $rbRes['data'] ?? [];
                if (empty($items)) break;

                foreach ($items as $it) {
                    $slug = $it['slug'] ?? '';
                    if (empty($slug)) continue;

                    // Sensor konten dewasa
                    if (MediaContentSanitizerService::isAdult($it)) {
                        continue;
                    }

                    $rawSlug = $it['raw_slug'] ?? preg_replace('/^rb-/', '', $slug);

                    // Jangan timpa jika sudah ada versi MovieBox dengan judul yang sama
                    $cleanTitle = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $it['title'] ?? ''));
                    $existsInMb = IndonesiaMediaItem::where('source', 'moviebox')
                        ->whereRaw("LOWER(REGEXP_REPLACE(title, '[^a-zA-Z0-9]+', '', 'g')) = ?", [$cleanTitle])
                        ->exists();

                    if ($existsInMb) {
                        continue;
                    }

                    IndonesiaMediaItem::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'raw_slug' => $rawSlug,
                            'title' => $it['title'] ?? 'Film Indonesia',
                            'type' => $it['type'] ?? 'movie',
                            'year' => $it['year'] ?? null,
                            'rating' => (float) ($it['rating'] ?? 7.5),
                            'quality' => $it['quality'] ?? 'HD',
                            'duration' => $it['duration'] ?? null,
                            'poster' => $it['poster'] ?? null,
                            'backdrop' => $it['banner'] ?? ($it['poster'] ?? null),
                            'genres' => ['Indonesia'],
                            'source' => 'server2',
                            'provider_badge' => 'Server 2',
                            'is_published' => true,
                            'last_synced_at' => Carbon::now(),
                        ]
                    );
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('IndonesiaCatalogVaultService::syncFromRebahin failed: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * Bersihkan konten dewasa yang terlanjur tersimpan di database vault
     */
    public static function purgeAdultContent(): int
    {
        $all = IndonesiaMediaItem::all();
        $deleted = 0;
        foreach ($all as $item) {
            $matched = MediaContentSanitizerService::findMatchedKeyword($item->toCard());
            if ($matched) {
                Log::info("[VaultPurge] Deleted adult item ID {$item->id}: '{$item->title}' (matched: '{$matched}')");
                $item->delete();
                $deleted++;
            }
        }
        return $deleted;
    }
}
