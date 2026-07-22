<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('scraper_settings')
            ->orderBy('id')
            ->get(['id', 'dramabuzz_network_statuses'])
            ->each(function (object $setting): void {
                if ($setting->dramabuzz_network_statuses === null) {
                    return;
                }

                $statuses = json_decode(
                    $setting->dramabuzz_network_statuses,
                    true,
                );

                if (!is_array($statuses) || in_array('raptdrama', $statuses, true)) {
                    return;
                }

                $statuses[] = 'raptdrama';

                DB::table('scraper_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'dramabuzz_network_statuses' => json_encode(
                            array_values($statuses),
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('scraper_settings')
            ->orderBy('id')
            ->get(['id', 'dramabuzz_network_statuses'])
            ->each(function (object $setting): void {
                if ($setting->dramabuzz_network_statuses === null) {
                    return;
                }

                $statuses = json_decode(
                    $setting->dramabuzz_network_statuses,
                    true,
                );

                if (!is_array($statuses)) {
                    return;
                }

                DB::table('scraper_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'dramabuzz_network_statuses' => json_encode(
                            array_values(array_filter(
                                $statuses,
                                fn (string $slug): bool => $slug !== 'raptdrama',
                            )),
                        ),
                    ]);
            });
    }
};