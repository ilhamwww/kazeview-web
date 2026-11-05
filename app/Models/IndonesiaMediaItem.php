<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndonesiaMediaItem extends Model
{
    use HasFactory;

    protected $table = 'indonesia_media_items';

    protected $fillable = [
        'slug',
        'raw_slug',
        'target_slug',
        'title',
        'type',
        'year',
        'rating',
        'quality',
        'duration',
        'poster',
        'backdrop',
        'synopsis',
        'genres',
        'source',
        'provider_badge',
        'external_id',
        'watch_url',
        'cached_detail',
        'is_published',
        'last_synced_at',
    ];

    protected $casts = [
        'genres' => 'array',
        'cached_detail' => 'array',
        'is_published' => 'boolean',
        'rating' => 'float',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Format to standard KZSTRM catalog card structure
     */
    public function toCard(): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'slug' => $this->slug,
            'raw_slug' => $this->raw_slug,
            'target_slug' => $this->target_slug,
            'type' => $this->type,
            'year' => $this->year,
            'rating' => $this->rating ?: 8.5,
            'voteAverage' => $this->rating ?: 8.5,
            'quality' => $this->quality ?: '1080p Ultra HD',
            'duration' => $this->duration,
            'runtime' => $this->duration,
            'poster' => $this->poster,
            'thumbnail' => $this->poster,
            'banner' => $this->backdrop ?: $this->poster,
            'isEmbed' => $this->source === 'server2',
            'source' => $this->source,
            'provider_badge' => $this->provider_badge ?: ($this->source === 'moviebox' ? 'Server 1' : 'Server 2'),
            'genres' => $this->genres ?: ['Indonesia'],
        ];
    }
}
