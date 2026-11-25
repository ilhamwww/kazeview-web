<?php

namespace App\Filament\Pages;

use App\Models\Content;
use App\Models\DownloadData;
use App\Models\DownloadFolder;
use App\Models\ListDownloaded;
use App\Support\GoogleDriveFolder as GoogleDriveFolderInput;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

        abort_unless($this->content->isPhotoContent(), 404);
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('redownload')
                ->label('Download Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn (): bool => trim((string) (Content::query()->find($this->contentId)?->link ?? '')) !== '')
                ->modalHeading('Download ulang dari link tersimpan')
                ->modalDescription(function (): string {
                    $link = trim((string) (Content::query()->find($this->contentId)?->link ?? ''));

                    return "Link tersimpan: {$link}\n\nHanya file yang belum ada di server yang akan diunduh, lalu foto baru otomatis masuk antrean AI indexing. Foto yang sudah terindeks tidak diulang.";
                })
                ->modalSubmitActionLabel('Download ulang')
                ->form([
                    Toggle::make('force')
                        ->label('Paksa tanpa kunci')
                        ->helperText('Aktifkan kalau tombol terasa tidak bereaksi karena job sebelumnya masih menggantung. Unduhan tetap hanya mengambil file baru.')
                        ->default(false),
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

                    $content = Content::query()->findOrFail($this->contentId);
                    $link = trim((string) $content->link);

                    if ($link === '' || $this->extractGoogleDriveFolderId($link) === null) {
                        Notification::make()
                            ->title('Link tersimpan tidak valid')
                            ->body('Gunakan tombol Download Data sekali untuk menyimpan URL folder yang benar.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $queued = $content->queueDriveDownload($link, (bool) ($data['force'] ?? false));

                    Notification::make()
                        ->title(
                            $queued
                                ? 'Download ulang masuk antrean'
                                : 'Download belum dapat dijadwalkan',
                        )
                        ->body(
                            $queued
                                ? ((bool) ($data['force'] ?? false)
                                    ? 'Mode paksa: kunci diabaikan. File yang sudah ada tetap dilewati.'
                                    : 'File yang sudah ada akan dilewati.')
                                : 'Periksa link dan pastikan migration queue terbaru sudah dijalankan.',
                        )
                        ->when(
                            $queued,
                            fn (Notification $notification) => $notification->success(),
                            fn (Notification $notification) => $notification->danger(),
                        )
                        ->send();
                }),
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
            ->poll(function (): ?string {
                $c = Content::query()->find($this->contentId);
                return ($c && in_array($c->ai_index_status, ['queued', 'indexing', 'reindexing'])) ? '2s' : null;
            })
            ->columns($columns)
            ->actions([
                Action::make('redownloadFolder')
                    ->label('Download Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->button()
                    ->modalHeading(fn (DownloadData $record): string => "Download ulang folder {$record->folder_name}?")
                    ->modalDescription('Hanya file yang belum ada di server yang akan diunduh. Setelah selesai, foto baru otomatis masuk antrean AI indexing dan foto yang sudah terindeks dilewati.')
                    ->modalSubmitActionLabel('Download ulang')
                    ->form([
                        Toggle::make('force')
                            ->label('Paksa tanpa kunci')
                            ->helperText('Aktifkan kalau tombol terasa tidak bereaksi karena job folder ini masih menggantung.')
                            ->default(false),
                    ])
                    ->action(function (Action $action, array $data, DownloadData $record): void {
                        $folderId = trim((string) $record->id_folder);

                        if (! preg_match('/^[A-Za-z0-9_-]+$/', $folderId)) {
                            throw new \RuntimeException('Folder ID tidak valid sehingga download ulang dibatalkan.');
                        }

                        $content = Content::query()->findOrFail($this->contentId);
                        $queued = $content->queueDriveDownload($folderId, (bool) ($data['force'] ?? false));

                        Notification::make()
                            ->title(
                                $queued
                                    ? 'Download ulang folder masuk antrean'
                                    : 'Download belum dapat dijadwalkan',
                            )
                            ->body(
                                $queued
                                    ? "Folder {$record->folder_name}: file lama dilewati."
                                        . ((bool) ($data['force'] ?? false) ? ' Mode paksa: kunci diabaikan.' : '')
                                    : 'Periksa konfigurasi queue Google Drive.',
                            )
                            ->when(
                                $queued,
                                fn (Notification $notification) => $notification->success(),
                                fn (Notification $notification) => $notification->danger(),
                            )
                            ->send();

                        if ($queued) {
                            $action->success();
                        }
                    }),
                Action::make('openGallery')
                    ->label('Buka Galeri Sortir')
                    ->icon('heroicon-o-photo')
                    ->color('success')
                    ->button()
                    ->url(fn (DownloadData $record): string => url('/admin/download-gallery/' . $record->id)),
                Action::make('viewFiles')
                    ->label('Tabel Data')
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->button()
                    ->modalHeading(fn(DownloadData $record): string => "Files — {$record->folder_name}")
                    ->modalWidth('7xl')
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

    public function getAiStats(): array
    {
        $content = Content::query()->find($this->contentId);
        if (! $content) {
            return [];
        }

        $downloaded = (int) DB::table('list_downloaded')
            ->join('download_data', 'download_data.id', '=', 'list_downloaded.id_download')
            ->where('download_data.id_content', $this->contentId)
            ->count();

        $query = DB::table('photo_motor_search_indexes')
            ->where('content_id', $this->contentId);

        // Hanya 'indexed' yang dianggap selesai. 'reindexing' berarti foto masih
        // diproses ulang, jadi tidak boleh menahan bar di 100%.
        $indexed = (clone $query)->where('status', 'indexed')->count();
        $processing = (clone $query)->whereIn('status', ['processing', 'pending', 'reindexing'])->count();
        $failed = (clone $query)->where('status', 'failed')->count();

        $percent = $downloaded > 0 ? min(100, (int) round(($indexed / $downloaded) * 100)) : 0;
        $isIndexing = in_array($content->ai_index_status, ['queued', 'indexing', 'reindexing']) || ($processing > 0 && $content->ai_photo_search_enabled);

        return [
            'enabled' => (bool) $content->ai_photo_search_enabled,
            'status' => $content->ai_index_status,
            'message' => $content->ai_index_message,
            'downloaded' => $downloaded,
            'indexed' => $indexed,
            'processing' => $processing,
            'failed' => $failed,
            'percent' => $percent,
            'is_indexing' => $isIndexing,
        ];
    }
}
