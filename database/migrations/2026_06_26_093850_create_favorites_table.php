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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('item_id');
            $table->string('item_type'); // movie, series, dracin, anime, bstation
            $table->string('title');
            $table->text('thumbnail')->nullable();
            $table->text('url')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'item_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
