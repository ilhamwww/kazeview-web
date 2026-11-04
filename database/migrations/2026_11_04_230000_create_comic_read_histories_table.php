<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat baca komik di server (sebelumnya hanya di localStorage).
        // Satu baris per user per komik; chapter terakhir & jumlah chapter dibaca diperbarui tiap buka reader.
        Schema::create('comic_read_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('comic_id', 255);
            $table->string('title', 500)->nullable();
            $table->string('cover', 1000)->nullable();
            $table->string('format', 30)->nullable();
            $table->string('provider', 20)->nullable();
            $table->string('last_chapter_id', 100)->nullable();
            $table->string('last_chapter_label', 100)->nullable();
            $table->json('read_chapters')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'comic_id']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comic_read_histories');
    }
};
