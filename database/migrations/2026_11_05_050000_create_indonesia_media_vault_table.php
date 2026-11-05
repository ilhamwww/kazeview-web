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
        Schema::create('indonesia_media_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('raw_slug')->index();
            $table->string('target_slug')->nullable()->index();
            $table->string('title')->index();
            $table->string('type')->index(); // 'movie' | 'series'
            $table->string('year')->nullable()->index();
            $table->decimal('rating', 3, 1)->default(8.5);
            $table->string('quality')->default('1080p Ultra HD');
            $table->integer('duration')->nullable();
            $table->text('poster')->nullable();
            $table->text('backdrop')->nullable();
            $table->text('synopsis')->nullable();
            $table->json('genres')->nullable();
            $table->string('source')->default('moviebox')->index(); // 'moviebox' | 'server2'
            $table->string('provider_badge')->default('Server 1')->index(); // 'Server 1' | 'Server 2'
            $table->string('external_id')->nullable()->index();
            $table->text('watch_url')->nullable();
            $table->json('cached_detail')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Composite indexes for fast catalog filtering
            $table->index(['type', 'is_published', 'id']);
            $table->index(['source', 'is_published', 'id']);
            $table->index(['year', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_media_items');
    }
};
