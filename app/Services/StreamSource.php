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
        if (FilmboxService::enabled()) {
            $found = FilmboxService::search($query, $page, $limit);

            if (!empty($found['data'])) {
                return $found;
            }
        }

        return ChitogenxService::search($query, $page, $limit);
    }
}
