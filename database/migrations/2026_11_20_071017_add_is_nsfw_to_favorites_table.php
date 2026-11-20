<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->boolean('is_nsfw')->default(false)->after('url');
        });

        // Doujin memang konten 18+, tandai sejak awal.
        DB::table('favorites')->where('item_type', 'doujin')->update(['is_nsfw' => true]);
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn('is_nsfw');
        });
    }
};
