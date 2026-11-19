<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kz:sync-indonesia-vault', function () {
    $this->info('Starting Indonesia Media Vault sync (Rebahin Only)...');
    $rb = \App\Services\IndonesiaCatalogVaultService::syncFromRebahin(5);
    $this->info("Synced Rebahin: {$rb} items");
    $purged = \App\Services\IndonesiaCatalogVaultService::purgeAdultContent();
    $this->info("Purged adult items: {$purged}");
    $total = \App\Models\IndonesiaMediaItem::where('source', 'server2')->count();
    $this->info("Total Vault Items in DB (Rebahin): {$total}");
})->purpose('Sync and archive Indonesian movies & series from Rebahin into local database vault');

Artisan::command('kz:sync-anime-vault', function () {
    $this->info('Starting Anime Catalog & Schedule sync into Database Vault...');
    $res = \App\Services\AnimeVaultService::syncFromUpstream();
    $this->info("Synced Homepage items: " . ($res['homepage_items'] ?? 0));
    $this->info("Synced Updates items: " . ($res['updates_items'] ?? 0));
    $this->info("Synced Schedule items: " . ($res['schedule_items'] ?? 0));
    $total = \App\Models\AnimeVaultItem::count();
    $this->info("Total Anime Items in DB: {$total}");
})->purpose('Sync and archive anime titles, updates, and schedule from upstream into local database vault');

Artisan::command('kz:watchdog', function () {
    $this->info('Executing KZSTRM Unified Watchdog & Self-Healing...');
    $report = \App\Services\WatchdogService::checkAndHeal();
    $this->line(json_encode($report, JSON_PRETTY_PRINT));
})->purpose('Health check and automatic self-healing for Anime Vault and Comics');

\Illuminate\Support\Facades\Schedule::command('kz:sync-indonesia-vault')->twiceDaily(6, 18);
\Illuminate\Support\Facades\Schedule::command('kz:sync-anime-vault')->everyFifteenMinutes();
\Illuminate\Support\Facades\Schedule::command('kz:watchdog')->everyFiveMinutes();

Artisan::command('kz:warm-filmbox', function () {
    $t = microtime(true);

    // Bangun ulang cache katalog di luar jalur pengguna.
    foreach (['fb_home_v1', 'fb_pool_movie', 'fb_pool_series', 'fb_indonesia_items_v1'] as $key) {
        \Illuminate\Support\Facades\Cache::forget($key);
    }

    \App\Services\FilmboxService::home();
    \App\Services\FilmboxService::pool('movie');
    \App\Services\FilmboxService::pool('series');
    $id = \App\Services\FilmboxService::indonesiaCatalog('all', 1, 24);

    $this->info('Cache filmbox siap dalam ' . round(microtime(true) - $t, 1) . 's, Indonesia=' . ($id['pagination']['total'] ?? 0));
})->purpose('Warm Filmbox catalog caches so visitors never wait for a rebuild');

\Illuminate\Support\Facades\Schedule::command('kz:warm-filmbox')->everyTenMinutes()->withoutOverlapping();
