<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->boolean('ai_photo_search_enabled')
                ->default(false)
                ->index();
            $table->string('ai_index_status', 24)
                ->nullable()
                ->index();
            $table->text('ai_index_message')->nullable();
            $table->timestamp('ai_index_started_at')->nullable();
            $table->timestamp('ai_index_finished_at')->nullable();
        });

        Schema::create('photo_motor_search_indexes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('list_downloaded_id');
            $table->integer('content_id');
            $table->string('source_hash', 64);
            $table->json('descriptor')->nullable();
            $table->binary('embedding')->nullable();
            $table->unsignedSmallInteger('embedding_dimensions')->nullable();
            $table->string('vision_model');
            $table->string('embedding_model');
            $table->string('prompt_version', 32);
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique('list_downloaded_id');
            $table->index(['content_id', 'status']);
            $table->index(['content_id', 'source_hash']);
            $table->foreign('list_downloaded_id')
                ->references('id')
                ->on('list_downloaded')
                ->cascadeOnDelete();
            $table->foreign('content_id')
                ->references('id')
                ->on('contents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_motor_search_indexes');

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropIndex(['ai_photo_search_enabled']);
            $table->dropIndex(['ai_index_status']);
            $table->dropColumn([
                'ai_photo_search_enabled',
                'ai_index_status',
                'ai_index_message',
                'ai_index_started_at',
                'ai_index_finished_at',
            ]);
        });
    }
};