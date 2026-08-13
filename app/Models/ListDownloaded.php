<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ListDownloaded extends Model
{
    protected $table = 'list_downloaded';
    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (ListDownloaded $file): void {
            $file->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'drive_modified_at' => 'datetime',
        ];
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(DownloadData::class, 'id_download');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DownloadFolder::class, 'download_folder_id');
    }

    public function motorSearchIndex(): HasOne
    {
        return $this->hasOne(PhotoMotorSearchIndex::class, 'list_downloaded_id');
    }

    public function storagePath(): string
    {
        if (filled($this->relative_path)) {
            return "downloads/{$this->id_folder}/{$this->relative_path}";
        }

        return "downloads/{$this->id_folder}/{$this->id_file_in_gd}.jpg";
    }
}
