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
        Schema::create('native_comics', function (Blueprint $table) {
            $table->id();
            $table->string('upstream_id')->unique()->nullable(); // UUID dari Shinigami jika ada
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('alt_title')->nullable();
            $table->string('cover_path')->nullable(); // local relative path misal /storage/comic-covers/{slug}.webp
            $table->string('original_cover_url')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('manhwa'); // manhwa, manga, manhua
            $table->string('status')->default('Ongoing');
            $table->float('rating')->nullable();
            $table->json('genres')->nullable();
            $table->string('author')->nullable();
            $table->string('latest_chapter')->nullable();
            $table->timestamp('latest_chapter_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'updated_at']);
        });

        Schema::create('native_comic_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('native_comics')->cascadeOnDelete();
            $table->string('upstream_chapter_id')->nullable(); // UUID Shinigami untuk fetch on-demand
            $table->string('chapter_slug'); // misal 'chapter-1' atau '920'
            $table->float('chapter_number');
            $table->string('chapter_title')->nullable();
            $table->json('pages')->nullable(); // array of URLs: [['index' => 1, 'url' => '...'], ...]
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['comic_id', 'chapter_number']);
            $table->index(['comic_id', 'chapter_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('native_comic_chapters');
        Schema::dropIfExists('native_comics');
    }
};
