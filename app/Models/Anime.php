<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anime extends Model
{
    protected $fillable = [
        'title',
        'url',
        'video_url',
        'thumbnail',
        'episode',
        'type',
        'status',
    ];
}