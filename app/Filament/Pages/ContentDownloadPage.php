<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use App\Models\DownloadData;
use App\Models\Content;
use App\Models\ListDownloaded;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class ContentDownloadPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.content-downloaded';
    protected static ?string $title = 'Downloaded';

    public $selectedDownloadId = null;
    public $selectedFolderId = null;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DownloadData::query()
                    ->where('id_content', $this->contentId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(), // fitur bawaan Filament untuk nomor urut

                Tables\Columns\TextColumn::make('folder_name')->label(
                    'Folder Name'
                )->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $folderPath = 'downloads/' . $record->id_folder;
                        $files = Storage::disk('public')->files($folderPath);
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
            ])
            ->actions([
                Action::make('viewFiles')
                    ->label('View Files')
                    ->button()
                    ->action(function (DownloadData $record) {
                        $this->selectedDownloadId = $record->id;
                        $this->selectedFolderId = $record->id_folder;
                        $this->dispatch('open-modal', id: 'filesModal');
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (DownloadData $record) {
                        $folderPath = "downloads/{$record->id_folder}";
                        if (Storage::disk('public')->exists($folderPath)) {
                            Storage::disk('public')->deleteDirectory($folderPath);
                        }
                        $record->delete();
                        \App\Models\ListDownloaded::where('id_download', $record->id)->delete();
                    }),
            ]);
    }

    #[On('getFiles')]
    public function getFiles()
    {
        $q = ListDownloaded::where('id_download', $this->selectedDownloadId);

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            // Ambil digit di akhir nama (sebelum ekstensi), cast ke int, default 0 jika tidak ada angka
            $q->orderByRaw("
            COALESCE(
                CAST(substring(split_part(file_name, '.', 1) FROM '[0-9]+$') AS INT),
                0
            ) ASC, file_name ASC
        ");
        } else { // mysql
            // MySQL 8+: REGEXP_SUBSTR tersedia
            $q->orderByRaw("
            CAST(
                IFNULL(REGEXP_SUBSTR(SUBSTRING_INDEX(file_name, '.', 1), '[0-9]+$'), '0')
            AS UNSIGNED) ASC, file_name ASC
        ");
        }

        return $q->get();
    }
}
