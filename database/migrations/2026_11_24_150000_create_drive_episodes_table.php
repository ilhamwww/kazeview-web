<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_episodes', function (Blueprint $table) {
            $table->id();
            $table->string('anime_path')->index();
            $table->string('episode_id')->unique();
            $table->string('title')->nullable();
            $table->string('rel_path');
            $table->string('quality', 16)->default('1080p');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('md5', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_episodes');
    }
};
