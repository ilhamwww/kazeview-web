<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContactSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'eyebrow' => 'CONTACT KAZEVIEW',
            'headline' => 'LET’S MAKE SOMETHING MOVE.',
            'intro' => 'Tell us what you are building, moving, or documenting. We would love to hear about the next frame.',
            'location' => 'YOGYAKARTA, INDONESIA',
            'availability' => 'AVAILABLE FOR SELECTED PROJECTS',
            'response_time' => 'USUALLY WITHIN 1–2 BUSINESS DAYS',
            'cta_label' => 'START A CONVERSATION',
            'social_links' => [],
            'is_active' => true,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public function whatsappUrl(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->whatsapp);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        } elseif (! str_starts_with($number, '62')) {
            $number = '62' . $number;
        }

        $message = trim((string) $this->whatsapp_message);

        return 'https://wa.me/' . $number
            . ($message !== '' ? '?text=' . urlencode($message) : '');
    }

    protected static function booted(): void
    {
        static::updating(function (self $setting): void {
            if ($setting->isDirty('image') && ($original = $setting->getOriginal('image'))) {
                Storage::disk('public')->delete($original);
            }
        });

        static::deleting(function (self $setting): void {
            if ($setting->image) {
                Storage::disk('public')->delete($setting->image);
            }
        });
    }
}