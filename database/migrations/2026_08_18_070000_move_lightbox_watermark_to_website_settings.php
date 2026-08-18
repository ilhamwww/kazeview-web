<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->boolean('lightbox_watermark_enabled')
                ->default(false);
            $table->string('lightbox_watermark_image')
                ->nullable();
            $table->string('lightbox_watermark_position', 20)
                ->default('center');
            $table->unsignedTinyInteger('lightbox_watermark_size')
                ->default(30);
            $table->unsignedTinyInteger('lightbox_watermark_opacity')
                ->default(65);
        });

        if (
            Schema::hasColumn('contents', 'lightbox_watermark_enabled')
            && Schema::hasColumn('contents', 'lightbox_watermark_image')
        ) {
            $legacyWatermark = DB::table('contents')
                ->where('lightbox_watermark_enabled', true)
                ->whereNotNull('lightbox_watermark_image')
                ->orderBy('id')
                ->first([
                    'lightbox_watermark_enabled',
                    'lightbox_watermark_image',
                    'lightbox_watermark_position',
                    'lightbox_watermark_size',
                    'lightbox_watermark_opacity',
                ]);

            if ($legacyWatermark) {
                DB::table('website_settings')
                    ->orderBy('id')
                    ->limit(1)
                    ->update([
                        'lightbox_watermark_enabled' => true,
                        'lightbox_watermark_image' => $legacyWatermark->lightbox_watermark_image,
                        'lightbox_watermark_position' => $legacyWatermark->lightbox_watermark_position ?: 'center',
                        'lightbox_watermark_size' => $legacyWatermark->lightbox_watermark_size ?: 30,
                        'lightbox_watermark_opacity' => $legacyWatermark->lightbox_watermark_opacity ?: 65,
                    ]);
            }

            Schema::table('contents', function (Blueprint $table): void {
                $table->dropColumn([
                    'lightbox_watermark_enabled',
                    'lightbox_watermark_image',
                    'lightbox_watermark_position',
                    'lightbox_watermark_size',
                    'lightbox_watermark_opacity',
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->boolean('lightbox_watermark_enabled')
                ->default(false);
            $table->string('lightbox_watermark_image')
                ->nullable();
            $table->string('lightbox_watermark_position', 20)
                ->default('center');
            $table->unsignedTinyInteger('lightbox_watermark_size')
                ->default(30);
            $table->unsignedTinyInteger('lightbox_watermark_opacity')
                ->default(65);
        });

        $globalWatermark = DB::table('website_settings')
            ->orderBy('id')
            ->first([
                'lightbox_watermark_enabled',
                'lightbox_watermark_image',
                'lightbox_watermark_position',
                'lightbox_watermark_size',
                'lightbox_watermark_opacity',
            ]);

        if ($globalWatermark) {
            DB::table('contents')
                ->where(function ($query): void {
                    $query
                        ->whereNull('content_media_type')
                        ->orWhere('content_media_type', 'FOTO');
                })
                ->update([
                    'lightbox_watermark_enabled' => $globalWatermark->lightbox_watermark_enabled,
                    'lightbox_watermark_image' => $globalWatermark->lightbox_watermark_image,
                    'lightbox_watermark_position' => $globalWatermark->lightbox_watermark_position,
                    'lightbox_watermark_size' => $globalWatermark->lightbox_watermark_size,
                    'lightbox_watermark_opacity' => $globalWatermark->lightbox_watermark_opacity,
                ]);
        }

        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'lightbox_watermark_enabled',
                'lightbox_watermark_image',
                'lightbox_watermark_position',
                'lightbox_watermark_size',
                'lightbox_watermark_opacity',
            ]);
        });
    }
};