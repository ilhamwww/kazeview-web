<?php

namespace App\Http\Controllers;

use App\Models\ListDownloaded;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoThumbnailController extends Controller
{
    private const MAX_DIMENSION = 960;

    private const WEBP_QUALITY = 72;

    public function __invoke(ListDownloaded $photo): BinaryFileResponse|Response
    {
        abort_unless(
            $photo->download()
                ->whereHas('content', fn ($query) => $query->where('is_active', true))
                ->exists(),
            404,
        );

        $disk = Storage::disk('public');
        $sourcePath = $photo->storagePath();

        abort_unless($disk->exists($sourcePath), 404);

        $sourceAbsolutePath = $disk->path($sourcePath);
        $sourceModifiedAt = filemtime($sourceAbsolutePath) ?: 0;
        $cacheRelativePath = sprintf(
            'thumbnails/%s-%s.webp',
            $photo->getKey(),
            substr(hash('sha256', $sourcePath . '|' . $sourceModifiedAt), 0, 16),
        );

        if (! $disk->exists($cacheRelativePath)) {
            $this->generateThumbnail(
                $sourceAbsolutePath,
                $disk->path($cacheRelativePath),
            );
        }

        $cacheAbsolutePath = $disk->path($cacheRelativePath);

        return response()
            ->file($cacheAbsolutePath, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'ETag' => '"' . hash_file('sha256', $cacheAbsolutePath) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    private function generateThumbnail(
        string $sourcePath,
        string $destinationPath,
    ): void {
        abort_unless(extension_loaded('gd') && function_exists('imagewebp'), 503);

        $imageInfo = @getimagesize($sourcePath);
        abort_unless($imageInfo !== false, 415);

        [$sourceWidth, $sourceHeight, $imageType] = $imageInfo;
        abort_unless($sourceWidth > 0 && $sourceHeight > 0, 415);

        $source = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => false,
        };

        abort_unless($source !== false, 415);

        try {
            if ($imageType === IMAGETYPE_JPEG) {
                $source = $this->applyExifOrientation($source, $sourcePath);
                $sourceWidth = imagesx($source);
                $sourceHeight = imagesy($source);
            }

            $scale = min(
                1,
                self::MAX_DIMENSION / max($sourceWidth, $sourceHeight),
            );
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));

            $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
            abort_unless($thumbnail !== false, 500);

            try {
                imagealphablending($thumbnail, true);
                imagesavealpha($thumbnail, false);

                $resampled = imagecopyresampled(
                    $thumbnail,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight,
                );
                abort_unless($resampled, 500);

                $destinationDirectory = dirname($destinationPath);

                if (! is_dir($destinationDirectory)) {
                    abort_unless(
                        mkdir($destinationDirectory, 0775, true)
                            || is_dir($destinationDirectory),
                        500,
                    );
                }

                $temporaryPath = $destinationPath . '.' . getmypid() . '.tmp';
                abort_unless(
                    imagewebp($thumbnail, $temporaryPath, self::WEBP_QUALITY),
                    500,
                );

                if (! @rename($temporaryPath, $destinationPath)) {
                    @unlink($temporaryPath);
                    abort(500);
                }
            } finally {
                imagedestroy($thumbnail);
            }
        } finally {
            imagedestroy($source);
        }
    }

    private function applyExifOrientation(
        \GdImage $image,
        string $sourcePath,
    ): \GdImage {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($sourcePath)['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}