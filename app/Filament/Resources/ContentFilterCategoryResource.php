<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentFilterCategoryResource\Pages;
use App\Models\ContentFilterCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContentFilterCategoryResource extends Resource
{
    protected static ?string $model = ContentFilterCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Gallery';

    protected static ?string $navigationLabel = 'Master Kategori Filter';

    protected static ?string $modelLabel = 'Kategori Filter';

    protected static ?string $pluralModelLabel = 'Master Kategori Filter';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Kategori')
                ->placeholder('Contoh: MOTORCYCLE')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(
                    fn (?string $state, Forms\Set $set) =>
                        $set('slug', Str::slug((string) $state)),
                ),
            Forms\Components\TextInput::make('slug')
                ->label('Slug Filter')
                ->placeholder('motorcycle')
                ->helperText(
                    'Dipakai sebagai kunci filter di frontend. Hanya huruf kecil, angka, dan tanda hubung.',
                )
                ->required()
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Tampilkan di Frontend')
                ->helperText(
                    'Kategori nonaktif tetap tersimpan tetapi tidak tampil sebagai filter.',
                )
                ->default(true)
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contents_count')
                    ->label('Jumlah Content')
                    ->counts('contents')
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
                    ->modalDescription(
                        'Kategori akan dihapus dari seluruh Content yang menggunakannya.',
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentFilterCategories::route('/'),
            'create' => Pages\CreateContentFilterCategory::route('/create'),
            'edit' => Pages\EditContentFilterCategory::route('/{record}/edit'),
        ];
    }
}