<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;


class Content extends Model
{
    protected $table = 'contents';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_new' => 'boolean',
            'is_price_enabled' => 'boolean',
            'price' => 'decimal:0',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::deleting(function ($content) {
            if ($content->image) {
                Storage::disk('public')->delete($content->image);
            }
        });

        static::updating(function ($content) {
            if ($content->isDirty('image')) {
                $originalImage = $content->getOriginal('image');
                if ($originalImage) {
                    Storage::disk('public')->delete($originalImage);
                }
            }
        });

        static::saved(function ($content) {
            if ($content->image) {
                $path = Storage::disk('public')->path($content->image);
                $manager = new ImageManager(new Driver());

                if (file_exists($path)) {
                    $image = $manager->read($path);

                    $image->scale(height: 2000);

                    $encoded = $image->encode(new JpegEncoder(quality: 75));

                    file_put_contents($path, (string) $image->encode(new JpegEncoder(quality: 75)));
                }
            }
        });


    }


}
