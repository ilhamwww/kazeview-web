<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scraper_settings', function (Blueprint $table) {
            $table->string('dramabuzz_api_url')->default('https://api.dramabuzz.sbs/api/status');
            $table->string('dramabuzz_api_key')->default('5193CD21848193E43FC399BA4D73BB13');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraper_settings', function (Blueprint $table) {
            $table->dropColumn(['dramabuzz_api_url', 'dramabuzz_api_key']);
        });
    }
};