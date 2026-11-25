<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * crop_severity: none | minor | major
 * Dipakai supaya potongan kecil di tepi (mis. ujung ban) tidak langsung
 * dianggap "objek terpotong" seperti pada versi boolean sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('photo_motor_search_indexes')) {
            return;
        }

        if (Schema::hasColumn('photo_motor_search_indexes', 'quality_crop_severity')) {
            return;
        }

        Schema::table('photo_motor_search_indexes', function (Blueprint $table): void {
            $table->string('quality_crop_severity', 10)->nullable()->after('quality_subject_cut');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('photo_motor_search_indexes') || ! Schema::hasColumn('photo_motor_search_indexes', 'quality_crop_severity')) {
            return;
        }

        Schema::table('photo_motor_search_indexes', function (Blueprint $table): void {
            $table->dropColumn('quality_crop_severity');
        });
    }
};
