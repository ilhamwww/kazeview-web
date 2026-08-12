<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use App\Support\GoogleDriveFolder;
use Filament\Actions;
use Filament\Notifications\Notification;
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
        $oldFolderId = GoogleDriveFolder::extractId($record->link);
        $record->update($data);
        $newFolderId = GoogleDriveFolder::extractId($record->link);

        if ($newFolderId !== null && $newFolderId !== $oldFolderId) {
            $queued = $record->queueDriveDownload();

            Notification::make()
                ->title(
                    $queued
                        ? 'Download Google Drive dijadwalkan'
                        : 'Download belum dapat dijadwalkan',
                )
                ->body(
                    $queued
                        ? 'Link berubah dan akan diproses oleh queue worker di background.'
                        : 'Jalankan migration queue dan struktur folder terbaru.',
                )
                ->when(
                    $queued,
                    fn (Notification $notification) => $notification->success(),
                    fn (Notification $notification) => $notification->warning(),
                )
                ->send();
        }

        return $record;
    }
}
