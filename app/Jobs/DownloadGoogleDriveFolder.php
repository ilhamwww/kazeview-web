<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\AiPhotoIndexDispatcher;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class DownloadGoogleDriveFolder implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3900;

    public function __construct(
        public int $contentId,
        public string $folderId,
    ) {
        $this->onQueue('google-drive');
    }

    public function uniqueId(): string
    {
        return "{$this->contentId}:{$this->folderId}";
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        GoogleDriveService $driveService,
        AiPhotoIndexDispatcher $aiPhotoIndexDispatcher,
    ): void
    {
        $content = Content::query()->find($this->contentId);

        if (! $content) {
            return;
        }

        if (! $content->isPhotoContent()) {
            $content->update([
                'drive_download_status' => null,
                'drive_download_message' => 'Video menggunakan link Google Drive tanpa diunduh ke server.',
                'drive_download_started_at' => null,
                'drive_download_finished_at' => null,
            ]);

            return;
        }

        $content->update([
            'drive_download_status' => 'processing',
            'drive_download_message' => 'Google Drive sedang dipindai dan diunduh.',
            'drive_download_started_at' => now(),
            'drive_download_finished_at' => null,
        ]);

        $result = $driveService->downloadFolder(
            $this->folderId,
            $content->getKey(),
        );

        if (($result['status'] ?? 'error') === 'error') {
            throw new RuntimeException(
                $result['message'] ?? 'Download Google Drive gagal.',
            );
        }

        $content->update([
            'drive_download_status' => 'completed',
            'drive_download_message' => $result['message'],
            'drive_download_finished_at' => now(),
        ]);

        $aiPhotoIndexDispatcher->dispatchForContent($content);
    }

    public function failed(?Throwable $exception): void
    {
        $content = Content::query()->find($this->contentId);

        if (! $content) {
            return;
        }

        if ($content->isVideoContent()) {
            $content->update([
                'drive_download_status' => null,
                'drive_download_message' => 'Video menggunakan link Google Drive tanpa diunduh ke server.',
                'drive_download_started_at' => null,
                'drive_download_finished_at' => null,
            ]);

            return;
        }

        $content->update([
            'drive_download_status' => 'failed',
            'drive_download_message' => $exception?->getMessage()
                ?? 'Download Google Drive gagal.',
            'drive_download_finished_at' => now(),
        ]);
    }
}