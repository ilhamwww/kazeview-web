<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exp_seasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number');
            $table->string('roman', 12);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique('number');
        });

        Schema::create('exp_season_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('exp_seasons')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('points')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();
            $table->unique(['season_id', 'user_id']);
            $table->index('user_id');
        });

        if (! Schema::hasColumn('user_exp_events', 'season_id')) {
            Schema::table('user_exp_events', function (Blueprint $table) {
                $table->unsignedBigInteger('season_id')->nullable()->index();
            });
        }

        // Season I: berjalan sampai 31 Desember 2026, reset pertama 1 Januari 2027.
        $start = '2026-11-24 00:00:00';
        $end = '2026-12-31 23:59:59';

        $seasonId = DB::table('exp_seasons')->insertGetId([
            'number' => 1,
            'roman' => 'I',
            'starts_at' => $start,
            'ends_at' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_exp_events')->whereNull('season_id')->update(['season_id' => $seasonId]);
    }

    public function down(): void
    {
        Schema::table('user_exp_events', function (Blueprint $table) {
            $table->dropColumn('season_id');
        });

        Schema::dropIfExists('exp_season_stats');
        Schema::dropIfExists('exp_seasons');
    }
};
