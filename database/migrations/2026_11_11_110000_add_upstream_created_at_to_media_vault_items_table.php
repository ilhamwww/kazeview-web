<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_vault_items')) {
            Schema::table('media_vault_items', function (Blueprint $table) {
                if (!Schema::hasColumn('media_vault_items', 'upstream_created_at')) {
                    $table->timestamp('upstream_created_at')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('media_vault_items')) {
            Schema::table('media_vault_items', function (Blueprint $table) {
                if (Schema::hasColumn('media_vault_items', 'upstream_created_at')) {
                    $table->dropColumn('upstream_created_at');
                }
            });
        }
    }
};
