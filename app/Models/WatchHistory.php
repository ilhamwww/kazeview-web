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
        'progress_seconds',
        'duration_seconds',
        'progress_percent',
    ];

    protected $casts = [
        'watched_episodes' => 'array',
        'progress_seconds' => 'float',
        'duration_seconds' => 'float',
        'progress_percent' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper: format detik ke m:ss atau h:mm:ss
     */
    public function getFormattedProgressAttribute(): string
    {
        $s = (int) $this->progress_seconds;
        if ($s < 3600) {
            return sprintf('%d:%02d', intdiv($s, 60), $s % 60);
        }
        return sprintf('%d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    }

    /**
     * Helper: format durasi ke m:ss atau h:mm:ss
     */
    public function getFormattedDurationAttribute(): string
    {
        $s = (int) $this->duration_seconds;
        if ($s < 3600) {
            return sprintf('%d:%02d', intdiv($s, 60), $s % 60);
        }
        return sprintf('%d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    }
}
