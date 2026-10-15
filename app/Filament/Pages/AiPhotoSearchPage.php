<?php

namespace App\Filament\Pages;

use App\Models\Content;
use App\Services\AiPhotoIndexDispatcher;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class AiPhotoSearchPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-magnifying-glass';

    protected static ?string $navigationLabel = 'AI Photo Search';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'AI Photo Search';

    protected static string $view = 'filament.pages.ai-photo-search';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->contentQuery())
            ->defaultSort('event_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Content')
                    ->description(fn (Content $record): string => $record->event_date?->format('d M Y') ?? 'Tanpa tanggal')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('downloaded_photos_count')
                    ->label('Downloaded')
                    ->numeric()
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('ai_photo_search_enabled')
                    ->label('AI Search')
                    ->onColor('success')
                    ->offColor('gray')
                    ->updateStateUsing(function (
                        Content $record,
                        bool $state,
                        AiPhotoIndexDispatcher $dispatcher,
                    ): bool {
                        $record->update([
                            'ai_photo_search_enabled' => $state,
                            'ai_index_status' => $state
                                ? ($record->drive_download_status === 'completed' ? 'queued' : 'waiting_for_download')
                                : 'off',
                            'ai_index_message' => $state
                                ? 'AI Photo Search diaktifkan.'
                                : 'AI Photo Search dinonaktifkan. Index lama tetap disimpan.',
                            'ai_index_finished_at' => $state ? null : now(),
                        ]);

                        if ($state) {
                            try {
                                $total = $dispatcher->dispatchForContent($record);

                                Notification::make()
                                    ->title('AI Photo Search diaktifkan')
                                    ->body(
                                        $total > 0
                                            ? "{$total} foto masuk antrean AI indexing."
                                            : ($record->fresh()->ai_index_message ?? 'Menunggu foto hasil download.'),
                                    )
                                    ->success()
                                    ->send();
                            } catch (Throwable $exception) {
                                $record->update([
                                    'ai_index_status' => 'failed',
                                    'ai_index_message' => mb_substr($exception->getMessage(), 0, 2000),
                                    'ai_index_finished_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('AI indexing belum dapat dimulai')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('AI Photo Search dinonaktifkan')
                                ->body('Galeri dan Download Data tetap berjalan normal. Index lama tidak dihapus.')
                                ->success()
                                ->send();
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->html()
                    ->getStateUsing(function (Content $record): string {
                        $downloaded = (int) $record->downloaded_photos_count;
                        $indexed = (int) $record->indexed_photos_count;
                        $failed = (int) $record->failed_photos_count;
                        $isActive = in_array($record->ai_index_status, ['queued', 'indexing', 'reindexing']) && $record->ai_photo_search_enabled;

                        if (! $record->ai_photo_search_enabled) {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-800 text-zinc-400">Nonaktif</span>';
                        }

                        if ($downloaded === 0) {
                            return '<span class="text-xs text-zinc-500">0 foto</span>';
                        }

                        $percent = min(100, (int) round(($indexed / $downloaded) * 100));

                        $barColor = $percent >= 100 
                            ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' 
                            : ($isActive ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.6)] animate-pulse' : 'bg-blue-500');

                        $statusBadge = '';
                        if ($isActive) {
                            $statusBadge = '<span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-400"><span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span> ' . $percent . '% <span class="text-[10px] text-amber-300 font-semibold">(⚡ 3 Worker)</span></span>';
                        } elseif ($percent >= 100) {
                            $statusBadge = '<span class="text-xs font-bold text-emerald-400">✓ 100% Selesai</span>';
                        } else {
                            $statusBadge = '<span class="text-xs font-bold text-zinc-300">' . $percent . '%</span>';
                        }

                        return <<<HTML
<div class="min-w-[170px] max-w-[240px] space-y-1.5 py-1">
    <div class="flex items-center justify-between text-xs">
        {$statusBadge}
        <span class="text-[11px] font-mono text-zinc-400">{$indexed}/{$downloaded}</span>
    </div>
    <div class="w-full bg-zinc-800 rounded-full h-2.5 overflow-hidden border border-zinc-700/60 shadow-inner">
        <div class="{$barColor} h-full rounded-full transition-all duration-300" style="width: {$percent}%"></div>
    </div>
</div>
HTML;
                    }),

                Tables\Columns\TextColumn::make('ai_index_status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'waiting_for_download' => 'Waiting for download',
                        'configuration_required' => 'Configuration required',
                        'partially_ready' => 'Partially ready',
                        'queued' => 'Queued',
                        'indexing' => 'Indexing',
                        'ready' => 'Ready',
                        'failed' => 'Failed',
                        'empty' => 'No photos',
                        'off', null => 'Off',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->description(fn (Content $record): ?string => $record->ai_index_message)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'ready' => 'success',
                        'failed', 'configuration_required' => 'danger',
                        'queued', 'indexing', 'waiting_for_download', 'partially_ready' => 'warning',
                        default => 'gray',
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('failed_photos_count')
                    ->label('Failed')
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->toggleable(),
            ])
            ->actions([
                Action::make('sync')
                    ->label('Sync Missing')
                    ->icon('fas-rotate')
                    ->color('primary')
                    ->visible(fn (Content $record): bool => $record->ai_photo_search_enabled)
                    ->action(function (
                        Content $record,
                        AiPhotoIndexDispatcher $dispatcher,
                    ): void {
                        $total = $dispatcher->dispatchForContent($record);

                        Notification::make()
                            ->title('Sinkronisasi dijadwalkan')
                            ->body(
                                $total > 0
                                    ? "{$total} foto diperiksa oleh queue. Foto yang tidak berubah akan dilewati."
                                    : ($record->fresh()->ai_index_message ?? 'Tidak ada foto untuk dijadwalkan.'),
                            )
                            ->success()
                            ->send();
                    }),

                Action::make('forceRescan')
                    ->label('Scan Ulang')
                    ->icon('fas-arrows-rotate')
                    ->color('danger')
                    ->visible(
                        fn (Content $record): bool =>
                            $record->ai_photo_search_enabled
                            && $record->downloaded_photos_count > 0,
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Scan ulang seluruh foto?')
                    ->modalDescription(
                        fn (Content $record): string =>
                            "Seluruh {$record->downloaded_photos_count} foto akan dianalisis ulang oleh AI. Index lama tetap dapat dipakai sampai hasil baru berhasil disimpan.",
                    )
                    ->modalSubmitActionLabel('Ya, scan ulang')
                    ->action(function (
                        Content $record,
                        AiPhotoIndexDispatcher $dispatcher,
                    ): void {
                        $total = $dispatcher->forceRescanForContent($record);

                        Notification::make()
                            ->title('Scan ulang dijadwalkan')
                            ->body(
                                $total > 0
                                    ? "{$total} foto masuk antrean scan ulang AI."
                                    : ($record->fresh()->ai_index_message ?? 'Tidak ada foto untuk dijadwalkan.'),
                            )
                            ->success()
                            ->send();
                    }),

                Action::make('retryFailed')
                    ->label('Retry Failed')
                    ->icon('fas-arrow-rotate-right')
                    ->color('warning')
                    ->visible(
                        fn (Content $record): bool =>
                            $record->ai_photo_search_enabled
                            && $record->failed_photos_count > 0,
                    )
                    ->requiresConfirmation()
                    ->action(function (
                        Content $record,
                        AiPhotoIndexDispatcher $dispatcher,
                    ): void {
                        $total = $dispatcher->retryFailedForContent($record);

                        Notification::make()
                            ->title('Retry dijadwalkan')
                            ->body("{$total} foto gagal masuk antrean ulang.")
                            ->success()
                            ->send();
                    }),
            ])
            ->poll(fn (): ?string => Content::whereIn('ai_index_status', ['queued', 'indexing', 'reindexing'])->exists() ? '2s' : '10s')
            ->emptyStateHeading('Belum ada Content foto')
            ->emptyStateDescription('Content bertipe FOTO akan muncul di halaman ini.');
    }

    private function contentQuery(): Builder
    {
        return Content::query()
            ->where(function (Builder $query): void {
                $query
                    ->where('content_media_type', 'FOTO')
                    ->orWhereNull('content_media_type');
            })
            ->select('contents.*')
            ->selectSub(
                'select count(*) from list_downloaded ld inner join download_data dd on dd.id = ld.id_download where dd.id_content = contents.id',
                'downloaded_photos_count',
            )
            ->selectSub(
                "select count(*) from photo_motor_search_indexes pmsi where pmsi.content_id = contents.id and pmsi.status in ('indexed', 'reindexing')",
                'indexed_photos_count',
            )
            ->selectSub(
                "select count(*) from photo_motor_search_indexes pmsi where pmsi.content_id = contents.id and pmsi.status = 'failed'",
                'failed_photos_count',
            );
    }
}