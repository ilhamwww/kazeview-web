<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'name',
        'description',
        'logo',
        'hero_image',
        'links',
        'last_name',
        'first_name',
        'wa',
        'seo_settings',
        'seo_image',
        'lightbox_watermark_enabled',
        'lightbox_watermark_image',
        'lightbox_watermark_position',
        'lightbox_watermark_size',
        'lightbox_watermark_opacity',
    ];

    protected $casts = [
        'links' => 'array',
        'seo_settings' => 'array',
        'lightbox_watermark_enabled' => 'boolean',
    ];

    public static function seoDefaults(): array
    {
        return [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'canonical_url' => '',
            'robots' => 'index, follow',
            'og_title' => '',
            'og_description' => '',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => '',
            'twitter_description' => '',
            'author' => '',
            'theme_color' => '#050506',
            'google_verification' => '',
            'bing_verification' => '',
            'organization_type' => 'ProfessionalService',
            'organization_description' => '',
            'same_as' => [],
        ];
    }

    public function getSeoAttribute(): array
    {
        return array_replace_recursive(self::seoDefaults(), $this->seo_settings ?? []);
    }

    protected static function booted()
    {
        static::deleting(function ($setting) {
            $paths = array_filter([
                $setting->logo,
                $setting->hero_image,
                $setting->seo_image,
                $setting->lightbox_watermark_image,
            ]);

            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }
        });

        static::updating(function ($setting) {
            foreach ([
                'logo',
                'hero_image',
                'seo_image',
                'lightbox_watermark_image',
            ] as $attribute) {
                if (! $setting->isDirty($attribute)) {
                    continue;
                }

                $originalPath = $setting->getOriginal($attribute);

                if ($originalPath) {
                    Storage::disk('public')->delete($originalPath);
                }
            }
        });
    }
}
