<?php

namespace App\Services;

use App\Models\AnimeVaultItem;
use App\Models\AnimeVaultUpdate;
use App\Models\NativeComic;
use App\Models\NativeComicChapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WatchdogService
{
    /**
     * Jalankan pemeriksaan kesehatan menyeluruh (IDLIX, Anime, Komik)
     * dan lakukan Self-Healing otomatis jika ditemukan masalah.
     */
    public static function checkAndHeal(): array
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'idlix' => self::checkIdlixResolver(),
            'anime' => self::checkAnimeVault(),
            'comics' => self::checkComics(),
            'actions_taken' => [],
        ];

        // 1. Self-Healing IDLIX jika Resolver Stuck / Down
        if ($report['idlix']['status'] !== 'ok') {
            $healResult = self::restartIdlixResolver($report['idlix']['reason'] ?? 'unknown');
            $report['actions_taken'][] = $healResult;
        }

        // 2. Self-Healing Anime jika ada Poster yang Hilang / Kosong
        if ($report['anime']['missing_posters_count'] > 0) {
            $healedCount = AnimeVaultService::backfillMissingPosters(20);
            $report['actions_taken'][] = [
                'target' => 'anime_posters',
                'action' => 'backfill_missing_posters',
                'fixed_items' => $healedCount,
            ];
            $report['anime']['missing_posters_count'] = AnimeVaultUpdate::whereNull('poster')->orWhere('poster', '')->count();
        }

        // Simpan status terakhir ke cache (tersedia untuk API / status check)
        Cache::put('kz_watchdog_last_status', $report, 3600);

        return $report;
    }

    /**
     * Periksa kesehatan microservice resolver IDLIX di NUC via SOCKS5 relay
     */
    protected static function checkIdlixResolver(): array
    {
        try {
            $res = Http::withOptions([
                'proxy' => 'socks5h://127.0.0.1:1055',
                'timeout' => 5,
            ])->get('http://100.110.54.105:8765/health');

            if ($res->successful()) {
                $data = $res->json();
                if (!empty($data['isBusy'])) {
                    // Cek apakah busy terlalu lama
                    $stuckCounter = (int) Cache::get('kz_idlix_resolver_busy_count', 0) + 1;
                    Cache::put('kz_idlix_resolver_busy_count', $stuckCounter, 300);

                    if ($stuckCounter >= 3) {
                        return [
                            'status' => 'stuck',
                            'reason' => "Resolver stuck busy for {$stuckCounter} consecutive cycles",
                            'details' => $data,
                        ];
                    }
                } else {
                    Cache::forget('kz_idlix_resolver_busy_count');
                }

                return [
                    'status' => 'ok',
                    'uptime' => $data['uptime'] ?? 0,
                    'isBusy' => $data['isBusy'] ?? false,
                    'cacheSize' => $data['cacheSize'] ?? 0,
                ];
            }

            return [
                'status' => 'error',
                'reason' => 'HTTP response ' . $res->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restart otomatis service idlix-resolver di NUC via SSH
     */
    protected static function restartIdlixResolver(string $reason): array
    {
        Log::warning("[Watchdog] Restarting idlix-resolver on NUC due to: {$reason}");

        $cmd = 'ssh -o ProxyCommand="nc -X 5 -x 127.0.0.1:1055 %h %p" -o ConnectTimeout=5 root@100.110.54.105 "systemctl restart idlix-resolver" 2>&1';
        $output = shell_exec($cmd);

        // Verifikasi setelah restart
        sleep(2);
        $recheck = self::checkIdlixResolver();

        return [
            'target' => 'idlix_resolver',
            'action' => 'systemctl restart idlix-resolver',
            'reason' => $reason,
            'success' => $recheck['status'] === 'ok',
            'post_status' => $recheck,
        ];
    }

    /**
     * Periksa kesehatan Anime Vault (poster & jadwal)
     */
    protected static function checkAnimeVault(): array
    {
        $missingItems = AnimeVaultItem::whereNull('poster')->orWhere('poster', '')->count();
        $missingUpdates = AnimeVaultUpdate::whereNull('poster')->orWhere('poster', '')->count();

        $schedule = AnimeVaultService::getWeeklySchedule();
        $emptyDays = [];
        foreach ($schedule as $day => $items) {
            if (empty($items)) {
                $emptyDays[] = $day;
            }
        }

        $latestUpdate = AnimeVaultUpdate::orderByDesc('id')->first();

        return [
            'status' => ($missingUpdates === 0 && empty($emptyDays)) ? 'ok' : 'degraded',
            'missing_posters_count' => $missingUpdates + $missingItems,
            'empty_schedule_days' => $emptyDays,
            'latest_update' => $latestUpdate ? [
                'title' => $latestUpdate->title,
                'episode' => $latestUpdate->episode,
                'created_at' => $latestUpdate->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Periksa integritas katalog dan chapter komik
     */
    protected static function checkComics(): array
    {
        $comicsCount = NativeComic::count();
        $chaptersCount = NativeComicChapter::count();
        $latestChapter = NativeComicChapter::orderByDesc('id')->first();

        return [
            'status' => ($comicsCount > 0 && $chaptersCount > 0) ? 'ok' : 'degraded',
            'total_comics' => $comicsCount,
            'total_chapters' => $chaptersCount,
            'latest_chapter' => $latestChapter ? [
                'chapter_title' => $latestChapter->chapter_title,
                'created_at' => $latestChapter->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
