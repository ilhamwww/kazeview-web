<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ScraperSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $setting = ScraperSetting::first();
        if ($setting) {
            $statuses = $setting->dramabuzz_network_statuses;
            if (is_array($statuses) && !in_array('goodshort', $statuses)) {
                $statuses[] = 'goodshort';
                $setting->dramabuzz_network_statuses = $statuses;
                $setting->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $setting = ScraperSetting::first();
        if ($setting) {
            $statuses = $setting->dramabuzz_network_statuses;
            if (is_array($statuses)) {
                $statuses = array_values(array_diff($statuses, ['goodshort']));
                $setting->dramabuzz_network_statuses = $statuses;
                $setting->save();
            }
        }
    }
};