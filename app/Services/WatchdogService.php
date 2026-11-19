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
     * Jalankan pemeriksaan kesehatan menyeluruh (Anime, Komik)
     * dan lakukan Self-Healing otomatis jika ditemukan masalah.
     */
    public static function checkAndHeal(): array
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'anime' => self::checkAnimeVault(),
            'comics' => self::checkComics(),
            'actions_taken' => [],
        ];

        // 1. Self-Healing Anime jika ada Poster yang Hilang / Kosong
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
