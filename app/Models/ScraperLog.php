<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperLog extends Model
{
    protected $fillable = [
        'source',
        'page',
        'url',
        'items_found',
        'items_saved',
        'items_skipped',
        'status',
        'error_message',
        'duration_seconds',
    ];

    protected $casts = [
        'items_found' => 'integer',
        'items_saved' => 'integer',
        'items_skipped' => 'integer',
        'duration_seconds' => 'float',
    ];

    /**
     * Check if a page has already been scraped successfully
     */
    public static function isPageScraped(string $source, int $page): bool
    {
        return static::where('source', $source)
            ->where('page', $page)
            ->where('status', 'success')
            ->exists();
    }

    /**
     * Get all scraped pages for a source
     */
    public static function getScrapedPages(string $source): array
    {
        return static::where('source', $source)
            ->where('status', 'success')
            ->pluck('page')
            ->toArray();
    }

    /**
     * Get scraping statistics for a source
     */
    public static function getStats(string $source = 'anoboy'): array
    {
        return [
            'total_pages_scraped' => static::where('source', $source)->where('status', 'success')->count(),
            'total_items_found' => static::where('source', $source)->sum('items_found'),
            'total_items_saved' => static::where('source', $source)->sum('items_saved'),
            'total_items_skipped' => static::where('source', $source)->sum('items_skipped'),
            'last_scraped_at' => static::where('source', $source)->latest()->value('created_at'),
            'total_errors' => static::where('source', $source)->where('status', 'error')->count(),
        ];
    }
}