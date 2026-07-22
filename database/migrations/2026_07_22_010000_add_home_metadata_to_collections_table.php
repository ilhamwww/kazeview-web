<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('collections')) {
            return;
        }

        Schema::table('collections', function (Blueprint $table): void {
            if (! Schema::hasColumn('collections', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('collections', 'category')) {
                $table->string('category', 30)->default('PHOTOGRAPHY');
            }

            if (! Schema::hasColumn('collections', 'media_type')) {
                $table->string('media_type', 20)->default('PHOTOGRAPHY');
            }

            if (! Schema::hasColumn('collections', 'duration')) {
                $table->string('duration', 10)->nullable();
            }

            if (! Schema::hasColumn('collections', 'project_year')) {
                $table->unsignedSmallInteger('project_year')->nullable();
            }

            if (! Schema::hasColumn('collections', 'image_position')) {
                $table->string('image_position', 30)->default('50% 50%');
            }

            if (! Schema::hasColumn('collections', 'link')) {
                $table->string('link')->nullable();
            }

            if (! Schema::hasColumn('collections', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }

            if (! Schema::hasColumn('collections', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('collections')) {
            return;
        }

        Schema::table('collections', function (Blueprint $table): void {
            foreach ([
                'title',
                'category',
                'media_type',
                'duration',
                'project_year',
                'image_position',
                'link',
                'is_featured',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('collections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};