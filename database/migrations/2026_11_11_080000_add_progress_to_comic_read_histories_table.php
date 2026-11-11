<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comic_read_histories', function (Blueprint $table) {
            $table->integer('last_progress')->default(0)->after('last_chapter_label');
            $table->integer('last_panel_index')->default(1)->after('last_progress');
            $table->integer('last_scroll_y')->default(0)->after('last_panel_index');
            $table->decimal('last_chapter_number', 8, 2)->nullable()->after('last_scroll_y');
        });
    }

    public function down(): void
    {
        Schema::table('comic_read_histories', function (Blueprint $table) {
            $table->dropColumn(['last_progress', 'last_panel_index', 'last_scroll_y', 'last_chapter_number']);
        });
    }
};
