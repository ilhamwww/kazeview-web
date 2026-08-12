<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_filter_categories')) {
            Schema::create(
                'content_filter_categories',
                function (Blueprint $table): void {
                    $table->id();
                    $table->string('name', 100);
                    $table->string('slug', 120)->unique();
                    $table->boolean('is_active')->default(true)->index();
                    $table->unsignedInteger('sort_order')->default(0)->index();
                    $table->timestamps();
                },
            );
        }

        if (
            Schema::hasTable('contents')
            && ! Schema::hasTable('content_content_filter_category')
        ) {
            Schema::create(
                'content_content_filter_category',
                function (Blueprint $table): void {
                    $table->foreignId('content_id')
                        ->constrained('contents')
                        ->cascadeOnDelete();
                    $table->foreignId('content_filter_category_id')
                        ->constrained('content_filter_categories')
                        ->cascadeOnDelete();

                    $table->primary([
                        'content_id',
                        'content_filter_category_id',
                    ], 'content_filter_category_pivot_primary');
                },
            );
        }

        $this->importLegacyCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('content_content_filter_category');
        Schema::dropIfExists('content_filter_categories');
    }

    private function importLegacyCategories(): void
    {
        if (
            ! Schema::hasTable('contents')
            || ! Schema::hasColumn('contents', 'preview_category')
            || ! Schema::hasTable('content_filter_categories')
            || ! Schema::hasTable('content_content_filter_category')
        ) {
            return;
        }

        DB::table('contents')
            ->select(['id', 'preview_category'])
            ->whereNotNull('preview_category')
            ->where('preview_category', '<>', '')
            ->orderBy('id')
            ->chunkById(200, function ($contents): void {
                foreach ($contents as $content) {
                    $tokens = preg_split(
                        '/[\s,;|]+/u',
                        trim((string) $content->preview_category),
                        -1,
                        PREG_SPLIT_NO_EMPTY,
                    ) ?: [];

                    foreach (array_unique($tokens) as $token) {
                        $name = Str::upper(trim($token));
                        $baseSlug = Str::slug($name);

                        if ($baseSlug === '') {
                            continue;
                        }

                        $category = DB::table(
                            'content_filter_categories',
                        )->where('slug', $baseSlug)->first();

                        if (! $category) {
                            $categoryId = DB::table(
                                'content_filter_categories',
                            )->insertGetId([
                                'name' => $name,
                                'slug' => $baseSlug,
                                'is_active' => true,
                                'sort_order' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $categoryId = $category->id;
                        }

                        $exists = DB::table(
                            'content_content_filter_category',
                        )
                            ->where('content_id', $content->id)
                            ->where(
                                'content_filter_category_id',
                                $categoryId,
                            )
                            ->exists();

                        if (! $exists) {
                            DB::table(
                                'content_content_filter_category',
                            )->insert([
                                'content_id' => $content->id,
                                'content_filter_category_id' => $categoryId,
                            ]);
                        }
                    }
                }
            }, 'id');
    }
};