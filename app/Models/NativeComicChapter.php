<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NativeComicChapter extends Model
{
    protected $table = 'native_comic_chapters';

    protected $guarded = [];

    protected $casts = [
        'pages' => 'array',
        'chapter_number' => 'float',
        'released_at' => 'datetime',
    ];

    public function comic(): BelongsTo
    {
        return $this->belongsTo(NativeComic::class, 'comic_id');
    }
}
