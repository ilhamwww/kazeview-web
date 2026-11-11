<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaVaultItem extends Model
{
    protected $table = 'media_vault_items';

    protected $guarded = ['id'];

    protected $casts = [
        'genres' => 'array',
        'rating' => 'float',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_published' => 'boolean',
        'last_synced_at' => 'datetime',
        'comments' => 'integer',
    ];
}
