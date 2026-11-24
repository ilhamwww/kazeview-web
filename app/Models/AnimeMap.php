<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeMap extends Model
{
    protected $fillable = [
        'otaku_slug',
        'otaku_title',
        'animek_path',
        'animek_title',
        'anilist_id',
        'source',
    ];
}
