<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime_maps', function (Blueprint $table) {
            $table->id();
            $table->string('otaku_slug')->unique();
            $table->string('otaku_title');
            $table->string('animek_path')->nullable();
            $table->string('animek_title')->nullable();
            $table->unsignedBigInteger('anilist_id')->nullable();
            $table->string('source', 24)->default('anilist');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_maps');
    }
};
