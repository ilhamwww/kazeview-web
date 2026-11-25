<?php

namespace App\Jobs;

use App\Models\ListDownloaded;
use App\Models\PhotoMotorSearchIndex;
use App\Services\NineRouterAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Memberi rating kualitas (fokus & kelengkapan objek) pada satu foto
 * tanpa mengulang seluruh proses indexing.
 *
 * Dipakai untuk foto yang sudah terlanjur terindeks dengan prompt lama,
 * sehingga belum punya nilai quality_rating.
 */
class RatePhotoQuality implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public int $listDownloadedId,
    ) {
        $this->onQueue('ai-indexing');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(NineRouterAiService $ai): void
    {
        $photo = ListDownloaded::query()
            ->with('download.content')
            ->find($this->listDownloadedId);

        if (! $photo) {
            return;
        }

        $content = $photo->download?->content;

        if (! $content) {
            return;
        }

        $disk = Storage::disk('public');
        $relativePath = $photo->storagePath();

        if (! $disk->exists($relativePath)) {
            return;
        }

        $quality = $ai->assessQuality($disk->path($relativePath));

        PhotoMotorSearchIndex::query()->updateOrCreate(
            ['list_downloaded_id' => $photo->getKey()],
            [
                'content_id' => $content->getKey(),
                'quality_rating' => $quality['rating'],
                'quality_focus' => $quality['focus'],
                'quality_framing' => $quality['framing'],
                'quality_subject_cut' => $quality['subject_cut'],
                'quality_crop_severity' => $quality['crop_severity'] ?? null,
                'quality_note' => $quality['note'],
                'quality_model' => $ai->visionModel(),
                'quality_rated_at' => now(),
            ],
        );
    }

    public function failed(?Throwable $exception): void
    {
        // Rating bersifat pelengkap: kegagalan tidak boleh mengubah status index
        // yang sudah ada, cukup dicatat di log aplikasi.
        report($exception);
    }
}
