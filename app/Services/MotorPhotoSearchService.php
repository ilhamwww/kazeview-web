<?php

namespace App\Services;

use App\Models\Content;
use App\Models\PhotoMotorSearchIndex;
use App\Support\FloatVector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

class MotorPhotoSearchService
{
    public function __construct(private NineRouterAiService $ai)
    {
    }

    /**
     * @return array{descriptor: array<string, mixed>, matches: Collection<int, array<string, mixed>>}
     */
    public function search(Content $content, UploadedFile $upload): array
    {
        if (! $content->is_active || ! $content->isPhotoContent()) {
            throw new RuntimeException('Content foto tidak tersedia.');
        }

        if (! $content->ai_photo_search_enabled) {
            throw new RuntimeException('AI Photo Search tidak aktif untuk Content ini.');
        }

        if (! $this->ai->configured()) {
            throw new RuntimeException('Provider AI belum dikonfigurasi.');
        }

        $temporaryPath = $this->makeThumbnail($upload->getRealPath());

        try {
            $descriptor = $this->ai->describeMotorcycle($temporaryPath, 'image/jpeg');
            $queryVector = FloatVector::normalize(
                $this->ai->embed($this->ai->canonicalDescriptor($descriptor)),
            );
        } finally {
            @unlink($temporaryPath);
        }

        $limit = max(1, min(50, (int) config('services.ninerouter.search_limit', 20)));
        $disk = Storage::disk('public');

        $matches = PhotoMotorSearchIndex::query()
            ->where('content_id', $content->getKey())
            ->where('status', 'indexed')
            ->where('embedding_dimensions', count($queryVector))
            ->whereNotNull('embedding')
            ->with('photo.folder')
            ->get()
            ->map(function (PhotoMotorSearchIndex $index) use ($queryVector, $disk): ?array {
                $photo = $index->photo;

                if (! $photo || ! $disk->exists($photo->storagePath())) {
                    return null;
                }

                $binary = $index->getRawOriginal('embedding');

                if (is_resource($binary)) {
                    $binary = stream_get_contents($binary);
                }

                if (! is_string($binary) || $binary === '') {
                    return null;
                }

                try {
                    $score = FloatVector::dot(
                        $queryVector,
                        FloatVector::decode($binary),
                    );
                } catch (\InvalidArgumentException) {
                    return null;
                }

                return [
                    'id' => $photo->getKey(),
                    'name' => $photo->file_name,
                    'url' => $disk->url($photo->storagePath()),
                    'score' => round($score, 6),
                    'confidence' => $this->confidence($score),
                    'folder' => $photo->folder
                        ? [
                            'id' => $photo->folder->getKey(),
                            'name' => $photo->folder->name,
                            'relative_path' => $photo->folder->relative_path,
                        ]
                        : null,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return [
            'descriptor' => $descriptor,
            'matches' => $matches,
        ];
    }

    private function makeThumbnail(string $sourcePath): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'kaze-search-');

        if ($temporaryPath === false) {
            throw new RuntimeException('File sementara pencarian tidak dapat dibuat.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($sourcePath);
        $image->scaleDown(width: 1024, height: 1024);

        file_put_contents(
            $temporaryPath,
            (string) $image->encode(new JpegEncoder(quality: 82)),
        );

        return $temporaryPath;
    }

    private function confidence(float $score): string
    {
        return match (true) {
            $score >= 0.90 => 'high',
            $score >= 0.75 => 'medium',
            default => 'low',
        };
    }
}