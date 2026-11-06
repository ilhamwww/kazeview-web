<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserExpEvent;
use App\Models\WatchHistory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sistem EXP & Level KZSTRM.
 *
 * - EXP per unit (episode / movie / chapter), tiap unit hanya dihitung sekali per user.
 * - Batas harian (WIB) hanya berlaku untuk EXP baru, bukan saldo awal hasil backfill.
 * - Akun dengan users.special_rank (king/queen) tetap dapat EXP, tapi dipisah dari ranking.
 */
class ExpService
{
    public const DAILY_CAP = 3000;
    public const TZ = 'Asia/Jakarta';
    public const MOVIE_DONE_PERCENT = 80;

    /** EXP per unit menurut tipe konten. */
    public const VALUES = [
        'movie'    => 50,
        'series'   => 20,
        'anime'    => 15,
        'bstation' => 15,
        'dracin'   => 8,
        'komik'    => 5,
    ];

    /** EXP kumulatif minimum untuk tiap level. */
    public const LEVELS = [
        1 => 0,
        2 => 500,
        3 => 3000,
        4 => 10000,
        5 => 20000,
        6 => 35000,
        7 => 55000,
        8 => 80000,
        9 => 110000,
        10 => 150000,
    ];

    public const LEADERBOARD_CACHE = 'kz_exp_leaderboard_v3';

    /** Cache ringkasan EXP per user untuk topbar/navbar (dipanggil di setiap halaman). */
    public static function navSummary(User $user): array
    {
        return Cache::remember('kz_exp_nav_' . $user->id, 60, fn () => self::forUser($user));
    }

    /** EXP loyalitas: +1 per hari sejak tanggal daftar (termasuk hari daftar), di luar batas harian. */
    public const LOYALTY_PER_DAY = 1;

    public static function loyaltyExp($createdAt): int
    {
        if (!$createdAt) {
            return 0;
        }
        $join = Carbon::parse($createdAt)->setTimezone(self::TZ)->startOfDay();
        $today = Carbon::now(self::TZ)->startOfDay();
        if ($join->gt($today)) {
            return 0;
        }
        return ((int) $join->diffInDays($today) + 1) * self::LOYALTY_PER_DAY;
    }

    /**
     * Beri EXP untuk satu unit. Return EXP yang benar-benar diberikan (0 jika duplikat / cap habis).
     */
    public static function award(int $userId, string $type, string $itemId, string $unitKey): int
    {
        $base = self::VALUES[$type] ?? 0;
        if ($base <= 0 || $itemId === '' || $unitKey === '') {
            return 0;
        }

        $today = Carbon::now(self::TZ)->toDateString();

        try {
            return DB::transaction(function () use ($userId, $type, $itemId, $unitKey, $base, $today) {
                // Kunci baris user supaya dua request paralel tidak menembus cap harian
                DB::table('users')->where('id', $userId)->lockForUpdate()->first();

                $exists = UserExpEvent::where([
                    'user_id' => $userId, 'source_type' => $type,
                    'item_id' => $itemId, 'unit_key' => $unitKey,
                ])->exists();
                if ($exists) {
                    return 0;
                }

                $earnedToday = (int) UserExpEvent::where('user_id', $userId)
                    ->where('is_backfill', false)
                    ->whereDate('earned_on', $today)
                    ->sum('exp');

                $grant = max(0, min($base, self::DAILY_CAP - $earnedToday));

                // Unit tetap dicatat walau grant 0, supaya tidak bisa di-farm ulang besok
                UserExpEvent::create([
                    'user_id' => $userId, 'source_type' => $type,
                    'item_id' => $itemId, 'unit_key' => $unitKey,
                    'exp' => $grant, 'is_backfill' => false, 'earned_on' => $today,
                ]);

                Cache::forget(self::LEADERBOARD_CACHE);
                Cache::forget('kz_exp_nav_' . $userId);
                return $grant;
            });
        } catch (QueryException $e) {
            // Race pada unique key = unit sudah tercatat oleh request lain
            if (str_contains($e->getMessage(), 'user_exp_unit_unique')) {
                return 0;
            }
            Log::warning('ExpService award failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Dipanggil dari observer WatchHistory setiap kali riwayat tersimpan.
     */
    public static function awardFromWatchHistory(WatchHistory $h): void
    {
        $type = $h->item_type;
        if ($type === 'komik' || !$h->user_id) {
            return;
        }

        // Jenis Indonesia disimpan sebagai tipe terpisah untuk histori, tetapi
        // mengikuti nilai EXP film/series yang sama.
        $awardType = match ($type) {
            'indonesia_movie' => 'movie',
            'indonesia_series' => 'series',
            default => $type,
        };
        if ($type === 'indonesia_series') {
            $eps = is_array($h->watched_episodes) ? $h->watched_episodes : [];
            $before = $h->getOriginal('watched_episodes');
            $before = is_array($before) ? $before : (json_decode((string) $before, true) ?: []);
            if ($h->wasRecentlyCreated) {
                $before = [];
            }
            foreach (array_diff(array_map('strval', $eps), array_map('strval', $before)) as $ep) {
                self::award((int) $h->user_id, $awardType, (string) $h->item_id, (string) $ep);
            }
            return;
        }
        if (!isset(self::VALUES[$awardType])) {
            return;
        }

        if (in_array($type, ['movie', 'indonesia_movie'], true)) {
            // Movie baru dihitung kalau sudah ditonton minimal 80%
            if ((float) $h->progress_percent >= self::MOVIE_DONE_PERCENT) {
                self::award($h->user_id, 'movie', (string) $h->item_id, '1');
            }
            return;
        }

        $eps = is_array($h->watched_episodes) ? $h->watched_episodes : [];
        $before = $h->getOriginal('watched_episodes');
        $before = is_array($before) ? $before : (json_decode((string) $before, true) ?: []);
        if ($h->wasRecentlyCreated) {
            $before = [];
        }

        $new = array_diff(array_map('strval', $eps), array_map('strval', $before));
        foreach ($new as $ep) {
            self::award($h->user_id, $awardType, (string) $h->item_id, (string) $ep);
        }
    }

    /**
     * Isi saldo awal dari riwayat tontonan lama. Idempotent (aman dijalankan ulang).
     * Movie lama: dihitung jika ada di riwayat, karena 81 dari 88 movie lama tidak punya data progres.
     */
    public static function backfill(): array
    {
        $now = Carbon::now(); // timestamp DB dalam timezone app (UTC); earned_on tetap tanggal WIB
        $inserted = 0;
        $rows = [];

        foreach (WatchHistory::query()->select(['user_id', 'item_id', 'item_type', 'watched_episodes', 'updated_at'])->cursor() as $h) {
            $base = self::VALUES[$h->item_type] ?? 0;
            if ($base <= 0 || !$h->user_id) {
                continue;
            }
            $eps = is_array($h->watched_episodes) ? $h->watched_episodes : [];
            if ($h->item_type === 'movie') {
                $eps = $eps ? ['1'] : [];
            }
            $date = $h->updated_at ? Carbon::parse($h->updated_at)->setTimezone(self::TZ)->toDateString() : $now->toDateString();

            foreach (array_unique(array_map('strval', $eps)) as $ep) {
                $rows[] = [
                    'user_id' => $h->user_id, 'source_type' => $h->item_type,
                    'item_id' => mb_substr((string) $h->item_id, 0, 255), 'unit_key' => mb_substr($ep, 0, 100),
                    'exp' => $base, 'is_backfill' => true, 'earned_on' => $date,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $inserted += DB::table('user_exp_events')->insertOrIgnore($chunk);
        }

        Cache::forget(self::LEADERBOARD_CACHE);
        return ['candidates' => count($rows), 'inserted' => $inserted];
    }

    /** Info level dari total EXP. */
    public static function levelInfo(int $exp): array
    {
        $level = 1;
        foreach (self::LEVELS as $lv => $min) {
            if ($exp >= $min) {
                $level = $lv;
            }
        }
        $curMin = self::LEVELS[$level];
        $nextMin = self::LEVELS[$level + 1] ?? null;
        $progress = $nextMin ? (int) floor((($exp - $curMin) / ($nextMin - $curMin)) * 100) : 100;

        return [
            'level' => $level,
            'exp' => $exp,
            'current_min' => $curMin,
            'next_min' => $nextMin,
            'to_next' => $nextMin ? $nextMin - $exp : 0,
            'progress' => max(0, min(100, $progress)),
            'is_max' => $nextMin === null,
        ];
    }

    public static function badgeUrl(int $level, ?string $special = null): string
    {
        $key = in_array($special, ['king', 'queen'], true) ? $special : (string) max(1, min(10, $level));
        // Path relatif: asset() di belakang proxy menghasilkan host internal 127.0.0.1
        return '/images/badges/level-' . $key . '.webp';
    }

    public static function levelTitle(int $level, ?string $special = null): string
    {
        if ($special === 'king') return 'King';
        if ($special === 'queen') return 'Queen';
        return [
            1 => 'Squire', 2 => 'Footman', 3 => 'Sentinel', 4 => 'Knight', 5 => 'Mystic Knight',
            6 => 'Paladin', 7 => 'Dragon Rider', 8 => 'Warlord', 9 => 'Archon', 10 => 'Legend',
        ][$level] ?? 'Squire';
    }

    /**
     * Data leaderboard: ['royals' => [...], 'ranked' => [...]] (cache 5 menit).
     */
    public static function leaderboard(int $limit = 20): array
    {
        return Cache::remember(self::LEADERBOARD_CACHE, 300, function () use ($limit) {
            $lastSeen = DB::table('watch_histories')->groupBy('user_id')->selectRaw('user_id, MAX(updated_at) as t')->pluck('t', 'user_id')->all();
            try {
                foreach (DB::table('comic_read_histories')->groupBy('user_id')->selectRaw('user_id, MAX(updated_at) as t')->pluck('t', 'user_id') as $uid => $t) {
                    if (!isset($lastSeen[$uid]) || $t > $lastSeen[$uid]) {
                        $lastSeen[$uid] = $t;
                    }
                }
            } catch (\Throwable $e) {
            }

            // Mulai dari users (left join) supaya user tanpa riwayat tetap dapat EXP loyalitas
            $totals = DB::table('users')
                ->leftJoin('user_exp_events', 'users.id', '=', 'user_exp_events.user_id')
                ->groupBy('users.id', 'users.name', 'users.special_rank', 'users.created_at')
                ->select('users.id', 'users.name', 'users.special_rank', 'users.created_at')
                ->selectRaw('COALESCE(SUM(user_exp_events.exp), 0) as total_exp')
                ->selectRaw("SUM(CASE WHEN source_type = 'movie' THEN exp ELSE 0 END) as exp_movie")
                ->selectRaw("SUM(CASE WHEN source_type = 'series' THEN exp ELSE 0 END) as exp_series")
                ->selectRaw("SUM(CASE WHEN source_type IN ('anime','bstation') THEN exp ELSE 0 END) as exp_anime")
                ->selectRaw("SUM(CASE WHEN source_type = 'dracin' THEN exp ELSE 0 END) as exp_dracin")
                ->selectRaw("SUM(CASE WHEN source_type = 'komik' THEN exp ELSE 0 END) as exp_komik")
                ->selectRaw("COUNT(CASE WHEN source_type = 'movie' THEN 1 END) as n_movie")
                ->selectRaw("COUNT(CASE WHEN source_type = 'series' THEN 1 END) as n_series")
                ->selectRaw("COUNT(CASE WHEN source_type IN ('anime','bstation') THEN 1 END) as n_anime")
                ->selectRaw("COUNT(CASE WHEN source_type = 'dracin' THEN 1 END) as n_dracin")
                ->selectRaw("COUNT(CASE WHEN source_type = 'komik' THEN 1 END) as n_komik")
                ->selectRaw('SUM(CASE WHEN user_exp_events.is_backfill = false AND user_exp_events.earned_on = ? THEN user_exp_events.exp ELSE 0 END) as exp_today', [Carbon::now(self::TZ)->toDateString()])
                ->selectRaw('MAX(user_exp_events.updated_at) as last_at')
                ->get()
                ->map(function ($r) use ($lastSeen) {
                    // Terakhir aktif = aktivitas nonton/baca asli, bukan waktu ledger (backfill)
                    $r->last_at = $lastSeen[$r->id] ?? null;
                    $r->exp_loyal = self::loyaltyExp($r->created_at);
                    $r->total_exp = (int) $r->total_exp + $r->exp_loyal;
                    return $r;
                })
                ->sortByDesc('total_exp')
                ->values();

            $map = fn ($r) => self::presentRow($r);

            $royals = $totals->filter(fn ($r) => in_array($r->special_rank, ['king', 'queen'], true))
                ->sortBy(fn ($r) => $r->special_rank === 'king' ? 0 : 1)
                ->map($map)->values()->all();

            $ranked = $totals->reject(fn ($r) => in_array($r->special_rank, ['king', 'queen'], true))
                ->filter(fn ($r) => (int) $r->total_exp > 0)
                ->take($limit)->values()
                ->map(function ($r, $i) use ($map) {
                    return ['rank' => $i + 1] + $map($r);
                })->all();

            return ['royals' => $royals, 'ranked' => $ranked];
        });
    }

    /** Ringkasan EXP satu user (untuk kartu "Level Kamu"). */
    public static function forUser(User $user): array
    {
        $r = DB::table('user_exp_events')->where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(exp),0) as total_exp')
            ->first();
        $today = (int) DB::table('user_exp_events')->where('user_id', $user->id)
            ->where('is_backfill', false)
            ->whereDate('earned_on', Carbon::now(self::TZ)->toDateString())
            ->sum('exp');

        $loyal = self::loyaltyExp($user->created_at);
        $info = self::levelInfo((int) $r->total_exp + $loyal);
        return $info + [
            'loyalty' => $loyal,
            'user_id' => $user->id,
            'name' => $user->name,
            'special' => $user->special_rank,
            'badge' => self::badgeUrl($info['level'], $user->special_rank),
            'title' => self::levelTitle($info['level'], $user->special_rank),
            'today' => $today,
            'today_left' => max(0, self::DAILY_CAP - $today),
        ];
    }

    private static function presentRow(object $r): array
    {
        $info = self::levelInfo((int) $r->total_exp);
        $breakdown = array_filter([
            'Movie' => (int) $r->exp_movie,
            'Series' => (int) $r->exp_series,
            'Anime' => (int) $r->exp_anime,
            'Dracin' => (int) $r->exp_dracin,
            'Komik' => (int) $r->exp_komik,
        ]);
        arsort($breakdown);

        return $info + [
            'user_id' => (int) $r->id,
            'name' => $r->name,
            'initial' => mb_strtoupper(mb_substr(trim($r->name) ?: '?', 0, 1)),
            'special' => $r->special_rank,
            'badge' => self::badgeUrl($info['level'], $r->special_rank),
            'title' => self::levelTitle($info['level'], $r->special_rank),
            'breakdown' => $breakdown,          // hanya EXP konten; loyalitas dipisah agar "Paling banyak" tetap bermakna
            'loyalty' => (int) ($r->exp_loyal ?? 0),
            'top_source' => array_key_first($breakdown) ?? 'Loyalitas',
            // Rincian untuk modal statistik per user (hanya agregat, tanpa judul yang ditonton)
            'sources' => [
                ['key' => 'movie', 'label' => 'Movie', 'exp' => (int) $r->exp_movie, 'count' => (int) ($r->n_movie ?? 0), 'unit' => 'film'],
                ['key' => 'series', 'label' => 'TV Series / Indonesia', 'exp' => (int) $r->exp_series, 'count' => (int) ($r->n_series ?? 0), 'unit' => 'episode'],
                ['key' => 'anime', 'label' => 'Anime', 'exp' => (int) $r->exp_anime, 'count' => (int) ($r->n_anime ?? 0), 'unit' => 'episode'],
                ['key' => 'dracin', 'label' => 'Dracin', 'exp' => (int) $r->exp_dracin, 'count' => (int) ($r->n_dracin ?? 0), 'unit' => 'episode'],
                ['key' => 'komik', 'label' => 'Komik', 'exp' => (int) $r->exp_komik, 'count' => (int) ($r->n_komik ?? 0), 'unit' => 'chapter'],
                ['key' => 'loyal', 'label' => 'Loyalitas', 'exp' => (int) ($r->exp_loyal ?? 0), 'count' => (int) ($r->exp_loyal ?? 0), 'unit' => 'hari'],
            ],
            'today' => (int) ($r->exp_today ?? 0),
            'joined' => $r->created_at ? Carbon::parse($r->created_at)->setTimezone(self::TZ)->locale('id')->translatedFormat('d F Y') : '-',
            'last_active' => $r->last_at ? Carbon::parse($r->last_at, 'UTC')->locale('id')->diffForHumans() : '-',
        ];
    }
}
