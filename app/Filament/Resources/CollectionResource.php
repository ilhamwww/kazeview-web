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
            Forms\Components\FileUpload::make('image')
                ->label('Image')
                ->directory($sequence)
                ->imageEditor()
                ->required(),
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
