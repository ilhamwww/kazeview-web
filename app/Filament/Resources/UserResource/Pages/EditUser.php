<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changePassword')
                ->label('Ubah Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading(fn (): string => 'Ubah Password: ' . $this->record->name)
                ->modalDescription('Masukkan password baru untuk user ini tanpa perlu mengetahui password lama.')
                ->modalSubmitActionLabel('Simpan')
                ->modalWidth('md')
                ->form([
                    TextInput::make('new_password')
                        ->label('Password Baru')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->same('new_password_confirmation')
                        ->validationAttribute('Password Baru'),
                    TextInput::make('new_password_confirmation')
                        ->label('Konfirmasi Password Baru')
                        ->password()
                        ->revealable()
                        ->required()
                        ->dehydrated(false)
                        ->validationAttribute('Konfirmasi Password Baru'),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'password' => $data['new_password'],
                    ]);

                    Notification::make()
                        ->title('Password Berhasil Diubah')
                        ->body("Password untuk {$this->record->name} berhasil diperbarui.")
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
