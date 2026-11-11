<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeVaultItem extends Model
{
    protected $table = 'anime_vault_items';

    protected $fillable = [
        'slug',
        'title',
        'alt_title',
        'poster',
        'banner',
        'type',
        'status',
        'rating',
        'latest_episode',
        'latest_episode_at',
        'release_day',
        'season',
        'year',
        'genres',
        'synopsis',
        'is_featured',
        'is_trending',
        'is_published',
        'source',
        'last_synced_at',
    ];

    protected $casts = [
        'genres' => 'array',
        'rating' => 'float',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_published' => 'boolean',
        'latest_episode_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
