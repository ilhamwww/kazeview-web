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
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->boolean('lightbox_watermark_tile_enabled')->default(true)->after('lightbox_watermark_opacity');
            $table->unsignedTinyInteger('lightbox_watermark_tile_opacity')->default(22)->after('lightbox_watermark_tile_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'lightbox_watermark_tile_enabled',
                'lightbox_watermark_tile_opacity',
            ]);
        });
    }
};
