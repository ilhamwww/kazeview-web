<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'title',
        'thumbnail',
        'url',
        'is_nsfw',
    ];

    protected $casts = [
        'is_nsfw' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
