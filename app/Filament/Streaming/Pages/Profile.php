<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Profile';
    protected static ?string $title = 'Profil Saya';
    protected static ?string $slug = 'profile';
    protected static string $view = 'filament.streaming.pages.profile';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $name = '';
    public string $email = '';
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->redirect('/streaming/login');
            return;
        }

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile(): void
    {
        $user = auth()->user();
        
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Profil Berhasil Diperbarui')
            ->success()
            ->send();
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        \Filament\Notifications\Notification::make()
            ->title('Password Berhasil Diperbarui')
            ->success()
            ->send();
    }

    public function removeFromFavorites($id): void
    {
        $user = auth()->user();
        if ($user) {
            $user->favorites()->where('id', $id)->delete();
            
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        }
    }

    public function removeFromHistory($id): void
    {
        $user = auth()->user();
        if ($user) {
            $user->watchHistories()->where('id', $id)->delete();
            
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Riwayat')
                ->success()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        return [
            'favorites' => auth()->user() ? auth()->user()->favorites()->latest()->get() : collect([]),
            'watchHistories' => auth()->user() ? auth()->user()->watchHistories()->orderBy('updated_at', 'desc')->get() : collect([]),
        ];
    }
}
