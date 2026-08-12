<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (! Schema::hasColumn('contents', 'drive_download_status')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->string('drive_download_status')
                    ->nullable()
                    ->after('link')
                    ->index();
            });
        }

        if (! Schema::hasColumn('contents', 'drive_download_message')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->text('drive_download_message')
                    ->nullable()
                    ->after('drive_download_status');
            });
        }

        if (! Schema::hasColumn('contents', 'drive_download_started_at')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->timestamp('drive_download_started_at')
                    ->nullable()
                    ->after('drive_download_message');
            });
        }

        if (! Schema::hasColumn('contents', 'drive_download_finished_at')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->timestamp('drive_download_finished_at')
                    ->nullable()
                    ->after('drive_download_started_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contents', 'drive_download_status')) {
            Schema::table('contents', function (Blueprint $table): void {
                $table->dropIndex(['drive_download_status']);
            });
        }

        $columns = collect([
            'drive_download_status',
            'drive_download_message',
            'drive_download_started_at',
            'drive_download_finished_at',
        ])->filter(
            fn (string $column): bool =>
                Schema::hasColumn('contents', $column),
        )->all();

        if ($columns !== []) {
            Schema::table(
                'contents',
                fn (Blueprint $table) => $table->dropColumn($columns),
            );
        }

        // Queue dan cache tables sengaja dipertahankan saat rollback karena
        // tabel tersebut dapat digunakan oleh fitur Laravel lainnya atau
        // sudah tersedia sebelum migration ini dijalankan.
    }
};