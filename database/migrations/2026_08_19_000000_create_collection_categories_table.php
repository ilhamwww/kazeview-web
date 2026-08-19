<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('collection_categories')) {
            Schema::create('collection_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 30)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $categories = collect([
            'PHOTOGRAPHY',
            'AUTOMOTIVE',
            'PORTRAITS',
            'EVENTS',
        ]);

        if (
            Schema::hasTable('collections')
            && Schema::hasColumn('collections', 'category')
        ) {
            $categories = $categories
                ->merge(
                    DB::table('collections')
                        ->whereNotNull('category')
                        ->where('category', '!=', '')
                        ->pluck('category'),
                );
        }

        $timestamp = now();

        $categories
            ->map(fn ($category): string => mb_strtoupper(trim((string) $category)))
            ->filter()
            ->unique()
            ->values()
            ->each(function (string $name, int $index) use ($timestamp): void {
                DB::table('collection_categories')->updateOrInsert(
                    ['name' => $name],
                    [
                        'sort_order' => $index,
                        'is_active' => true,
                        'updated_at' => $timestamp,
                        'created_at' => $timestamp,
                    ],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_categories');
    }
};