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
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->float('progress_seconds')->default(0)->after('url');       // detik terakhir ditonton
            $table->float('duration_seconds')->default(0)->after('progress_seconds'); // total durasi konten
            $table->float('progress_percent')->default(0)->after('duration_seconds'); // persen progress (0-100)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_histories', function (Blueprint $table) {
            $table->dropColumn(['progress_seconds', 'duration_seconds', 'progress_percent']);
        });
    }
};
