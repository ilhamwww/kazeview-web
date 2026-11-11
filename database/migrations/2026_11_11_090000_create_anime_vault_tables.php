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
        Schema::create('anime_vault_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->index();
            $table->string('alt_title')->nullable();
            $table->text('poster')->nullable();
            $table->text('banner')->nullable();
            $table->string('type')->default('TV')->index(); // 'TV', 'Movie', 'OVA', 'ONA'
            $table->string('status')->default('Ongoing')->index(); // 'Ongoing', 'Completed'
            $table->decimal('rating', 4, 2)->nullable()->index();
            $table->string('latest_episode')->nullable();
            $table->timestamp('latest_episode_at')->nullable()->index();
            $table->string('release_day')->nullable()->index(); // 'Senin', 'Selasa', ...
            $table->string('season')->nullable(); // 'Fall 2026', 'Spring 2026'
            $table->string('year', 10)->nullable()->index();
            $table->json('genres')->nullable();
            $table->text('synopsis')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_trending')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->string('source')->default('mirainime')->index(); // 'mirainime', 'otakudesu'
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes for fast catalog browsing and filter queries
            $table->index(['is_published', 'latest_episode_at']);
            $table->index(['is_published', 'status']);
            $table->index(['is_published', 'release_day']);
        });

        // Tabel untuk feed episode terbaru agar sinkron instan
        Schema::create('anime_vault_updates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->index();
            $table->string('title');
            $table->string('episode');
            $table->text('poster')->nullable();
            $table->string('type')->nullable();
            $table->string('rating')->nullable();
            $table->string('ago')->nullable();
            $table->bigInteger('released_timestamp')->nullable()->index();
            $table->string('source')->default('mirainime');
            $table->timestamps();

            $table->unique(['slug', 'episode', 'source']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anime_vault_updates');
        Schema::dropIfExists('anime_vault_items');
    }
};
