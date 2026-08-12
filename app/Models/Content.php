<?php

namespace App\Models;

use App\Jobs\DownloadGoogleDriveFolder;
use App\Support\GoogleDriveFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;


class Content extends Model
{
    protected $table = 'contents';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_active' => 'boolean',
            'is_new' => 'boolean',
            'is_price_enabled' => 'boolean',
            'price' => 'decimal:0',
            'drive_download_started_at' => 'datetime',
            'drive_download_finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DownloadData::class, 'id_content');
    }

    public static function hasDriveDownloadSchema(): bool
    {
        return Schema::hasTable('jobs')
            && Schema::hasTable('cache')
            && Schema::hasTable('cache_locks')
            && Schema::hasTable('download_folders')
            && Schema::hasColumn('contents', 'drive_download_status')
            && Schema::hasColumn('list_downloaded', 'download_folder_id')
            && Schema::hasColumn('list_downloaded', 'relative_path');
    }

    public function isPhotoContent(): bool
    {
        return ($this->content_media_type ?: 'FOTO') === 'FOTO';
    }

    public function isVideoContent(): bool
    {
        return $this->content_media_type === 'VIDEO';
    }

    public function queueDriveDownload(?string $link = null): bool
    {
        if (! $this->isPhotoContent()) {
            return false;
        }

        $folderId = GoogleDriveFolder::extractId($link ?? $this->link);

        if (! $folderId || ! self::hasDriveDownloadSchema()) {
            return false;
        }

        $this->update([
            'drive_download_status' => 'pending',
            'drive_download_message' => 'Menunggu queue worker Google Drive.',
            'drive_download_started_at' => null,
            'drive_download_finished_at' => null,
        ]);

        DownloadGoogleDriveFolder::dispatch(
            (int) $this->getKey(),
            $folderId,
        )->afterCommit();

        return true;
    }

    protected static function booted()
    {
        static::deleting(function ($content) {
            if ($content->image) {
                Storage::disk('public')->delete($content->image);
            }
        });

        static::updating(function ($content) {
            if ($content->isDirty('image')) {
                $originalImage = $content->getOriginal('image');
                if ($originalImage) {
                    Storage::disk('public')->delete($originalImage);
                }
            }
        });

        static::saved(function ($content) {
            if (
                ! $content->image
                || (
                    ! $content->wasRecentlyCreated
                    && ! $content->wasChanged('image')
                )
            ) {
                return;
            }

            $path = Storage::disk('public')->path($content->image);

            if (! file_exists($path)) {
                return;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($path);
            $image->scale(height: 2000);

            file_put_contents(
                $path,
                (string) $image->encode(
                    new JpegEncoder(quality: 75),
                ),
            );
        });


    }


}
