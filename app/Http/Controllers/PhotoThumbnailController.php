<?php

namespace App\Http\Controllers;

use App\Models\ListDownloaded;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoThumbnailController extends Controller
{
    private const MAX_DIMENSION = 960;

    private const GRID_DIMENSION = 640;

    /** Ukuran sisi terpanjang thumbnail "fit" (tanpa crop) untuk placeholder lightbox. */
    private const FIT_DIMENSION = 720;

    private const WEBP_QUALITY = 72;

    public function __invoke(Request $request, ListDownloaded $photo): BinaryFileResponse|Response
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

        $isGrid = $request->query('mode') === 'grid' || $request->query('grid') === '1';
        $isFit = ! $isGrid && $request->query('mode') === 'fit';

        if ($isGrid) {
            // Mode GRID: Crop 1:1, Bersih Tanpa Watermark apapun
            $cacheRelativePath = sprintf(
                'thumbnails/%s-%s-grid-clean.webp',
                $photo->getKey(),
                substr(hash('sha256', $sourcePath . '|' . $sourceModifiedAt), 0, 12),
            );

            if (! $disk->exists($cacheRelativePath)) {
                $this->generateGridSquareThumbnail(
                    $sourceAbsolutePath,
                    $disk->path($cacheRelativePath),
                );
            }
        } elseif ($isFit) {
            // Mode FIT: aspect ratio asli, tanpa crop & tanpa watermark.
            // Dipakai sebagai placeholder instan di lightbox supaya tidak
            // sempat tampil sebagai kotak 1:1 hasil crop thumbnail grid.
            $cacheRelativePath = sprintf(
                'thumbnails/%s-%s-fit-clean.webp',
                $photo->getKey(),
                substr(hash('sha256', $sourcePath . '|' . $sourceModifiedAt), 0, 12),
            );

            if (! $disk->exists($cacheRelativePath)) {
                $this->generateFitThumbnail(
                    $sourceAbsolutePath,
                    $disk->path($cacheRelativePath),
                );
            }
        } else {
            // Mode LIGHTBOX / FULL DETAIL: Aspect ratio asli, dengan watermark lengkap (center + tiled + helmet)
            $watermark = $this->getWatermarkConfig();
            $helmets = $photo->motorSearchIndex?->descriptor['helmets'] ?? [];
            $helmetKey = ! empty($helmets) ? substr(md5(json_encode($helmets)), 0, 8) : 'nohelm';

            $watermarkKey = $watermark
                ? sprintf(
                    'wm-%s-%s-%d-%d-%d-%d-%s-%d',
                    md5($watermark['path']),
                    $watermark['position'],
                    $watermark['size'],
                    $watermark['opacity'],
                    $watermark['tile_enabled'] ? 1 : 0,
                    $watermark['tile_opacity'],
                    $watermark['helmet_shield'] ? $helmetKey : 'shieldoff',
                    $watermark['mtime'],
                )
                : 'nowm';

            $cacheRelativePath = sprintf(
                'thumbnails/%s-%s-%s.webp',
                $photo->getKey(),
                substr(hash('sha256', $sourcePath . '|' . $sourceModifiedAt), 0, 12),
                substr(hash('sha256', $watermarkKey), 0, 8),
            );

            if (! $disk->exists($cacheRelativePath)) {
                $this->generateThumbnail(
                    $sourceAbsolutePath,
                    $disk->path($cacheRelativePath),
                    $watermark,
                    $helmets,
                );
            }
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

    private function getWatermarkConfig(): ?array
    {
        return Cache::remember('site_watermark_config', 60, function (): ?array {
            $ws = WebsiteSetting::first();
            if (! $ws || ! $ws->lightbox_watermark_enabled || ! $ws->lightbox_watermark_image) {
                return null;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($ws->lightbox_watermark_image)) {
                return null;
            }

            $fullPath = $disk->path($ws->lightbox_watermark_image);

            return [
                'path' => $fullPath,
                'position' => (string) ($ws->lightbox_watermark_position ?: 'center'),
                'size' => (int) ($ws->lightbox_watermark_size ?: 50),
                'opacity' => (int) ($ws->lightbox_watermark_opacity ?: 75),
                'tile_enabled' => (bool) ($ws->lightbox_watermark_tile_enabled ?? true),
                'tile_opacity' => (int) ($ws->lightbox_watermark_tile_opacity ?: 35),
                'helmet_shield' => (bool) ($ws->lightbox_watermark_helmet_shield ?? true),
                'mtime' => filemtime($fullPath) ?: 0,
            ];
        });
    }

    private function generateGridSquareThumbnail(
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

            // Calculate center square crop coordinates
            $cropSize = min($sourceWidth, $sourceHeight);
            $cropX = (int) round(($sourceWidth - $cropSize) / 2);
            $cropY = (int) round(($sourceHeight - $cropSize) / 2);

            $targetDim = min(self::GRID_DIMENSION, $cropSize);

            $thumbnail = imagecreatetruecolor($targetDim, $targetDim);
            abort_unless($thumbnail !== false, 500);

            try {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);

                $resampled = imagecopyresampled(
                    $thumbnail,
                    $source,
                    0,
                    0,
                    $cropX,
                    $cropY,
                    $targetDim,
                    $targetDim,
                    $cropSize,
                    $cropSize,
                );
                abort_unless($resampled, 500);

                // Note: Bersih tanpa watermark apapun sesuai instruksi Ilham

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

    /**
     * Thumbnail tanpa crop: rasio asli dipertahankan, sisi terpanjang dibatasi
     * FIT_DIMENSION. Dipakai untuk placeholder lightbox.
     */
    private function generateFitThumbnail(
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

            $scale = min(1, self::FIT_DIMENSION / max($sourceWidth, $sourceHeight));
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));

            $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
            abort_unless($thumbnail !== false, 500);

            try {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);

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

    private function generateThumbnail(
        string $sourcePath,
        string $destinationPath,
        ?array $watermark = null,
        array $helmets = [],
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

                if (! empty($watermark)) {
                    $this->applyWatermark($thumbnail, $watermark, $helmets);
                }

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

    private function applyWatermark(\GdImage $target, array $config, array $helmets = []): void
    {
        $watermarkPath = $config['path'] ?? null;
        if (! $watermarkPath || ! file_exists($watermarkPath)) {
            return;
        }

        $imageInfo = @getimagesize($watermarkPath);
        if ($imageInfo === false) {
            return;
        }

        $watermark = match ($imageInfo[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($watermarkPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($watermarkPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($watermarkPath),
            default => null,
        };

        if (! $watermark) {
            return;
        }

        try {
            $targetWidth = imagesx($target);
            $targetHeight = imagesy($target);
            $wmWidth = imagesx($watermark);
            $wmHeight = imagesy($watermark);

            if ($wmWidth <= 0 || $wmHeight <= 0) {
                return;
            }

            // 1. Tiled Anti-AI Watermark Pattern (subtle repeating grid across the whole image)
            if (! empty($config['tile_enabled'])) {
                $this->applyTiledWatermark($target, $watermark, $targetWidth, $targetHeight, $wmWidth, $wmHeight, $config);
            }

            // 2. Primary Focal Watermark (centered or custom configured position)
            $sizePercent = max(5, min(90, (int) ($config['size'] ?? 50)));
            $scaledWmW = (int) round($targetWidth * ($sizePercent / 100));
            $scaledWmH = (int) round($wmHeight * ($scaledWmW / $wmWidth));

            if ($scaledWmH > $targetHeight * 0.9) {
                $scaledWmH = (int) round($targetHeight * 0.9);
                $scaledWmW = (int) round($wmWidth * ($scaledWmH / $wmHeight));
            }
            if ($scaledWmW > $targetWidth * 0.9) {
                $scaledWmW = (int) round($targetWidth * 0.9);
                $scaledWmH = (int) round($wmHeight * ($scaledWmW / $wmWidth));
            }

            $scaledWmW = max(1, $scaledWmW);
            $scaledWmH = max(1, $scaledWmH);

            $resizedWm = imagecreatetruecolor($scaledWmW, $scaledWmH);
            if (! $resizedWm) {
                return;
            }

            try {
                imagealphablending($resizedWm, false);
                imagesavealpha($resizedWm, true);
                $transparent = imagecolorallocatealpha($resizedWm, 0, 0, 0, 127);
                imagefill($resizedWm, 0, 0, $transparent);
                imagecopyresampled(
                    $resizedWm,
                    $watermark,
                    0,
                    0,
                    0,
                    0,
                    $scaledWmW,
                    $scaledWmH,
                    $wmWidth,
                    $wmHeight,
                );

                $opacity = max(5, min(100, (int) ($config['opacity'] ?? 75))) / 100;
                if ($opacity < 1.0) {
                    $this->adjustAlpha($resizedWm, $scaledWmW, $scaledWmH, $opacity);
                }

                $marginX = (int) round($targetWidth * 0.03);
                $marginY = (int) round($targetHeight * 0.03);

                $position = $config['position'] ?? 'center';
                $posX = match ($position) {
                    'top-left', 'center-left', 'bottom-left' => $marginX,
                    'top-right', 'center-right', 'bottom-right' => max(0, $targetWidth - $scaledWmW - $marginX),
                    default => (int) round(($targetWidth - $scaledWmW) / 2),
                };

                $posY = match ($position) {
                    'top-left', 'top-center', 'top-right' => $marginY,
                    'bottom-left', 'bottom-center', 'bottom-right' => max(0, $targetHeight - $scaledWmH - $marginY),
                    default => (int) round(($targetHeight - $scaledWmH) / 2),
                };

                imagealphablending($target, true);
                imagecopy(
                    $target,
                    $resizedWm,
                    $posX,
                    $posY,
                    0,
                    0,
                    $scaledWmW,
                    $scaledWmH,
                );
            } finally {
                imagedestroy($resizedWm);
            }

            // 3. Smart Helmet Shield (100% Solid Opaque Badge directly covering each detected helmet)
            if (! empty($config['helmet_shield']) && ! empty($helmets)) {
                $this->applyHelmetShields($target, $watermark, $targetWidth, $targetHeight, $wmWidth, $wmHeight, $helmets);
            }
        } finally {
            imagedestroy($watermark);
        }
    }

    private function applyHelmetShields(
        \GdImage $target,
        \GdImage $watermark,
        int $targetWidth,
        int $targetHeight,
        int $wmWidth,
        int $wmHeight,
        array $helmets,
    ): void {
        foreach ($helmets as $helmet) {
            $box = $helmet['box_2d'] ?? null;
            if (! is_array($box) || count($box) !== 4) {
                continue;
            }

            [$ymin, $xmin, $ymax, $xmax] = $box;
            if ($ymin >= $ymax || $xmin >= $xmax) {
                continue;
            }

            // Convert normalized coordinates (0-1000) to target pixel coordinates
            $hYmin = ($ymin / 1000) * $targetHeight;
            $hXmin = ($xmin / 1000) * $targetWidth;
            $hYmax = ($ymax / 1000) * $targetHeight;
            $hXmax = ($xmax / 1000) * $targetWidth;

            $hW = $hXmax - $hXmin;
            $hH = $hYmax - $hYmin;

            if ($hW < 10 || $hH < 10) {
                continue;
            }

            // Fit KAZE VIEW logo typography directly across the helmet (no black background box)
            $targetW = (int) round($hW * 1.15);
            $targetH = (int) round($hH * 1.15);

            $logoScale = min($targetW / $wmWidth, $targetH / $wmHeight);
            $finalW = max(1, (int) round($wmWidth * $logoScale));
            $finalH = max(1, (int) round($wmHeight * $logoScale));

            $finalX = (int) round(($hXmin + $hXmax - $finalW) / 2);
            $finalY = (int) round(($hYmin + $hYmax - $finalH) / 2);

            // Transparent badge container for the clean solid logo
            $badge = imagecreatetruecolor($finalW, $finalH);
            if (! $badge) {
                continue;
            }

            try {
                imagealphablending($badge, false);
                imagesavealpha($badge, true);
                $trans = imagecolorallocatealpha($badge, 0, 0, 0, 127);
                imagefill($badge, 0, 0, $trans);

                imagecopyresampled($badge, $watermark, 0, 0, 0, 0, $finalW, $finalH, $wmWidth, $wmHeight);

                // Make the text solid opaque (alpha = 0) while keeping background 100% transparent
                for ($x = 0; $x < $finalW; $x++) {
                    for ($y = 0; $y < $finalH; $y++) {
                        $c = imagecolorat($badge, $x, $y);
                        $a = ($c >> 24) & 0x7F;
                        if ($a < 110) {
                            $r = ($c >> 16) & 0xFF;
                            $g = ($c >> 8) & 0xFF;
                            $b = $c & 0xFF;
                            imagesetpixel($badge, $x, $y, imagecolorallocatealpha($badge, $r, $g, $b, 0));
                        }
                    }
                }

                // Stamp solid text watermark directly over the helmet onto the thumbnail
                imagealphablending($target, true);
                imagecopy($target, $badge, $finalX, $finalY, 0, 0, $finalW, $finalH);
            } finally {
                imagedestroy($badge);
            }
        }
    }

    private function applyTiledWatermark(
        \GdImage $target,
        \GdImage $watermark,
        int $targetWidth,
        int $targetHeight,
        int $wmWidth,
        int $wmHeight,
        array $config,
    ): void {
        $tileW = max(1, (int) round($targetWidth * 0.22));
        $tileH = max(1, (int) round($wmHeight * ($tileW / $wmWidth)));

        $tileWm = imagecreatetruecolor($tileW, $tileH);
        if (! $tileWm) {
            return;
        }

        try {
            imagealphablending($tileWm, false);
            imagesavealpha($tileWm, true);
            $transparent = imagecolorallocatealpha($tileWm, 0, 0, 0, 127);
            imagefill($tileWm, 0, 0, $transparent);
            imagecopyresampled(
                $tileWm,
                $watermark,
                0,
                0,
                0,
                0,
                $tileW,
                $tileH,
                $wmWidth,
                $wmHeight,
            );

            $tileOpacity = max(5, min(60, (int) ($config['tile_opacity'] ?? 22))) / 100;
            $this->adjustAlpha($tileWm, $tileW, $tileH, $tileOpacity);

            imagealphablending($target, true);
            $stepX = (int) round($tileW * 1.6);
            $stepY = (int) round($tileH * 1.8);
            $row = 0;

            for ($y = -(int) round($tileH / 2); $y < $targetHeight + $tileH; $y += $stepY) {
                $offsetX = ($row % 2 === 1) ? (int) round($stepX / 2) : 0;
                for ($x = -(int) round($tileW / 2) + $offsetX; $x < $targetWidth + $tileW; $x += $stepX) {
                    imagecopy($target, $tileWm, (int) $x, (int) $y, 0, 0, $tileW, $tileH);
                }
                $row++;
            }
        } finally {
            imagedestroy($tileWm);
        }
    }

    private function adjustAlpha(\GdImage $image, int $w, int $h, float $opacity): void
    {
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0x7F;
                if ($alpha < 127) {
                    $newAlpha = (int) round(127 - (127 - $alpha) * $opacity);
                    $r = ($color >> 16) & 0xFF;
                    $g = ($color >> 8) & 0xFF;
                    $b = $color & 0xFF;
                    imagesetpixel(
                        $image,
                        $x,
                        $y,
                        imagecolorallocatealpha($image, $r, $g, $b, $newAlpha),
                    );
                }
            }
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
