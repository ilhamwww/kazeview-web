<?php

namespace App\Filament\Widgets;

use App\Models\ListDownloaded;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DownloadedFilesTable extends BaseWidget
{
    public ?int $downloadId = null;

    public ?string $folderId = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilesQuery())
            ->heading('Downloaded Files')
            ->description($this->getDownloadSummary())
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\ImageColumn::make('preview')
                    ->label('Preview')
                    ->disk('public')
                    ->getStateUsing(function (ListDownloaded $record): ?string {
                        $path = "downloads/{$record->id_folder}/{$record->id_file_in_gd}.jpg";

                        return Storage::disk('public')->exists($path)
                            ? $path
                            : null;
                    })
                    ->height(80)
                    ->width(80)
                    ->square(),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Downloaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('delete')
                    ->label('Hapus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus file?')
                    ->modalDescription(
                        fn (ListDownloaded $record): string =>
                            "File {$record->file_name} akan dihapus dari database dan storage.",
                    )
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->action(function (
                        Tables\Actions\Action $action,
                        ListDownloaded $record,
                    ): void {
                        $path = "downloads/{$record->id_folder}/{$record->id_file_in_gd}.jpg";
                        $disk = Storage::disk('public');

                        if ($disk->exists($path) && ! $disk->delete($path)) {
                            throw new \RuntimeException(
                                "File {$record->file_name} gagal dihapus dari storage.",
                            );
                        }

                        if (! $record->delete()) {
                            throw new \RuntimeException(
                                "Data {$record->file_name} gagal dihapus dari database.",
                            );
                        }

                        $action->success();
                    })
                    ->successNotificationTitle('File berhasil dihapus'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->emptyStateHeading('Belum ada file')
            ->emptyStateDescription('Tidak ada file yang tercatat untuk download ini.')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass');
    }

    protected function getFilesQuery(): Builder
    {
        $query = ListDownloaded::query()
            ->where('id_download', $this->downloadId ?? 0);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return $query->orderByRaw("
                COALESCE(
                    CAST(substring(split_part(file_name, '.', 1) FROM '[0-9]+$') AS INT),
                    0
                ) ASC,
                file_name ASC
            ");
        }

        if ($driver === 'mysql') {
            return $query->orderByRaw("
                CAST(
                    IFNULL(REGEXP_SUBSTR(SUBSTRING_INDEX(file_name, '.', 1), '[0-9]+$'), '0')
                    AS UNSIGNED
                ) ASC,
                file_name ASC
            ");
        }

        return $query->orderBy('file_name');
    }

    protected function getDownloadSummary(): string
    {
        $downloadedFiles = $this->folderId
            ? count(Storage::disk('public')->files("downloads/{$this->folderId}"))
            : 0;

        $totalFiles = $this->downloadId
            ? (int) DB::table('download_data')
                ->where('id', $this->downloadId)
                ->value('total_files')
            : 0;

        return "Downloaded: {$downloadedFiles} dari total {$totalFiles} file";
    }
}