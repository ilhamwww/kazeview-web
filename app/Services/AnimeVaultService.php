<?php

namespace App\Services;

use App\Models\AnimeVaultItem;
use App\Models\AnimeVaultUpdate;
use App\Services\MirainimeScraperService;
use App\Services\OtakudesuScraperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnimeVaultService
{
    /**
     * Ambil data beranda Anime dari Database Lokal (Super Cepat < 0.02s)
     */
    public static function getHomeData(): array
    {
        return Cache::remember('anime_vault_home_v1', 300, function () {
            // 1. Featured / Hero items
            $hero = AnimeVaultItem::where('is_published', true)
                ->where('is_featured', true)
                ->orderByDesc('rating')
                ->take(8)
                ->get()
                ->map(fn($item, $idx) => self::mapItem($item, $idx))
                ->all();

            // 2. Trending
            $trending = AnimeVaultItem::where('is_published', true)
                ->where('is_trending', true)
                ->orderByDesc('rating')
                ->take(10)
                ->get()
                ->map(fn($item, $idx) => self::mapItem($item, $idx))
                ->all();

            // 3. Ongoing Musim Ini
            $season = AnimeVaultItem::where('is_published', true)
                ->where('status', 'Ongoing')
                ->orderByDesc('latest_episode_at')
                ->take(12)
                ->get()
                ->map(fn($item, $idx) => self::mapItem($item, $idx))
                ->all();

            // 4. Top Rated Anime
            $top = AnimeVaultItem::where('is_published', true)
                ->whereNotNull('rating')
                ->orderByDesc('rating')
                ->take(10)
                ->get()
                ->map(fn($item, $idx) => self::mapItem($item, $idx))
                ->all();

            return [
                'hero' => $hero,
                'trending' => $trending,
                'season' => $season,
                'season_title' => 'Anime Ongoing Terhangat',
                'top' => $top,
            ];
        });
    }

    /**
     * Ambil feed episode terbaru (Baru Update) dari Database Lokal
     */
    public static function getRecentUpdates(int $limit = 24): array
    {
        return Cache::remember('anime_vault_recent_updates_v1_' . $limit, 180, function () use ($limit) {
            $updates = AnimeVaultUpdate::orderByDesc('created_at')
                ->take($limit)
                ->get()
                ->map(function ($u) {
                    return [
                        'slug' => $u->slug,
                        'title' => $u->title,
                        'episode' => $u->episode,
                        'poster' => $u->poster ?: '',
                        'type' => $u->type ?: 'TV',
                        'rating' => $u->rating ?: '',
                        'ago' => $u->ago ?: '',
                        'is_new' => true,
                    ];
                })
                ->all();

            return $updates;
        });
    }

    /**
     * Ambil Jadwal Tayang Mingguan dari Database Lokal
     */
    public static function getWeeklySchedule(): array
    {
        return Cache::remember('anime_vault_weekly_schedule_v1', 1800, function () {
            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            $schedule = [];

            foreach ($days as $day) {
                $schedule[$day] = AnimeVaultItem::where('is_published', true)
                    ->where('status', 'Ongoing')
                    ->where('release_day', $day)
                    ->orderByDesc('rating')
                    ->take(15)
                    ->get()
                    ->map(fn($item) => self::mapItem($item))
                    ->all();
            }

            return $schedule;
        });
    }

    /**
     * Ambil katalog daftar anime dengan filter dan pagination dari Database Lokal
     */
    public static function getAnimeList(int $page = 1, string $status = '', string $search = '', string $genre = '', int $perPage = 24): array
    {
        $query = AnimeVaultItem::where('is_published', true);

        if (!empty($search)) {
            $query->where('title', 'ilike', '%' . trim($search) . '%');
        }

        if (!empty($status)) {
            $query->where('status', 'ilike', $status);
        }

        if (!empty($genre)) {
            $query->whereJsonContains('genres', $genre);
        }

        $query->orderByDesc('latest_episode_at')
              ->orderByDesc('id');

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)
                       ->take($perPage)
                       ->get()
                       ->map(fn($item) => self::mapItem($item))
                       ->all();

        return [
            'items' => $items,
            'total' => $total,
            'hasMore' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Synchronize and crawl updates into local database (Bisa dijalankan di VPS atau NUC)
     */
    public static function syncFromUpstream(): array
    {
        $stats = [
            'homepage_items' => 0,
            'updates_items' => 0,
            'schedule_items' => 0,
        ];

        try {
            $scraper = new MirainimeScraperService();

            // 1. Sync Homepage (Hero, Trending, Ongoing, Top, dan Latest)
            $home = $scraper->scrapeHomepage();
            if (!empty($home)) {
                // Latest Episodes dari Homepage (LENGKAP DENGAN POSTER ASLI!)
                foreach ($home['latest'] ?? [] as $it) {
                    $slug = $it['slug'] ?? '';
                    $title = $it['series'] ?? ($it['title'] ?? '');
                    $ep = (string)($it['episode'] ?? '');
                    if (empty($slug) || empty($title)) continue;

                    $poster = $it['poster'] ?? ($it['thumbnail'] ?? null);

                    AnimeVaultUpdate::updateOrCreate(
                        ['slug' => $slug, 'episode' => $ep, 'source' => 'mirainime'],
                        [
                            'title' => $title,
                            'poster' => $poster,
                            'type' => $it['type'] ?? 'TV',
                            'rating' => $it['rating'] ?? '',
                            'ago' => $it['ago'] ?? 'Baru saja',
                            'released_timestamp' => time(),
                        ]
                    );

                    self::upsertItem($slug, [
                        'title' => $title,
                        'poster' => $poster,
                        'latest_episode' => $ep,
                        'latest_episode_at' => now(),
                        'rating' => !empty($it['rating']) ? (float)$it['rating'] : null,
                        'type' => $it['type'] ?? 'TV',
                        'status' => 'Ongoing',
                    ]);

                    $stats['updates_items']++;
                }

                // Hero
                foreach ($home['hero'] ?? [] as $it) {
                    $slug = $it['slug'] ?? '';
                    $title = $it['title'] ?? ($it['series'] ?? '');
                    if (empty($slug) || empty($title)) continue;
                    self::upsertItem($slug, [
                        'title' => $title,
                        'poster' => $it['poster'] ?? null,
                        'banner' => $it['banner'] ?? ($it['poster'] ?? null),
                        'rating' => !empty($it['rating']) ? (float)$it['rating'] : null,
                        'is_featured' => true,
                        'type' => $it['type'] ?? 'TV',
                        'status' => 'Ongoing',
                    ]);
                    $stats['homepage_items']++;
                }

                // Trending
                foreach ($home['trending'] ?? [] as $it) {
                    $slug = $it['slug'] ?? '';
                    $title = $it['title'] ?? ($it['series'] ?? '');
                    if (empty($slug) || empty($title)) continue;
                    self::upsertItem($slug, [
                        'title' => $title,
                        'poster' => $it['poster'] ?? null,
                        'rating' => !empty($it['rating']) ? (float)$it['rating'] : null,
                        'is_trending' => true,
                        'status' => 'Ongoing',
                    ]);
                    $stats['homepage_items']++;
                }

                // Ongoing Season
                foreach ($home['season'] ?? [] as $it) {
                    $slug = $it['slug'] ?? '';
                    $title = $it['title'] ?? ($it['series'] ?? '');
                    if (empty($slug) || empty($title)) continue;
                    self::upsertItem($slug, [
                        'title' => $title,
                        'poster' => $it['poster'] ?? null,
                        'rating' => !empty($it['rating']) ? (float)$it['rating'] : null,
                        'status' => 'Ongoing',
                    ]);
                    $stats['homepage_items']++;
                }

                // Top
                foreach ($home['top'] ?? [] as $it) {
                    $slug = $it['slug'] ?? '';
                    $title = $it['title'] ?? ($it['series'] ?? '');
                    if (empty($slug) || empty($title)) continue;
                    self::upsertItem($slug, [
                        'title' => $title,
                        'poster' => $it['poster'] ?? null,
                        'rating' => !empty($it['rating']) ? (float)$it['rating'] : null,
                    ]);
                    $stats['homepage_items']++;
                }
            }

            // 2. Sync Recent Feed (Baru Update)
            $recent = $scraper->scrapeRecentFeed(30);
            if (!empty($recent)) {
                // Kumpulkan slug yang belum punya poster
                $missingSlugs = [];
                foreach ($recent as $r) {
                    $s = $r['slug'] ?? '';
                    if ($s && !AnimeVaultItem::where('slug', $s)->whereNotNull('poster')->exists()) {
                        $missingSlugs[] = $s;
                    }
                }

                $fetchedPosters = [];
                if (!empty($missingSlugs)) {
                    $fetchedPosters = $scraper->fetchPosters(array_unique($missingSlugs));
                }

                foreach ($recent as $r) {
                    $slug = $r['slug'] ?? '';
                    $title = $r['series'] ?? '';
                    $ep = (string)($r['episode'] ?? '');
                    if (empty($slug) || empty($title) || empty($ep)) continue;

                    $existingItem = AnimeVaultItem::where('slug', $slug)->first();
                    $poster = $existingItem ? $existingItem->poster : ($fetchedPosters[$slug] ?? null);

                    if (!empty($poster)) {
                        AnimeVaultUpdate::updateOrCreate(
                            ['slug' => $slug, 'episode' => $ep, 'source' => 'mirainime'],
                            [
                                'title' => $title,
                                'poster' => $poster,
                                'type' => 'TV',
                                'ago' => $r['ago'] ?? '',
                                'released_timestamp' => $r['timestamp'] ?? time(),
                            ]
                        );

                        // Update episode terakhir pada item anime
                        self::upsertItem($slug, [
                            'title' => $title,
                            'poster' => $poster,
                            'latest_episode' => $ep,
                            'latest_episode_at' => now(),
                        ]);

                        $stats['updates_items']++;
                    }
                }
            }

            // 3. Sync Jadwal Tayang Mingguan
            $schedule = $scraper->scrapeWeeklySchedule();
            if (!empty($schedule)) {
                foreach ($schedule as $day => $items) {
                    foreach ($items as $it) {
                        $slug = $it['slug'] ?? '';
                        $title = $it['title'] ?? ($it['series'] ?? '');
                        if (empty($slug) || empty($title)) continue;

                        self::upsertItem($slug, [
                            'title' => $title,
                            'poster' => $it['poster'] ?? ($it['thumbnail'] ?? null),
                            'release_day' => $day,
                            'status' => 'Ongoing',
                            'type' => $it['type'] ?? 'TV',
                        ]);
                        $stats['schedule_items']++;
                    }
                }
            }

            // Bersihkan cache beranda agar langsung segar
            Cache::forget('anime_vault_home_v1');
            Cache::forget('anime_vault_recent_updates_v1_24');
            Cache::forget('anime_vault_weekly_schedule_v1');

        } catch (\Throwable $e) {
            Log::error('[AnimeVaultService] syncFromUpstream failed: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Upsert helper
     */
    protected static function upsertItem(string $slug, array $attributes): AnimeVaultItem
    {
        $item = AnimeVaultItem::firstOrNew(['slug' => $slug]);

        foreach ($attributes as $key => $val) {
            if ($val !== null && $val !== '') {
                $item->{$key} = $val;
            }
        }

        $item->last_synced_at = now();
        $item->save();

        return $item;
    }

    /**
     * Map model to UI array
     */
    protected static function mapItem(AnimeVaultItem $item, int $index = 0): array
    {
        $detailUrl = $item->slug ? \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $item->slug], panel: 'streaming') : '#';

        return [
            'slug' => $item->slug,
            'title' => $item->title,
            'series' => $item->title,
            'poster' => $item->poster ?: '',
            'thumbnail' => $item->poster ?: '',
            'banner' => $item->banner ?: ($item->poster ?: ''),
            'rating' => $item->rating ? (string)$item->rating : '',
            'type' => $item->type ?: 'TV',
            'status' => $item->status ?: 'Ongoing',
            'episode' => $item->latest_episode ?: '',
            'episodes' => $item->latest_episode ?: '',
            'release_day' => $item->release_day ?: '',
            'detail_url' => $detailUrl,
            'url' => $detailUrl,
            'rank' => (string)($index + 1),
        ];
    }
}
