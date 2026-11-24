<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveEpisode extends Model
{
    protected $fillable = [
        'anime_path',
        'episode_id',
        'title',
        'rel_path',
        'quality',
        'size_bytes',
        'md5',
    ];
}
