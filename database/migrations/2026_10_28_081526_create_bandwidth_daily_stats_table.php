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
        Schema::create('bandwidth_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->index();
            $table->bigInteger('total_bytes')->default(0);
            $table->bigInteger('movie_bytes')->default(0);
            $table->bigInteger('series_bytes')->default(0);
            $table->bigInteger('anime_bytes')->default(0);
            $table->bigInteger('dracin_bytes')->default(0);
            $table->bigInteger('manga_bytes')->default(0);
            $table->bigInteger('proxy_bytes')->default(0);
            $table->bigInteger('other_bytes')->default(0);
            $table->integer('requests_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandwidth_daily_stats');
    }
};
