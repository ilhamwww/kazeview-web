<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandwidthDailyStat extends Model
{
    protected $table = 'bandwidth_daily_stats';

    protected $fillable = [
        'date',
        'total_bytes',
        'movie_bytes',
        'series_bytes',
        'anime_bytes',
        'dracin_bytes',
        'manga_bytes',
        'proxy_bytes',
        'other_bytes',
        'requests_count',
    ];

    protected $casts = [
        'date' => 'date',
        'total_bytes' => 'integer',
        'movie_bytes' => 'integer',
        'series_bytes' => 'integer',
        'anime_bytes' => 'integer',
        'dracin_bytes' => 'integer',
        'manga_bytes' => 'integer',
        'proxy_bytes' => 'integer',
        'other_bytes' => 'integer',
        'requests_count' => 'integer',
    ];
}
