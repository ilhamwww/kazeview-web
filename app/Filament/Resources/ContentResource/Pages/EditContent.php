<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

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

    protected function handleRecordUpdate(
        \Illuminate\Database\Eloquent\Model $record,
        array $data,
    ): \Illuminate\Database\Eloquent\Model {
        $record->update($data);

        return $record;
    }
}
