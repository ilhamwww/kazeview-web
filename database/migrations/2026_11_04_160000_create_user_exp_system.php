<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ledger EXP: satu baris = satu unit (episode / movie / chapter) yang sudah memberi EXP.
        // Unique key mencegah EXP ganda saat episode yang sama ditonton ulang.
        Schema::create('user_exp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20);      // movie, series, anime, dracin, komik
            $table->string('item_id', 255);
            $table->string('unit_key', 100);        // nomor episode / chapter id
            $table->unsignedInteger('exp');         // EXP yang benar-benar diberikan (setelah cap harian)
            $table->boolean('is_backfill')->default(false);
            $table->date('earned_on');              // tanggal WIB, untuk batas harian
            $table->timestamps();

            $table->unique(['user_id', 'source_type', 'item_id', 'unit_key'], 'user_exp_unit_unique');
            $table->index(['user_id', 'earned_on']);
        });

        Schema::table('users', function (Blueprint $table) {
            // 'king' / 'queen' = akun Royal, tidak ikut ranking leaderboard
            $table->string('special_rank', 20)->nullable()->after('nsfw_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_exp_events');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('special_rank');
        });
    }
};
