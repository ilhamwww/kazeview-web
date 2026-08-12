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
        $wasPhoto = $record->isPhotoContent();
        $oldFolderId = GoogleDriveFolder::extractId($record->link);
        $record->update($data);

        if ($record->isVideoContent()) {
            if (
                \Illuminate\Support\Facades\Schema::hasColumn(
                    'contents',
                    'drive_download_status',
                )
            ) {
                $record->update([
                    'drive_download_status' => null,
                    'drive_download_message' => 'Video menggunakan link Google Drive tanpa diunduh ke server.',
                    'drive_download_started_at' => null,
                    'drive_download_finished_at' => null,
                ]);
            }

            if ($wasPhoto) {
                Notification::make()
                    ->title('Kategori diubah menjadi VIDEO')
                    ->body(
                        'Video akan dibuka langsung melalui Google Drive dan tidak diunduh ke storage server.',
                    )
                    ->success()
                    ->send();
            }

            return $record;
        }

        $newFolderId = GoogleDriveFolder::extractId($record->link);
        $shouldQueue = $newFolderId !== null
            && (
                ! $wasPhoto
                || $newFolderId !== $oldFolderId
            );

        if ($shouldQueue) {
            $queued = $record->queueDriveDownload();

            Notification::make()
                ->title(
                    $queued
                        ? 'Download Google Drive dijadwalkan'
                        : 'Download belum dapat dijadwalkan',
                )
                ->body(
                    $queued
                        ? 'Folder foto akan diproses oleh queue worker di background.'
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
