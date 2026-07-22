<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

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

        DB::table('media')->insert([
            'model_type' => 'TomatoPHP\FilamentMediaManager\Models\Folder',
            'model_id' => 5,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'contents',
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
        $maxUrut = DB::table('contents')->max('urut');

        $record = new \App\Models\Content();
        $record->link = $data['link'];
        $record->title = $data['title'];
        $record->body = $data['body'];
        $record->price = $data['is_price_enabled'] ? $data['price'] : null;
        $record->is_price_enabled = $data['is_price_enabled'];
        $record->image = $data['image'] ?? null;
        $record->user_id = $data['user_id'];
        $record->created_at = now('Asia/Jakarta');
        $record->updated_at = now('Asia/Jakarta');
        $record->urut = $maxUrut + 1;
        $record->save();

        $id = $record->id;

        $record = ContentResource::getModel()::findOrFail($id);

        Notification::make()
            ->title('Konten berhasil dibuat')
            ->success()
            ->send();

        return $record;
    }
}
