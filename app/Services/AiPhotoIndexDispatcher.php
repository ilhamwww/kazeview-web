<?php

namespace App\Services;

use App\Jobs\IndexMotorPhoto;
use App\Models\Content;
use App\Models\ListDownloaded;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AiPhotoIndexDispatcher
{
    public function dispatchForContent(Content $content): int
    {
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

        $content->update([
            'ai_index_status' => 'queued',
            'ai_index_message' => 'Foto sedang dijadwalkan untuk AI indexing.',
            'ai_index_started_at' => now(),
            'ai_index_finished_at' => null,
        ]);

        ListDownloaded::query()
            ->whereHas('download', fn ($query) => $query->where('id_content', $content->getKey()))
            ->orderBy('id')
            ->chunkById(100, function ($photos) use (&$total): void {
                foreach ($photos as $photo) {
                    IndexMotorPhoto::dispatch((int) $photo->getKey())->afterCommit();
                    $total++;
                }
            });

        if ($total === 0) {
            $content->update([
                'ai_index_status' => $content->drive_download_status === 'completed'
                    ? 'empty'
                    : 'waiting_for_download',
                'ai_index_message' => $content->drive_download_status === 'completed'
                    ? 'Belum ada foto hasil download untuk di-index.'
                    : 'Menunggu proses Download Data selesai.',
                'ai_index_finished_at' => now(),
            ]);
        } else {
            $content->update([
                'ai_index_message' => "{$total} foto dijadwalkan untuk AI indexing.",
            ]);
        }

        return $total;
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('photo_motor_search_indexes')
            && Schema::hasColumn('contents', 'ai_photo_search_enabled');
    }
}