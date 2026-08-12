<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ContentFilterCategory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContentFilterCategory $category): void {
            $category->name = trim($category->name);
            $category->slug = Str::slug(
                filled($category->slug)
                    ? $category->slug
                    : $category->name,
            );
        });
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(
            Content::class,
            'content_content_filter_category',
        );
    }
}