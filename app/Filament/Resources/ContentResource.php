<?php

namespace App\Filament\Resources;

use App\Models\Content;
use App\Models\User;

use App\Filament\Resources\ContentResource\Pages;
use App\Filament\Resources\ContentResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\State;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\DB;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationIcon = 'heroicon-m-square-3-stack-3d';
    protected static ?string $navigationGroup = 'Gallery';
    public static function form(Form $form): Form
    {
        $sequence = DB::select("SELECT last_value, is_called FROM media_id_seq")[0]->last_value + 1;

        return $form
            ->schema([
                Section::make('Metadata Preview Event')
                    ->description('Data ini digunakan pada halaman /preview.')
                    ->schema([
                        TextInput::make('event_location')
                            ->label('Lokasi Event')
                            ->placeholder('Contoh: TETRA CAFE')
                            ->maxLength(255),
                        DatePicker::make('event_date')
                            ->label('Tanggal Event')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->default(fn (): string => now('Asia/Jakarta')->toDateString())
                            ->required(),
                        Select::make('preview_type')
                            ->label('Tipe Preview')
                            ->options([
                                'PHOTOGRAPHY' => 'PHOTOGRAPHY',
                                'PHOTO + FILM' => 'PHOTO + FILM',
                            ])
                            ->default('PHOTOGRAPHY')
                            ->live()
                            ->required(),
                        TextInput::make('preview_category')
                            ->label('Kategori Filter')
                            ->placeholder('Contoh: motorcycle film')
                            ->helperText('Gunakan kata: motorcycle, automotive, atau film.')
                            ->maxLength(50),
                        Toggle::make('is_active')
                            ->label('Event Aktif / Dibuka')
                            ->helperText('Jika dimatikan, event tetap tampil abu-abu di Preview dengan badge CLOSED dan tidak bisa dibuka.')
                            ->onIcon('heroicon-o-lock-open')
                            ->offIcon('heroicon-o-lock-closed')
                            ->onColor('success')
                            ->default(true),
                        Toggle::make('is_new')
                            ->label('Tampilkan badge NEW')
                            ->default(false),
                        TextInput::make('image_position')
                            ->label('Posisi Gambar')
                            ->placeholder('50% 50%')
                            ->default('50% 50%')
                            ->helperText('Format CSS object-position, misalnya 50% 42%.')
                            ->maxLength(30),
                        TextInput::make('film_duration')
                            ->label('Durasi Film')
                            ->placeholder('01:24')
                            ->maxLength(10)
                            ->visible(fn ($get) => $get('preview_type') === 'PHOTO + FILM'),
                    ])
                    ->columns(2)
                    ->columnSpan('full'),

                TextInput::make('title')
                    ->label('Judul Konten')
                    ->required(),
                Toggle::make('is_price_enabled')
                    ->label('Harga')
                    ->onIcon('heroicon-o-currency-dollar')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->columnSpan('full')
                    ->reactive()
                    ->default(true),
                TextInput::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->hidden(fn ($get) => !$get('is_price_enabled'))
                    ->required(fn ($get) => $get('is_price_enabled')),
                Textarea::make('body')
                    ->label('Isi Konten'),
                TextInput::make('link')
                    ->label('Link')
                    ->required(fn ($get) => $get('is_price_enabled')),
                Select::make('user_id')
                    ->label('Pemilik Konten')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->default(function () {
                        return User::pluck('id')->first();
                    }),
                FileUpload::make('image')
                    ->label('Gambar')
                    ->required()
                    ->directory($sequence)
                    ->imageEditor(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urut', 'asc')
            ->reorderable('urut')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar Konten')
                    ->circular()
                    ->size(40),
                TextColumn::make('id')->label('ID'),
                TextColumn::make('title')->label('Judul'),
                TextColumn::make('event_location')->label('Lokasi')->placeholder('-'),
                TextColumn::make('event_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('preview_type')->label('Tipe')->badge(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onIcon('heroicon-o-lock-open')
                    ->offIcon('heroicon-o-lock-closed')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),
                IconColumn::make('is_new')->label('NEW')->boolean(),
                TextColumn::make('body')->label('Isi')->limit(50),
                TextColumn::make('user.name')->label('Pemilik Konten'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\Action::make('download')
                //     ->label('Download')
                //     ->color('success')
                //     ->url(fn(Content $record): string => url('/admin/content-download-page/' . $record->id))
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }
}
