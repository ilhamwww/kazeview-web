<?php

namespace App\Filament\Resources;

use App\Models\Collection;
use App\Filament\Resources\CollectionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Gallery';
    protected static ?string $navigationLabel = 'Collections';

    public static function form(Form $form): Form
    {
        $sequence = DB::select("SELECT last_value, is_called FROM media_id_seq")[0]->last_value + 1;
        return $form->schema([
            Forms\Components\Section::make('Home Portfolio Metadata')
                ->description('Data ini digunakan pada portfolio halaman Home.')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Judul')
                        ->placeholder('Contoh: NIGHT RUN — SURABAYA')
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'PHOTOGRAPHY' => 'PHOTOGRAPHY',
                            'AUTOMOTIVE' => 'AUTOMOTIVE',
                            'PORTRAITS' => 'PORTRAITS',
                            'EVENTS' => 'EVENTS',
                        ])
                        ->default('PHOTOGRAPHY')
                        ->required(),
                    Forms\Components\Select::make('media_type')
                        ->label('Tipe Media')
                        ->options([
                            'PHOTOGRAPHY' => 'PHOTOGRAPHY',
                            'FILM' => 'FILM',
                        ])
                        ->default('PHOTOGRAPHY')
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('duration')
                        ->label('Durasi Film')
                        ->placeholder('01:24')
                        ->maxLength(10)
                        ->visible(fn ($get) => $get('media_type') === 'FILM'),
                    Forms\Components\TextInput::make('project_year')
                        ->label('Tahun Project')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(2200)
                        ->default(now()->year),
                    Forms\Components\TextInput::make('image_position')
                        ->label('Posisi Gambar')
                        ->default('50% 50%')
                        ->helperText('Format CSS object-position, contoh: 50% 42%.')
                        ->maxLength(30),
                    Forms\Components\TextInput::make('link')
                        ->label('Link')
                        ->placeholder('#work')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Film')
                        ->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Tampilkan di Home')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpan('full'),
            Forms\Components\FileUpload::make('image')
                ->label('Thumbnail / Poster')
                ->helperText('Wajib berupa gambar. Digunakan sebagai cover dan fallback video.')
                ->directory($sequence)
                ->image()
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])
                ->imageEditor()
                ->required(fn ($get): bool => $get('media_type') !== 'FILM')
                ->visible(fn ($get): bool => $get('media_type') !== 'FILM'),
            Forms\Components\FileUpload::make('video')
                ->label('Video')
                ->helperText('Format: MP4, WebM, atau MOV. Maksimal 200 MB.')
                ->directory('collection-videos')
                ->acceptedFileTypes([
                    'video/mp4',
                    'video/webm',
                    'video/quicktime',
                ])
                ->maxSize(204800)
                ->downloadable()
                ->required(fn ($get): bool => $get('media_type') === 'FILM')
                ->visible(fn ($get): bool => $get('media_type') === 'FILM'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urut', 'asc')
            ->reorderable('urut')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->size(50)
                    ->circular(),
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('title')->label('Judul')->placeholder('-'),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->badge(),
                Tables\Columns\TextColumn::make('media_type')->label('Tipe')->badge(),
                Tables\Columns\IconColumn::make('video')
                    ->label('Video')
                    ->boolean(fn ($state): bool => filled($state)),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
