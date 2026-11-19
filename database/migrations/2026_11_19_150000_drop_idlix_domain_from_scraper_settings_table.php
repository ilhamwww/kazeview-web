<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('scraper_settings', 'idlix_domain')) {
            Schema::table('scraper_settings', function (Blueprint $table) {
                $table->dropColumn('idlix_domain');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('scraper_settings', 'idlix_domain')) {
            Schema::table('scraper_settings', function (Blueprint $table) {
                $table->string('idlix_domain')->nullable()->after('anoboy_domain');
            });
        }
    }
};
