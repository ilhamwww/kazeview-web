<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DownloadFolder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'image_count' => 'integer',
            'total_image_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(DownloadData::class, 'download_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ListDownloaded::class, 'download_folder_id');
    }
}