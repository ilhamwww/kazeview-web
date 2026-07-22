<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AboutSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'eyebrow' => 'ABOUT KAZEVIEW',
            'headline' => 'MOTION, PEOPLE, AND MACHINES.',
            'intro' => 'KAZEVIEW is an independent visual studio documenting automotive culture, people, and events through photography and film.',
            'story_title' => 'BUILT AROUND THE MOMENT.',
            'story_body' => 'We work close to the action—following movement, atmosphere, and the details that make every project distinct. From the street to the circuit, every frame is made to feel immediate and honest.',
            'capabilities' => [
                ['title' => 'AUTOMOTIVE', 'description' => 'Editorial, commercial, rolling shots, and motorsport coverage.'],
                ['title' => 'PORTRAITS', 'description' => 'Character-led portraits for individuals, teams, and brands.'],
                ['title' => 'EVENTS', 'description' => 'Fast, atmospheric coverage for launches, gatherings, and live moments.'],
                ['title' => 'FILM', 'description' => 'Short-form motion, campaign films, and social-first visual stories.'],
            ],
            'location' => 'YOGYAKARTA, INDONESIA',
            'established' => 'INDEPENDENT VISUAL STUDIO',
            'cta_title' => 'LET’S CREATE SOMETHING IN MOTION.',
            'cta_label' => 'BOOK A SHOOT',
            'is_active' => true,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    protected static function booted(): void
    {
        static::updating(function (self $setting): void {
            foreach (['hero_image', 'story_image'] as $column) {
                if (! $setting->isDirty($column)) {
                    continue;
                }

                $original = $setting->getOriginal($column);

                if ($original) {
                    Storage::disk('public')->delete($original);
                }
            }
        });

        static::deleting(function (self $setting): void {
            foreach (['hero_image', 'story_image'] as $column) {
                if ($setting->{$column}) {
                    Storage::disk('public')->delete($setting->{$column});
                }
            }
        });
    }
}