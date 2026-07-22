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
            foreach (['image', 'video'] as $fileColumn) {
                if ($collection->{$fileColumn}) {
                    Storage::disk('public')->delete($collection->{$fileColumn});
                }
            }
        });

        static::updating(function ($collection) {
            foreach (['image', 'video'] as $fileColumn) {
                if ($collection->isDirty($fileColumn)) {
                    $originalFile = $collection->getOriginal($fileColumn);

                    if ($originalFile) {
                        Storage::disk('public')->delete($originalFile);
                    }
                }
            }
        });

        static::saved(function ($collection) {
            if (! $collection->image) {
                return;
            }

            $path = Storage::disk('public')->path($collection->image);

            if (! is_file($path)) {
                return;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($path);
            $image->scale(height: 2500);
            $encoded = $image->encode(new JpegEncoder(quality: 75));
            file_put_contents($path, (string) $encoded);
        });
    }
}
