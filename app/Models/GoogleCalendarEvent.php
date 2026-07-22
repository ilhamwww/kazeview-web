<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarEvent extends Model
{
    protected $fillable = [
        'google_event_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'is_all_day',
        'location',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
    ];
}
