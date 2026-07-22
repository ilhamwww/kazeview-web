<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the metadata required by the public event preview page.
     */
    public function up(): void
    {
        if (! Schema::hasTable('contents')) {
            return;
        }

        Schema::table('contents', function (Blueprint $table): void {
            if (! Schema::hasColumn('contents', 'event_location')) {
                $table->string('event_location')->nullable();
            }

            if (! Schema::hasColumn('contents', 'event_date')) {
                $table->date('event_date')->nullable();
            }

            if (! Schema::hasColumn('contents', 'preview_type')) {
                $table->string('preview_type', 20)->default('PHOTOGRAPHY');
            }

            if (! Schema::hasColumn('contents', 'preview_category')) {
                $table->string('preview_category', 50)->nullable();
            }

            if (! Schema::hasColumn('contents', 'is_new')) {
                $table->boolean('is_new')->default(false);
            }

            if (! Schema::hasColumn('contents', 'image_position')) {
                $table->string('image_position', 30)->default('50% 50%');
            }

            if (! Schema::hasColumn('contents', 'film_duration')) {
                $table->string('film_duration', 10)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contents')) {
            return;
        }

        Schema::table('contents', function (Blueprint $table): void {
            foreach ([
                'event_location',
                'event_date',
                'preview_type',
                'preview_category',
                'is_new',
                'image_position',
                'film_duration',
            ] as $column) {
                if (Schema::hasColumn('contents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};