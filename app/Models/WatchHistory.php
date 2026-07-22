<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{
    protected $table = 'watch_histories';

    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'title',
        'thumbnail',
        'last_episode',
        'watched_episodes',
        'url',
    ];

    protected $casts = [
        'watched_episodes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
