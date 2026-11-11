<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_vault_items')) {
            Schema::create('media_vault_items', function (Blueprint $table) {
                $table->id();
                $table->string('upstream_id')->nullable()->index(); // IDLIX UUID / Rebahin ID
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('type')->default('movie')->index(); // 'movie', 'series', 'indonesia'
                $table->string('quality')->nullable();
                $table->string('year', 10)->nullable()->index();
                $table->float('rating', 4, 1)->nullable()->index();
                $table->integer('comments')->default(0);
                $table->string('duration', 50)->nullable();
                $table->string('genre')->nullable();
                $table->json('genres')->nullable();
                $table->text('thumbnail')->nullable();
                $table->text('banner')->nullable();
                $table->text('logo')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_featured')->default(false)->index();
                $table->boolean('is_trending')->default(false)->index();
                $table->boolean('is_published')->default(true)->index();
                $table->string('source')->default('idlix')->index();
                $table->timestamp('last_synced_at')->nullable()->index();
                $table->timestamps();

                $table->index(['type', 'is_published', 'created_at']);
                $table->index(['type', 'rating']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_vault_items');
    }
};
