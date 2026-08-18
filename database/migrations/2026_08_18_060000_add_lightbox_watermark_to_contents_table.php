<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->boolean('lightbox_watermark_enabled')
                ->default(false)
                ->after('image_position');
            $table->string('lightbox_watermark_image')
                ->nullable()
                ->after('lightbox_watermark_enabled');
            $table->string('lightbox_watermark_position', 20)
                ->default('center')
                ->after('lightbox_watermark_image');
            $table->unsignedTinyInteger('lightbox_watermark_size')
                ->default(30)
                ->after('lightbox_watermark_position');
            $table->unsignedTinyInteger('lightbox_watermark_opacity')
                ->default(65)
                ->after('lightbox_watermark_size');
        });
    }

    public function down(): void
    {
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
};