<?php

namespace App\Services;

use App\Jobs\IndexMotorPhoto;
use App\Models\Content;
use App\Models\ListDownloaded;
use App\Models\PhotoMotorSearchIndex;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AiPhotoIndexDispatcher
{
    public function dispatchForContent(Content $content): int
    {
        return $this->dispatch($content);
    }

    public function retryFailedForContent(Content $content): int
    {
        return $this->dispatch($content, failedOnly: true);
    }

    /**
     * Hanya jadwalkan foto yang belum punya index siap pakai.
     * Foto yang sudah terindeks tidak dijadwalkan ulang walaupun nama model
     * atau versi prompt berubah — jadi penambahan foto baru bersifat lanjutan,
     * bukan mengulang dari awal. Untuk scan ulang penuh pakai forceRescanForContent().
     */
    public function dispatchPendingForContent(Content $content): int
    {
        return $this->dispatch($content, pendingOnly: true);
    }

    public function forceRescanForContent(Content $content): int
    {
        return $this->dispatch($content, force: true);
    }

    private function dispatch(
        Content $content,
        bool $force = false,
        bool $failedOnly = false,
        bool $pendingOnly = false,
    ): int {
        $content->refresh();

        if (! $content->isPhotoContent() || ! $content->ai_photo_search_enabled) {
            return 0;
        }

        if (! $this->schemaReady()) {
            throw new RuntimeException('Schema AI Photo Search belum tersedia. Jalankan migration.');
        }

        if (! app(NineRouterAiService::class)->configured()) {
            $content->update([
                'ai_index_status' => 'configuration_required',
                'ai_index_message' => 'NINEROUTER_API_KEY belum dikonfigurasi.',
                'ai_index_finished_at' => now(),
            ]);

            return 0;
        }

        $total = 0;

        $modeMessage = match (true) {
            $force => 'Scan ulang seluruh foto sedang dijadwalkan.',
            $failedOnly => 'Foto yang gagal sedang dijadwalkan ulang.',
            $pendingOnly => 'Foto yang belum terindeks sedang dijadwalkan.',
            default => 'Foto sedang dijadwalkan untuk AI indexing.',
        };

        $content->update([
            'ai_index_status' => 'queued',
            'ai_index_message' => $modeMessage,
            'ai_index_started_at' => now(),
            'ai_index_finished_at' => null,
        ]);

        $photosQuery = ListDownloaded::query()
            ->whereHas('download', fn ($query) => $query->where('id_content', $content->getKey()));

        if ($force) {
            // Tandai index lama sebagai sedang diproses ulang supaya progres di
            // dashboard langsung turun, bukan tertahan di 100% sampai job selesai.
            PhotoMotorSearchIndex::query()
                ->where('content_id', $content->getKey())
                ->whereIn('status', ['indexed', 'failed', 'processing', 'pending'])
                ->update([
                    'status' => 'reindexing',
                    'error_message' => null,
                ]);
        }

        if ($failedOnly) {
            $photosQuery->whereHas(
                'motorSearchIndex',
                fn ($query) => $query->where('status', 'failed'),
            );
        }

        if ($pendingOnly) {
            // Foto yang sudah punya index siap pakai tidak dijadwalkan ulang.
            // Ini yang membuat penambahan foto baru bersifat lanjutan.
            $photosQuery->whereDoesntHave(
                'motorSearchIndex',
                fn ($query) => $query->where('status', 'indexed')->whereNotNull('embedding'),
            );
        }

        $photosQuery
            ->orderBy('id')
            ->chunkById(100, function ($photos) use (&$total, $force): void {
                foreach ($photos as $photo) {
                    IndexMotorPhoto::dispatch(
                        (int) $photo->getKey(),
                        $force,
                    )->afterCommit();
                    $total++;
                }
            });

        if ($total === 0) {
            $indexed = PhotoMotorSearchIndex::query()
                ->where('content_id', $content->getKey())
                ->whereIn('status', ['indexed', 'reindexing'])
                ->count();
            $failed = PhotoMotorSearchIndex::query()
                ->where('content_id', $content->getKey())
                ->where('status', 'failed')
                ->count();

            $status = match (true) {
                $indexed > 0 && $failed > 0 => 'partially_ready',
                $indexed > 0 => 'ready',
                $content->drive_download_status !== 'completed' => 'waiting_for_download',
                default => 'empty',
            };
            $message = match (true) {
                $failedOnly && $failed === 0 => 'Tidak ada foto gagal untuk dijadwalkan ulang.',
                $indexed > 0 && $failed > 0 => "{$indexed} foto siap, {$failed} foto gagal.",
                $indexed > 0 => "{$indexed} foto siap untuk pencarian AI.",
                $content->drive_download_status !== 'completed' => 'Menunggu proses Download Data selesai.',
                default => 'Belum ada foto hasil download untuk di-index.',
            };

            $content->update([
                'ai_index_status' => $status,
                'ai_index_message' => $message,
                'ai_index_finished_at' => now(),
            ]);
        } else {
            $message = match (true) {
                $force => "{$total} foto dijadwalkan untuk scan ulang penuh.",
                $failedOnly => "{$total} foto gagal dijadwalkan ulang.",
                $pendingOnly => "{$total} foto baru dijadwalkan. Foto yang sudah terindeks dilewati.",
                default => "{$total} foto dijadwalkan untuk AI indexing.",
            };

            $content->update(['ai_index_message' => $message]);
        }

        return $total;
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('photo_motor_search_indexes')
            && Schema::hasColumn('contents', 'ai_photo_search_enabled');
    }
}