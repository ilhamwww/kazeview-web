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
    ];

    protected $casts = [
        'links' => 'array',
        'seo_settings' => 'array',
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
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            if ($setting->hero_image) {
                Storage::disk('public')->delete($setting->hero_image);
            }
            if ($setting->seo_image) {
                Storage::disk('public')->delete($setting->seo_image);
            }
        });

        static::updating(function ($setting) {
            if ($setting->isDirty('logo')) {
                $originalLogo = $setting->getOriginal('logo');
                if ($originalLogo) {
                    Storage::disk('public')->delete($originalLogo);
                }
            }
            if ($setting->isDirty('hero_image')) {
                $originalHeroImage = $setting->getOriginal('hero_image');
                if ($originalHeroImage) {
                    Storage::disk('public')->delete($originalHeroImage);
                }
            }
            if ($setting->isDirty('seo_image')) {
                $originalSeoImage = $setting->getOriginal('seo_image');
                if ($originalSeoImage) {
                    Storage::disk('public')->delete($originalSeoImage);
                }
            }
        });
    }
}
