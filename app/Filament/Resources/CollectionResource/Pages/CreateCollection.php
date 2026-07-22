<?php

namespace App\Filament\Resources\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use App\Models\Collection;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['id']);
        return $data;
    }


    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $filePath = $data['image'];
        $fileName = basename($filePath);
        $mimeType = Storage::disk('public')->mimeType($filePath);
        $fileSize = Storage::disk('public')->size(path: $filePath);

        $maxUrut = (int) DB::table('collections')->max('urut');
        $collection = new Collection();
        $collection->fill($data);
        $collection->urut = $maxUrut + 1;
        $collection->save();

        $id = $collection->id;

        DB::table('media')->insert([
            'model_type' => 'TomatoPHP\FilamentMediaManager\Models\Folder',
            'model_id' => 1,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'collections',
            'name' => $fileName,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => $fileSize,
            'manipulations' => '[]',
            'custom_properties' => '{"title": null, "description": null}',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        $record = CollectionResource::getModel()::findOrFail($id);

        Notification::make()
            ->title('Konten berhasil dibuat')
            ->success()
            ->send();

        return $record;
    }
}
