<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListDownloaded extends Model
{
    protected $table = 'list_downloaded';
    protected $guarded = [];

    public function download(): BelongsTo
    {
        return $this->belongsTo(DownloadData::class, 'id_download');
    }
}
