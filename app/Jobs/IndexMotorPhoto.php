<?php

namespace App\Jobs;

use App\Models\Content;
use App\Models\ListDownloaded;
use App\Models\PhotoMotorSearchIndex;
use App\Services\NineRouterAiService;
use App\Support\FloatVector;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class IndexMotorPhoto implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public int $uniqueFor = 300;

    public function __construct(
        public int $listDownloadedId,
        public bool $force = false,
    ) {
        $this->onQueue('ai-indexing');
    }

    public function uniqueId(): string
    {
        return (string) $this->listDownloadedId;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(NineRouterAiService $ai): void
    {
        $photo = ListDownloaded::query()
            ->with('download.content')
            ->find($this->listDownloadedId);

        $content = $photo?->download?->content;

        if (! $photo || ! $content instanceof Content) {
            return;
        }

        if (! $content->isPhotoContent() || ! $content->ai_photo_search_enabled) {
            return;
        }

        $relativePath = $photo->storagePath();
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            $this->markFailed($photo, $content, 'File foto tidak ditemukan di storage.');

            return;
        }

        $sourcePath = $disk->path($relativePath);
        $sourceHash = hash_file('sha256', $sourcePath);

        if (! is_string($sourceHash)) {
            throw new RuntimeException('Hash file foto tidak dapat dibuat.');
        }

        $index = PhotoMotorSearchIndex::query()->firstOrNew([
            'list_downloaded_id' => $photo->getKey(),
        ]);

        if (
            ! $this->force
            && $index->exists
            && $index->status === 'indexed'
            && $index->source_hash === $sourceHash
            && $index->vision_model === $ai->visionModel()
            && $index->embedding_model === $ai->embeddingModel()
            && $index->prompt_version === $ai->promptVersion()
            && filled($index->embedding)
        ) {
            $this->refreshContentStatus($content);

            return;
        }

        $hasUsableIndex = $index->exists
            && filled($index->embedding)
            && is_array($index->descriptor);

        $processingAttributes = [
            'content_id' => $content->getKey(),
            'status' => $this->force && $hasUsableIndex
                ? 'reindexing'
                : 'processing',
            'attempts' => ((int) $index->attempts) + 1,
            'error_message' => null,
            'processing_started_at' => now(),
            'indexed_at' => $this->force && $hasUsableIndex
                ? $index->indexed_at
                : null,
        ];

        if (! ($this->force && $hasUsableIndex)) {
            $processingAttributes += [
                'source_hash' => $sourceHash,
                'vision_model' => $ai->visionModel(),
                'embedding_model' => $ai->embeddingModel(),
                'prompt_version' => $ai->promptVersion(),
            ];
        }

        $index->fill($processingAttributes)->save();

        $content->update([
            'ai_index_status' => 'indexing',
            'ai_index_message' => 'AI indexing sedang berjalan.',
        ]);

        $temporaryPath = $this->makeThumbnail($sourcePath);

        try {
            $content->refresh();

            if (! $content->ai_photo_search_enabled) {
                $index->update(['status' => 'pending']);

                return;
            }

            $descriptor = $ai->describeMotorcycle($temporaryPath, 'image/jpeg');
            $embedding = $ai->embed($ai->retrievalDescriptor($descriptor));

            $index->update([
                'source_hash' => $sourceHash,
                'vision_model' => $ai->visionModel(),
                'embedding_model' => $ai->embeddingModel(),
                'prompt_version' => $ai->promptVersion(),
                'descriptor' => $descriptor,
                'embedding' => FloatVector::encode($embedding),
                'embedding_dimensions' => count($embedding),
                'status' => 'indexed',
                'error_message' => null,
                'indexed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $index->update([
                // A forced refresh must not remove a previously usable index
                // merely because the provider failed during this attempt.
                'status' => $this->force && $hasUsableIndex
                    ? 'indexed'
                    : 'failed',
                'error_message' => $this->safeErrorMessage($exception),
            ]);

            throw $exception;
        } finally {
            @unlink($temporaryPath);
            $this->refreshContentStatus($content);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $photo = ListDownloaded::query()
            ->with('download.content')
            ->find($this->listDownloadedId);

        $content = $photo?->download?->content;

        if (! $photo || ! $content instanceof Content) {
            return;
        }

        $message = $exception
            ? $this->safeErrorMessage($exception)
            : 'AI indexing gagal.';
        $index = PhotoMotorSearchIndex::query()
            ->where('list_downloaded_id', $photo->getKey())
            ->first();

        if (
            $this->force
            && $index
            && filled($index->embedding)
            && is_array($index->descriptor)
        ) {
            $index->update([
                'status' => 'indexed',
                'error_message' => $message,
            ]);
            $this->refreshContentStatus($content);

            return;
        }

        $this->markFailed($photo, $content, $message);
    }

    private function makeThumbnail(string $sourcePath): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'kaze-ai-');

        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara AI tidak dapat dibuat.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($sourcePath);
        $image->scaleDown(width: 2048, height: 2048);

        file_put_contents(
            $temporaryPath,
            (string) $image->encode(new JpegEncoder(quality: 92)),
        );

        return $temporaryPath;
    }

    private function markFailed(
        ListDownloaded $photo,
        Content $content,
        string $message,
    ): void {
        PhotoMotorSearchIndex::query()->updateOrCreate(
            ['list_downloaded_id' => $photo->getKey()],
            [
                'content_id' => $content->getKey(),
                'source_hash' => str_repeat('0', 64),
                'vision_model' => (string) config('services.ninerouter.vision_model'),
                'embedding_model' => (string) config('services.ninerouter.embedding_model'),
                'prompt_version' => (string) config('services.ninerouter.prompt_version'),
                'status' => 'failed',
                'error_message' => mb_substr($message, 0, 2000),
                'processing_started_at' => now(),
            ],
        );

        $this->refreshContentStatus($content);
    }

    private function safeErrorMessage(Throwable $exception): string
    {
        $source = $exception->getPrevious() ?: $exception;
        $message = $source->getMessage();

        if (! mb_check_encoding($message, 'UTF-8')) {
            $message = iconv('UTF-8', 'UTF-8//IGNORE', $message) ?: '';
        }

        $message = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $message,
        ) ?? '';

        $message = trim($message);

        return mb_substr(
            $message !== '' ? $message : 'AI indexing gagal.',
            0,
            1000,
        );
    }

    private function refreshContentStatus(Content $content): void
    {
        if (! $content->fresh()?->ai_photo_search_enabled) {
            return;
        }

        $query = PhotoMotorSearchIndex::query()
            ->where('content_id', $content->getKey());

        $indexed = (clone $query)->where('status', 'indexed')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $remaining = (clone $query)->whereIn('status', ['pending', 'processing', 'reindexing'])->count();

        if ($remaining > 0) {
            $status = 'indexing';
            $message = "{$indexed} foto selesai, {$remaining} masih diproses.";
            $finishedAt = null;
        } elseif ($failed > 0) {
            $status = $indexed > 0 ? 'partially_ready' : 'failed';
            $message = "{$indexed} foto selesai, {$failed} gagal.";
            $finishedAt = now();
        } else {
            $status = 'ready';
            $message = "{$indexed} foto siap untuk pencarian AI.";
            $finishedAt = now();
        }

        $content->update([
            'ai_index_status' => $status,
            'ai_index_message' => $message,
            'ai_index_finished_at' => $finishedAt,
        ]);
    }
}