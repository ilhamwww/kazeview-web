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
        Schema::table('native_comics', function (Blueprint $table) {
            $table->string('artist')->nullable()->after('author');
            $table->string('format')->nullable()->after('type'); // Manhua, Manhwa, Manga
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('native_comics', function (Blueprint $table) {
            $table->dropColumn(['artist', 'format']);
        });
    }
};
