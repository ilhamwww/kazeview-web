<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rating kualitas foto hasil penilaian AI:
 *  - quality_rating      : nilai akhir 1-10 (dipakai untuk urutan galeri)
 *  - quality_focus       : ketajaman/ketepatan fokus pada objek utama 1-10
 *  - quality_framing     : kelengkapan objek dalam frame 1-10
 *  - quality_subject_cut : true bila objek utama terpotong frame
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('photo_motor_search_indexes')) {
            return;
        }

        Schema::table('photo_motor_search_indexes', function (Blueprint $table): void {
            if (! Schema::hasColumn('photo_motor_search_indexes', 'quality_rating')) {
                $table->smallInteger('quality_rating')->nullable();
                $table->smallInteger('quality_focus')->nullable();
                $table->smallInteger('quality_framing')->nullable();
                $table->boolean('quality_subject_cut')->nullable();
                $table->text('quality_note')->nullable();
                $table->string('quality_model', 100)->nullable();
                $table->timestamp('quality_rated_at')->nullable();

                $table->index('quality_rating', 'photo_motor_search_quality_rating_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('photo_motor_search_indexes')) {
            return;
        }

        Schema::table('photo_motor_search_indexes', function (Blueprint $table): void {
            if (Schema::hasColumn('photo_motor_search_indexes', 'quality_rating')) {
                $table->dropIndex('photo_motor_search_quality_rating_index');
                $table->dropColumn([
                    'quality_rating',
                    'quality_focus',
                    'quality_framing',
                    'quality_subject_cut',
                    'quality_note',
                    'quality_model',
                    'quality_rated_at',
                ]);
            }
        });
    }
};
