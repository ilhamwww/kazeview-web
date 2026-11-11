<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeVaultUpdate extends Model
{
    protected $table = 'anime_vault_updates';

    protected $fillable = [
        'slug',
        'title',
        'episode',
        'poster',
        'type',
        'rating',
        'ago',
        'released_timestamp',
        'source',
    ];

    protected $casts = [
        'released_timestamp' => 'integer',
    ];
}
