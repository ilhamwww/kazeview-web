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
            $table->json('dramabuzz_network_statuses')->nullable()->after('dramabuzz_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraper_settings', function (Blueprint $table) {
            $table->dropColumn('dramabuzz_network_statuses');
        });
    }
};