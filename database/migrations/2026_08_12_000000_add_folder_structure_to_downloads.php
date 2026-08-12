<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_folders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('download_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('google_folder_id');
            $table->string('parent_google_folder_id')->nullable();
            $table->string('name');
            $table->string('relative_path', 1000);
            $table->unsignedInteger('depth')->default(1);
            $table->unsignedInteger('image_count')->default(0);
            $table->unsignedInteger('total_image_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['download_id', 'google_folder_id']);
            $table->index(['download_id', 'parent_id']);
            $table->foreign('parent_id')
                ->references('id')
                ->on('download_folders')
                ->nullOnDelete();
        });

        Schema::table('list_downloaded', function (Blueprint $table): void {
            $table->unsignedBigInteger('download_folder_id')->nullable()->after('id_download');
            $table->string('relative_path', 1200)->nullable()->after('id_folder');
            $table->string('mime_type')->nullable()->after('relative_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->timestamp('drive_modified_at')->nullable()->after('file_size');

            $table->index('download_folder_id');
            $table->foreign('download_folder_id')
                ->references('id')
                ->on('download_folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('list_downloaded', function (Blueprint $table): void {
            $table->dropForeign(['download_folder_id']);
            $table->dropIndex(['download_folder_id']);
            $table->dropColumn([
                'download_folder_id',
                'relative_path',
                'mime_type',
                'file_size',
                'drive_modified_at',
            ]);
        });

        Schema::dropIfExists('download_folders');
    }
};