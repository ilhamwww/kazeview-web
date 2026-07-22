<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Filament\Notifications\Notification;
use Intervention\Image\Encoders\JpegEncoder;

class Collection extends Model
{
    protected $table = 'collections';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'project_year' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($collection) {
            if ($collection->image) {
                $path = Storage::disk('public')->path($collection->image);

                // Cek apakah file itu benar gambar
                if (!file_exists($path)) {
                    throw new \Exception('Image file does not exist.');
                }

                $manager = new ImageManager(new Driver());

                try {
                    $image = $manager->read($path);
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('The uploaded file is not a valid image.')
                        ->send();
                }
            }
        });

        static::deleting(function ($collection) {
            if ($collection->image) {
                Storage::disk('public')->delete($collection->image);
            }
        });

        static::updating(function ($collection) {
            if ($collection->isDirty('image')) {
                $originalImage = $collection->getOriginal('image');
                if ($originalImage) {
                    Storage::disk('public')->delete($originalImage);
                }
            }
        });

        static::saved(function ($collection) {
            if ($collection->image) {
                $path = Storage::disk('public')->path($collection->image);
                $manager = new ImageManager(new Driver());

                if (file_exists($path)) {
                    $image = $manager->read($path);
                    $image->scale(height: 2500);
                    $encoded = $image->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 75));
                    file_put_contents($path, (string) $encoded);
                }
            }
        });
    }
}
