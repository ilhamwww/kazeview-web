<?php

namespace App\Console\Commands;

use App\Models\ExpSeason;
use App\Services\ExpService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tutup season yang sudah habis, simpan klasemen akhir, lalu buka season baru.
 *
 * Season I berakhir 31 Des 2026 23:59:59 WIB, sehingga reset pertama jatuh
 * tepat 1 Januari 2027 00:00. Season berikutnya berjalan 100 hari.
 */
class ExpSeasonClose extends Command
{
    protected $signature = 'exp:season-close {--force : Tutup season aktif sekarang juga (untuk uji)} {--dry-run : Hitung klasemen tanpa menyimpan}';

    protected $description = 'Tutup season EXP yang sudah berakhir dan buat season berikutnya';

    public function handle(): int
    {
        $now = Carbon::now(ExpService::TZ);
        $closed = 0;

        $seasons = ExpSeason::whereNull('closed_at')
            ->when(! $this->option('force'), fn ($q) => $q->where('ends_at', '<', $now))
            ->orderBy('number')
            ->get();

        foreach ($seasons as $season) {
            if ($this->option('dry-run')) {
                DB::beginTransaction();

                try {
                    $this->snapshot($season);
                    $count = DB::table('exp_season_stats')->where('season_id', $season->id)->count();
                    $top = DB::table('exp_season_stats')->where('season_id', $season->id)->orderByDesc('points')->first();
                    $this->info("DRY Season {$season->roman}: {$count} baris, teratas " . ($top->points ?? 0) . ' poin');
                } finally {
                    DB::rollBack();
                }

                continue;
            }

            $this->snapshot($season);
            $season->update(['closed_at' => $now]);
            $this->info("Season {$season->roman} ditutup.");
            $closed++;
        }

        if ($closed > 0) {
            $last = ExpSeason::orderByDesc('number')->first();
            $this->openNext($last);
        }

        if ($closed === 0) {
            $this->info('Tidak ada season yang perlu ditutup.');
        }

        Cache::forget('kz_exp_current_season');
        Cache::forget(ExpService::LEADERBOARD_CACHE);

        return self::SUCCESS;
    }

    protected function snapshot(ExpSeason $season): void
    {
        $rows = DB::table('user_exp_events')
            ->where('season_id', $season->id)
            ->groupBy('user_id')
            ->selectRaw('user_id, COALESCE(SUM(exp),0) as points')
            ->get();

        $users = DB::table('users')
            ->whereIn('id', $rows->pluck('user_id'))
            ->select('id', 'created_at')
            ->get()
            ->keyBy('id');

        $standings = $rows->map(function ($r) use ($season, $users) {
            $created = $users[$r->user_id]->created_at ?? null;
            $loyalty = ExpService::seasonLoyaltyExp($created, $season->starts_at);

            return ['user_id' => (int) $r->user_id, 'points' => (int) $r->points + $loyalty];
        })->sortByDesc('points')->values();

        $rank = 0;
        $lastPoints = null;

        foreach ($standings as $i => $row) {
            if ($lastPoints === null || $row['points'] < $lastPoints) {
                $rank = $i + 1;
                $lastPoints = $row['points'];
            }

            DB::table('exp_season_stats')->updateOrInsert(
                ['season_id' => $season->id, 'user_id' => $row['user_id']],
                [
                    'points' => $row['points'],
                    'level' => ExpService::levelInfo($row['points'])['level'],
                    'rank' => $rank,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    protected function openNext(?ExpSeason $last): void
    {
        $number = ($last?->number ?? 0) + 1;
        $starts = $last ? $last->ends_at->copy()->addSecond() : Carbon::now(ExpService::TZ);

        ExpSeason::create([
            'number' => $number,
            'roman' => ExpSeason::roman($number),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addDays(100)->subSecond(),
        ]);

        $this->info("Season " . ExpSeason::roman($number) . " dibuka sampai {$starts->copy()->addDays(100)->format('d M Y H:i')} WIB.");
    }
}
