<?php

namespace App\Filament\Pages;

use App\Models\Content;
use App\Models\DownloadData;
use App\Models\DownloadFolder;
use App\Models\ListDownloaded;
use App\Support\GoogleDriveFolder as GoogleDriveFolderInput;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContentDownloadPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.content-downloaded';
    protected static ?string $title = 'Downloaded';

    public $contentId = null;
    public $content = null;

    // ✅ Signature sesuai Filament
    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.admin.pages.content-download-page';
    }

    public static function getRoutePath(?string $panel = null): string
    {
        return '/content-download-page/{id}';
    }

    public function mount($id)
    {
        $this->contentId = $id;
        $this->content = Content::findOrFail($id);
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('downloadData')
                ->label('Download Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->modalHeading('Download dari Google Drive')
                ->modalDescription('Masukkan URL folder Google Drive atau folder ID.')
                ->modalSubmitActionLabel('Download')
                ->form([
                    TextInput::make('folder_input')
                        ->label('URL atau Folder ID Google Drive')
                        ->placeholder('https://drive.google.com/drive/folders/...')
                        ->helperText('URL lengkap dan folder ID mentah sama-sama didukung.')
                        ->required()
                        ->maxLength(500)
                        ->autofocus(),
                ])
                ->action(function (array $data): void {
                    if (! $this->hasFolderSchema()) {
                        Notification::make()
                            ->title('Database belum siap')
                            ->body('Jalankan migration terbaru terlebih dahulu agar struktur subfolder Google Drive dapat disimpan.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $folderId = $this->extractGoogleDriveFolderId(
                        $data['folder_input'],
                    );

                    if ($folderId === null) {
                        throw ValidationException::withMessages([
                            'folder_input' => 'URL atau folder ID Google Drive tidak valid.',
                        ]);
                    }

                    $content = Content::query()->findOrFail(
                        $this->contentId,
                    );

                    if ($content->link !== $data['folder_input']) {
                        $content->update([
                            'link' => trim($data['folder_input']),
                        ]);
                    }

                    $queued = $content->queueDriveDownload(
                        $data['folder_input'],
                    );

                    Notification::make()
                        ->title(
                            $queued
                                ? 'Download masuk antrean'
                                : 'Download belum dapat dijadwalkan',
                        )
                        ->body(
                            $queued
                                ? 'Google Drive akan diproses di background. Halaman ini tidak perlu ditunggu.'
                                : 'Periksa URL dan pastikan migration queue terbaru sudah dijalankan.',
                        )
                        ->when(
                            $queued,
                            fn (Notification $notification) => $notification->success(),
                            fn (Notification $notification) => $notification->danger(),
                        )
                        ->send();
                }),
        ];
    }

    protected function extractGoogleDriveFolderId(string $input): ?string
    {
        return GoogleDriveFolderInput::extractId($input);
    }

    protected function hasFolderSchema(): bool
    {
        return Schema::hasTable('download_folders')
            && Schema::hasColumn('list_downloaded', 'download_folder_id')
            && Schema::hasColumn('list_downloaded', 'relative_path');
    }

    public function table(Table $table): Table
    {
        $columns = [
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(), // fitur bawaan Filament untuk nomor urut

                Tables\Columns\TextColumn::make('folder_name')->label(
                    'Folder Name'
                )->sortable(),

                Tables\Columns\TextColumn::make('total_files')
                    ->label('Images')
                    ->numeric()
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $folderPath = 'downloads/' . $record->id_folder;
                        $files = Storage::disk('public')->allFiles($folderPath);
                        $totalFiles = count($files);

                        $totalDownload = DB::table('download_data')
                            ->where('id', $record->id)
                            ->value('total_files');

                        return $totalFiles === $totalDownload ? 'Sukses' : 'Proses';
                    })
                    ->badge()
                    ->colors([
                        'success' => 'Sukses',
                        'warning' => 'Proses',
                    ]),
        ];

        if ($this->hasFolderSchema()) {
            array_splice($columns, 2, 0, [
                Tables\Columns\TextColumn::make('folders_count')
                    ->label('Subfolders')
                    ->counts('folders')
                    ->badge(),
            ]);
        }

        return $table
            ->query(
                DownloadData::query()
                    ->where('id_content', $this->contentId)
            )
            ->columns($columns)
            ->actions([
                Action::make('viewFiles')
                    ->label('View Files')
                    ->button()
                    ->modalHeading(fn(DownloadData $record): string => "Files — {$record->folder_name}")
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn(DownloadData $record) => view(
                        'filament.pages.actions.downloaded-files',
                        [
                            'downloadId' => $record->id,
                            'folderId' => $record->id_folder,
                        ],
                    )),
                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus seluruh data download?')
                    ->modalDescription(
                        fn (DownloadData $record): string =>
                            "Folder {$record->folder_name}, seluruh file di storage, dan data terkait akan dihapus permanen.",
                    )
                    ->modalSubmitActionLabel('Ya, hapus semua')
                    ->action(function (
                        Action $action,
                        DownloadData $record,
                    ): void {
                        $folderId = (string) $record->id_folder;

                        if (! preg_match('/^[A-Za-z0-9_-]+$/', $folderId)) {
                            throw new \RuntimeException(
                                'Folder ID tidak valid sehingga penghapusan storage dibatalkan.',
                            );
                        }

                        $otherReferences = DownloadData::query()
                            ->where('id_folder', $folderId)
                            ->whereKeyNot($record->getKey())
                            ->count();

                        if ($otherReferences > 0) {
                            throw new \RuntimeException(
                                "Folder {$record->folder_name} masih digunakan oleh {$otherReferences} data download lain.",
                            );
                        }

                        $folderPath = "downloads/{$folderId}";
                        $disk = Storage::disk('public');

                        if (
                            $disk->exists($folderPath)
                            && ! $disk->deleteDirectory($folderPath)
                        ) {
                            throw new \RuntimeException(
                                "Folder {$record->folder_name} gagal dihapus dari storage.",
                            );
                        }

                        if ($disk->exists($folderPath)) {
                            throw new \RuntimeException(
                                "Folder {$record->folder_name} masih tersisa di storage.",
                            );
                        }

                        DB::transaction(function () use ($record): void {
                            ListDownloaded::where(
                                'id_download',
                                $record->getKey(),
                            )->delete();

                            if ($this->hasFolderSchema()) {
                                DownloadFolder::where(
                                    'download_id',
                                    $record->getKey(),
                                )
                                    ->orderByDesc('depth')
                                    ->delete();
                            }

                            if (! $record->delete()) {
                                throw new \RuntimeException(
                                    'Data download gagal dihapus dari database.',
                                );
                            }
                        });

                        $action->success();
                    })
                    ->successNotificationTitle(
                        'Folder, file storage, dan data download berhasil dihapus',
                    ),
            ]);
    }
}
