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
    ];

    protected $casts = [
        'links' => 'array',
    ];

    protected static function booted()
    {
        static::deleting(function ($setting) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            if ($setting->hero_image) {
                Storage::disk('public')->delete($setting->hero_image);
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
        });
    }
}
