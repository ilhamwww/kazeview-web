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
                    ->getStateUsing(
                        fn (Content $record): string =>
                            "{$record->indexed_photos_count} / {$record->downloaded_photos_count}",
                    )
                    ->badge()
                    ->color(fn (Content $record): string => match (true) {
                        ! $record->ai_photo_search_enabled => 'gray',
                        $record->indexed_photos_count > 0
                            && $record->indexed_photos_count >= $record->downloaded_photos_count => 'success',
                        $record->failed_photos_count > 0 => 'danger',
                        default => 'warning',
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
                        $total = $dispatcher->dispatchForContent($record);

                        Notification::make()
                            ->title('Retry dijadwalkan')
                            ->body("{$total} foto akan diperiksa ulang secara idempotent.")
                            ->success()
                            ->send();
                    }),
            ])
            ->poll('10s')
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
                "select count(*) from photo_motor_search_indexes pmsi where pmsi.content_id = contents.id and pmsi.status = 'indexed'",
                'indexed_photos_count',
            )
            ->selectSub(
                "select count(*) from photo_motor_search_indexes pmsi where pmsi.content_id = contents.id and pmsi.status = 'failed'",
                'failed_photos_count',
            );
    }
}