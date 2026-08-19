<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionCategoryResource\Pages;
use App\Models\CollectionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CollectionCategoryResource extends Resource
{
    protected static ?string $model = CollectionCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Gallery';

    protected static ?string $navigationLabel = 'Master Kategori Collection';

    protected static ?string $modelLabel = 'Kategori Collection';

    protected static ?string $pluralModelLabel = 'Master Kategori Collection';

    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->placeholder('Contoh: WEDDING')
                    ->helperText('Nama otomatis disimpan menggunakan huruf kapital.')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(
                        fn (?string $state): string => mb_strtoupper(trim((string) $state)),
                    ),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Kategori nonaktif tidak tampil sebagai pilihan untuk Collection baru.')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->withCount([
                    'collections',
                ]),
            )
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('collections_count')
                    ->label('Jumlah Collection')
                    ->badge(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->disabled(fn (CollectionCategory $record): bool => $record->collections()->exists())
                    ->tooltip(
                        fn (CollectionCategory $record): ?string =>
                            $record->collections()->exists()
                                ? 'Kategori masih digunakan oleh Collection dan tidak dapat dihapus.'
                                : null,
                    ),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollectionCategories::route('/'),
            'create' => Pages\CreateCollectionCategory::route('/create'),
            'edit' => Pages\EditCollectionCategory::route('/{record}/edit'),
        ];
    }
}