<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sistem EXP: setiap riwayat tontonan tersimpan, beri EXP untuk episode/movie baru
        \App\Models\WatchHistory::saved(function (\App\Models\WatchHistory $history) {
            try {
                \App\Services\ExpService::awardFromWatchHistory($history);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('EXP award skipped', ['error' => $e->getMessage()]);
            }
        });

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event) {
                \App\Models\LoginHistory::record(
                    $event->user->id,
                    request()->ip() ?? '',
                    request()->userAgent() ?? ''
                );
            }
        );
    }
}
