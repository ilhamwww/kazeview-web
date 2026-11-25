<?php

namespace App\Http\Controllers;

use App\Models\ListDownloaded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Preview bersih (tanpa watermark) ukuran sedang untuk Galeri Sortir admin.
 * Hanya super_admin, karena file ini tidak ber-watermark.
 */
class AdminPhotoPreviewController extends Controller
{
    private const MAX_DIMENSION = 1600;

    private const WEBP_QUALITY = 80;

    public function __invoke(Request $request, ListDownloaded $photo): BinaryFileResponse
    {
        // Guest & non-super_admin ditolak 403 (app tidak punya route 'login' untuk redirect)
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $disk = Storage::disk('public');
        $sourcePath = $photo->storagePath();
        abort_unless($disk->exists($sourcePath), 404);

        $sourceAbsolute = $disk->path($sourcePath);
        $version = substr(hash('sha256', $sourcePath . '|' . (filemtime($sourceAbsolute) ?: 0)), 0, 12);

        // Disimpan di luar folder public agar tidak bisa diakses langsung lewat /storage
        $cacheDir = storage_path('app/admin-previews');
        $cachePath = "{$cacheDir}/{$photo->getKey()}-{$version}.webp";

        if (! is_file($cachePath)) {
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            $image = (new ImageManager(new Driver()))->read($sourceAbsolute);
            $image->orient();
            $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

            $tmp = $cachePath . '.' . getmypid() . '.tmp';
            file_put_contents($tmp, (string) $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY)));
            rename($tmp, $cachePath);
        }

        $response = response()->file($cachePath, [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // BinaryFileResponse default-nya public; paksa private agar tidak di-cache Cloudflare/proxy
        $response->setPrivate();
        $response->setMaxAge(604800);

        return $response;
    }
}
