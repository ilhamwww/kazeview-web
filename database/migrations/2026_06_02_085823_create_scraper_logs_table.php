<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraper_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('anoboy');
            $table->integer('page')->unsigned();
            $table->string('url')->nullable();
            $table->integer('items_found')->default(0);
            $table->integer('items_saved')->default(0);
            $table->integer('items_skipped')->default(0);
            $table->string('status')->default('success'); // success, error, skipped
            $table->text('error_message')->nullable();
            $table->float('duration_seconds')->nullable();
            $table->timestamps();

            $table->unique(['source', 'page']);
            $table->index('source');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraper_logs');
    }
};