<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CollectionCategory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CollectionCategory $category): void {
            $category->name = mb_strtoupper(trim((string) $category->name));
        });

        static::updated(function (CollectionCategory $category): void {
            if (! $category->wasChanged('name')) {
                return;
            }

            DB::table('collections')
                ->where('category', $category->getOriginal('name'))
                ->update(['category' => $category->name]);
        });
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'category', 'name');
    }
}
