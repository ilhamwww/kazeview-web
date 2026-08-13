<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoMotorSearchIndex extends Model
{
    protected $table = 'photo_motor_search_indexes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'descriptor' => 'array',
            'embedding_dimensions' => 'integer',
            'attempts' => 'integer',
            'processing_started_at' => 'datetime',
            'indexed_at' => 'datetime',
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(ListDownloaded::class, 'list_downloaded_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function isIndexed(): bool
    {
        return $this->status === 'indexed' && filled($this->embedding);
    }
}