<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('collections')
            && ! Schema::hasColumn('collections', 'video')
        ) {
            Schema::table('collections', function (Blueprint $table): void {
                $table->string('video')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('collections')
            && Schema::hasColumn('collections', 'video')
        ) {
            Schema::table('collections', function (Blueprint $table): void {
                $table->dropColumn('video');
            });
        }
    }
};