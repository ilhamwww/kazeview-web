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
                        $path = $record->storagePath();

                        return Storage::disk('public')->exists($path)
                            ? $path
                            : null;
                    })
                    ->height(115)
                    ->width(115)
                    ->square()
                    ->extraImgAttributes([
                        'class' => 'rounded-xl object-cover shadow-sm hover:opacity-90 transition cursor-pointer',
                    ])
                    ->url(
                        fn (ListDownloaded $record): ?string =>
                            Storage::disk('public')->exists($record->storagePath())
                                ? Storage::disk('public')->url($record->storagePath())
                                : null,
                        shouldOpenInNewTab: true,
                    ),

                Tables\Columns\TextColumn::make('embedding_result')
                    ->label('Hasil Embedding')
                    ->getStateUsing(function (ListDownloaded $record): string {
                        $index = $record->motorSearchIndex;
                        if (! $index) {
                            return 'Belum di-index';
                        }

                        if ($index->status === 'failed') {
                            return 'Gagal Index';
                        }

                        if ($index->status !== 'indexed') {
                            return ucfirst(str_replace('_', ' ', $index->status ?? 'pending'));
                        }

                        $d = $index->descriptor ?? [];
                        if (! ($d['motorcycle_present'] ?? true)) {
                            return 'Bukan Motor';
                        }

                        $brand = ($d['brand_guess'] ?? 'unknown') !== 'unknown' ? ucfirst($d['brand_guess']) : '';
                        $model = ($d['model_guess'] ?? 'unknown') !== 'unknown' ? strtoupper($d['model_guess']) : '';
                        $vehicle = trim("{$brand} {$model}");

                        if (! $vehicle) {
                            $cat = ($d['category'] ?? 'unknown') !== 'unknown' ? ucfirst($d['category']) : 'Motor';
                            $vehicle = $cat;
                        }

                        return $vehicle;
                    })
                    ->description(function (ListDownloaded $record): ?string {
                        $index = $record->motorSearchIndex;
                        if (! $index || $index->status !== 'indexed') {
                            return $index?->error_message;
                        }

                        $d = $index->descriptor ?? [];
                        $parts = [];

                        $color = ($d['primary_color'] ?? 'unknown') !== 'unknown' ? ucfirst($d['primary_color']) : null;
                        if ($color) {
                            $parts[] = "Warna: {$color}";
                        }

                        $plate = $d['license_plate'] ?? ($d['visible_text'][0] ?? null);
                        if ($plate && $plate !== 'unknown') {
                            $parts[] = "Plat: " . strtoupper($plate);
                        }

                        $helmet = $d['helmet'] ?? [];
                        $hColor = ($helmet['primary_color'] ?? 'unknown') !== 'unknown' ? ucfirst($helmet['primary_color']) : null;
                        $hType = ($helmet['type'] ?? 'unknown') !== 'unknown' ? $helmet['type'] : null;
                        if ($hType || $hColor) {
                            $parts[] = "Helm: " . trim("{$hColor} {$hType}");
                        }

                        if ($index->embedding_dimensions) {
                            $parts[] = "Vektor: {$index->embedding_dimensions}d";
                        }

                        return ! empty($parts) ? implode(' • ', $parts) : null;
                    })
                    ->badge(fn (ListDownloaded $record): bool => ! $record->motorSearchIndex || $record->motorSearchIndex->status !== 'indexed')
                    ->color(function (ListDownloaded $record): string {
                        $index = $record->motorSearchIndex;
                        if (! $index) {
                            return 'gray';
                        }

                        return match ($index->status) {
                            'indexed' => 'success',
                            'failed' => 'danger',
                            'processing', 'indexing', 'queued', 'reindexing' => 'warning',
                            default => 'gray',
                        };
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('folder.relative_path')
                    ->label('Folder')
                    ->default('Root / Uncategorized')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Downloaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewAiDetail')
                    ->label('Detail AI')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->visible(fn (ListDownloaded $record): bool => filled($record->motorSearchIndex))
                    ->modalHeading(fn (ListDownloaded $record): string => "Hasil Analisis AI & Embedding — {$record->file_name}")
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (ListDownloaded $record) => view(
                        'filament.pages.actions.ai-photo-detail',
                        [
                            'record' => $record,
                            'index' => $record->motorSearchIndex,
                            'descriptor' => $record->motorSearchIndex?->descriptor ?? [],
                        ]
                    )),
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
                        $path = $record->storagePath();
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
            ->poll(fn (): ?string => DB::table('photo_motor_search_indexes')->whereIn('status', ['processing', 'pending', 'reindexing'])->exists() ? '3s' : null)
            ->emptyStateHeading('Belum ada file')
            ->emptyStateDescription('Tidak ada file yang tercatat untuk download ini.')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass');
    }

    protected function getFilesQuery(): Builder
    {
        $query = ListDownloaded::query()
            ->with(['folder', 'motorSearchIndex'])
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
            ? count(Storage::disk('public')->allFiles("downloads/{$this->folderId}"))
            : 0;

        $totalFiles = $this->downloadId
            ? (int) DB::table('download_data')
                ->where('id', $this->downloadId)
                ->value('total_files')
            : 0;

        $indexedCount = $this->downloadId
            ? DB::table('photo_motor_search_indexes')
                ->join('list_downloaded', 'list_downloaded.id', '=', 'photo_motor_search_indexes.list_downloaded_id')
                ->where('list_downloaded.id_download', $this->downloadId)
                ->where('photo_motor_search_indexes.status', 'indexed')
                ->count()
            : 0;

        $percent = $totalFiles > 0 ? min(100, (int) round(($indexedCount / $totalFiles) * 100)) : 0;

        return "Downloaded: {$downloadedFiles}/{$totalFiles} file • AI Indexed: {$indexedCount}/{$totalFiles} ({$percent}%)";
    }
}