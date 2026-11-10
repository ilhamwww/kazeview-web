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

\Illuminate\Support\Facades\Schedule::command('kz:sync-indonesia-vault')->twiceDaily(6, 18);
