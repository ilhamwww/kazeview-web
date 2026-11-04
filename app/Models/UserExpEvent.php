<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserExpEvent extends Model
{
    protected $fillable = [
        'user_id', 'source_type', 'item_id', 'unit_key', 'exp', 'is_backfill', 'earned_on',
    ];

    protected $casts = [
        'exp' => 'integer',
        'is_backfill' => 'boolean',
        'earned_on' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
