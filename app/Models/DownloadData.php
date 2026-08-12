<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DownloadData extends Model
{
    protected $table = 'download_data';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_files' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'id_content');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ListDownloaded::class, 'id_download');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(DownloadFolder::class, 'download_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
