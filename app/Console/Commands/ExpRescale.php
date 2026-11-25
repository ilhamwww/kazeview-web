<?php

namespace App\Console\Commands;

use App\Services\ExpService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Skalakan total poin season berjalan supaya user teratas menyentuh target
 * tertentu, tanpa mengubah urutan/komposisi konten tiap user.
 *
 * Selisihnya dicatat sebagai satu event penyesuaian (is_backfill = true) agar
 * tidak memakan batas harian.
 */
class ExpRescale extends Command
{
    protected $signature = 'exp:rescale
        {--target=100000 : Total poin user teratas setelah diskalakan}
        {--dry-run : Hitung saja, tidak menulis}';

    protected $description = 'Sesuaikan total poin season aktif agar level tertinggi sesuai target';

    public function handle(): int
    {
        $season = ExpService::currentSeason();

        if (! $season) {
            $this->error('Tidak ada season aktif');

            return self::FAILURE;
        }

        $target = max(1, (int) $this->option('target'));

        $rows = DB::table('user_exp_events')
            ->where('season_id', $season->id)
            ->groupBy('user_id')
            ->selectRaw('user_id, COALESCE(SUM(exp),0) as points')
            ->get();

        $users = DB::table('users')->whereIn('id', $rows->pluck('user_id'))->select('id', 'created_at')->get()->keyBy('id');

        $totals = $rows->map(function ($r) use ($season, $users) {
            $loyalty = ExpService::seasonLoyaltyExp($users[$r->user_id]->created_at ?? null, $season->starts_at);

            return [
                'user_id' => (int) $r->user_id,
                'points' => (int) $r->points,
                'loyalty' => $loyalty,
                'total' => (int) $r->points + $loyalty,
            ];
        })->sortByDesc('total')->values();

        $top = $totals->first();

        if (! $top || $top['total'] <= 0) {
            $this->error('Belum ada poin untuk diskalakan');

            return self::FAILURE;
        }

        $factor = $target / $top['total'];
        $this->info("Season {$season->roman}: teratas {$top['total']} → target {$target} (faktor " . round($factor, 4) . ')');

        $changes = 0;

        DB::beginTransaction();

        foreach ($totals as $row) {
            $desired = $row['user_id'] === $top['user_id']
                ? $target
                : (int) round($row['total'] * $factor);

            $delta = $desired - $row['total'];

            if ($delta === 0) {
                continue;
            }

            $this->line("user {$row['user_id']}: {$row['total']} → {$desired} (" . ($delta > 0 ? '+' : '') . "{$delta})");
            $changes++;

            if ($this->option('dry-run')) {
                continue;
            }

            DB::table('user_exp_events')->insert([
                'user_id' => $row['user_id'],
                'source_type' => 'bonus',
                'item_id' => 'season-scale',
                'unit_key' => 'scale-' . $season->id . '-' . $row['user_id'],
                'exp' => $delta,
                'is_backfill' => true,
                'earned_on' => Carbon::now(ExpService::TZ)->toDateString(),
                'season_id' => $season->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->warn("DRY RUN: {$changes} user akan disesuaikan (tidak ada yang disimpan)");
        } else {
            DB::commit();
            Cache::forget(ExpService::LEADERBOARD_CACHE);
            Cache::forget('kz_exp_current_season');
            $this->info("Selesai: {$changes} user disesuaikan");
        }

        return self::SUCCESS;
    }
}
