<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Users';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->hiddenOn('edit'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                DateTimePicker::make('subscription_expires_at')
                    ->label('Langganan Berakhir')
                    ->helperText('Kosongkan jika pengguna memiliki akses unlimited. Jika diisi, pengguna akan melihat modal peringatan setelah tanggal ini terlewati.')
                    ->nullable(),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Disetujui Admin')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_approved')
                    ->label('Disetujui'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription_expires_at')
                    ->label('Langganan')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->isSubscriptionExpired() ? 'danger' : ($record->subscription_expires_at ? 'success' : 'gray'))
                    ->formatStateUsing(fn ($state, $record) => $state ? ($record->isSubscriptionExpired() ? 'Expired' : 'Active') : 'Unlimited'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Impersonate::make()
                ->redirectTo('/admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

}
