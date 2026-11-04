<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NativeComic extends Model
{
    protected $table = 'native_comics';

    protected $guarded = [];

    protected $casts = [
        'genres' => 'array',
        'rating' => 'float',
        'latest_chapter_at' => 'datetime',
    ];

    public function chapters(): HasMany
    {
        return $this->hasMany(NativeComicChapter::class, 'comic_id')->orderBy('chapter_number', 'desc');
    }
}
