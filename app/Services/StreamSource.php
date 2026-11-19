<?php

namespace App\Services;

/**
 * Router sumber konten: Indocast Filmbox lebih dulu, Chitogenx sebagai cadangan.
 *
 * Semua method menerima slug publik KZSTRM (slug Chitogenx lama tetap sah),
 * lalu FilmboxService memetakannya ke detailPath. Kalau Filmbox tidak punya
 * judul tersebut atau gagal, Chitogenx yang menjawab.
 */
class StreamSource
{
    public static function primary(): string
    {
        return FilmboxService::enabled() ? 'filmbox' : 'chitogenx';
    }

    public static function home(): array
    {
        if (FilmboxService::enabled()) {
            $home = FilmboxService::home();

            if (!empty($home['sections'])) {
                return $home;
            }
        }

        return ChitogenxService::home();
    }

    public static function listing(string $type, int $page = 1, int $limit = 36): array
    {
        if (FilmboxService::enabled()) {
            $list = FilmboxService::listing($type, $page, $limit);

            if (!empty($list['data'])) {
                return $list;
            }
        }

        return ChitogenxService::listing($type, $page, $limit);
    }

    public static function trending(string $type, int $limit = 10): array
    {
        if (FilmboxService::enabled()) {
            $items = FilmboxService::trending($type, $limit);

            if (!empty($items)) {
                return $items;
            }
        }

        return ChitogenxService::trending($type, $limit);
    }

    public static function detail(string $slug): ?array
    {
        if (FilmboxService::enabled()) {
            $detail = FilmboxService::detail($slug);

            if ($detail) {
                return $detail;
            }
        }

        return ChitogenxService::detail($slug);
    }

    public static function season(string $slug, int $seasonNumber): ?array
    {
        if (FilmboxService::enabled()) {
            $season = FilmboxService::season($slug, $seasonNumber);

            if ($season && !empty($season['season']['episodes'])) {
                return $season;
            }
        }

        return ChitogenxService::season($slug, $seasonNumber);
    }

    public static function play(string $slug, ?int $seasonNumber = null, ?int $episodeNumber = null): ?array
    {
        if (FilmboxService::enabled()) {
            $play = FilmboxService::play($slug, $seasonNumber, $episodeNumber);

            if ($play && !empty($play['video_url'])) {
                return $play;
            }
        }

        return ChitogenxService::play($slug, $seasonNumber, $episodeNumber);
    }

    public static function search(string $query, int $page = 1, int $limit = 20): array
    {
        $primary = ['data' => [], 'pagination' => []];

        if (FilmboxService::enabled()) {
            $primary = FilmboxService::search($query, 1, max($limit, 20));
        }

        // Lengkapi dengan sumber cadangan, tanpa duplikat.
        $secondary = ChitogenxService::search($query, 1, max($limit, 20));
        $merged = [];
        $seen = [];

        foreach (array_merge($primary['data'] ?? [], $secondary['data'] ?? []) as $item) {
            // Jangan pakai slug saja: banyak judul kembar beda tahun/tipe.
            $key = implode('|', [
                strtolower(trim((string) ($item['title'] ?? ''))),
                (string) ($item['year'] ?? ''),
                (string) ($item['type'] ?? ''),
            ]);

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $merged[] = $item;
        }

        $total = count($merged);
        $page = max(1, $page);
        $limit = max(1, $limit);

        return [
            'data' => array_slice($merged, ($page - 1) * $limit, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }
}
