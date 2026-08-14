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

    public function forceRescanForContent(Content $content): int
    {
        return $this->dispatch($content, force: true);
    }

    private function dispatch(
        Content $content,
        bool $force = false,
        bool $failedOnly = false,
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

        if ($failedOnly) {
            $photosQuery->whereHas(
                'motorSearchIndex',
                fn ($query) => $query->where('status', 'failed'),
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