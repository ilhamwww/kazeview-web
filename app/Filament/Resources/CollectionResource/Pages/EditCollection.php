<?php

namespace App\Filament\Resources\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['id']);
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $newImagePath = $data['image'];
        $oldImagePath = $record->image;
        if ($newImagePath !== $oldImagePath) {


            if ($oldImagePath) {
                $path_img = $oldImagePath;
                $oldFile = explode('/', $oldImagePath)[1] ?? null;
                $oldID = strtok($oldImagePath, '/') ?: null;
                if ($oldFile && Storage::disk('public')->exists($path_img)) {
                    Storage::disk('public')->delete($path_img);
                }
            }
            $fileName = basename($newImagePath);
            $mimeType = Storage::disk('public')->mimeType($newImagePath);
            $fileSize = Storage::disk('public')->size($newImagePath);

            $media = DB::table('media')
                ->where('id', $oldID)
                ->first();
            if ($media) {

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
                $idNew = DB::getPdo()->lastInsertId();

                $newImageValue = "{$idNew}/{$fileName}";
                $record->image = $newImageValue;
                $record->updated_at = now('Asia/Jakarta');
                $record->save(); 
                DB::table('media')->where('id', $media->id)->delete();

            }
        }

        return $record;
    }
}
