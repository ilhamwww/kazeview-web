<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('contents')
            && ! Schema::hasColumn('contents', 'content_media_type')
        ) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->string('content_media_type', 10)
                    ->default('FOTO')
                    ->after('preview_type')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contents', 'content_media_type')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->dropIndex(['content_media_type']);
                $table->dropColumn('content_media_type');
            });
        }
    }
};