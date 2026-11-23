<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Registered;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class CustomRegister extends BaseRegister
{
    protected static string $layout = 'filament-panels::components.layout.base';
    protected static string $view = 'filament.pages.auth.custom-register';

    public function register(): ?\Filament\Http\Responses\Auth\Contracts\RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        // Registrasi hanya untuk yang punya kode undangan.
        $invite = (string) config('kz.invite_code');
        $provided = trim((string) ($this->data['invite_code'] ?? ''));

        if ($invite === '' || ! hash_equals($invite, $provided)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.invite_code' => 'Kode undangan tidak valid.',
            ]);
        }

        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            if (mb_strlen((string) ($data['password'] ?? '')) < 10
                || ! preg_match('/[A-Za-z]/', (string) $data['password'])
                || ! preg_match('/[0-9]/', (string) $data['password'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.password' => 'Password minimal 10 karakter dan harus berisi huruf dan angka.',
                ]);
            }

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $user->assignRole('user_stream');

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        // Redirect to success page instead of logging in or showing notification
        $this->redirect(route('register.success'));
        return null;
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data = parent::mutateFormDataBeforeRegister($data);

        $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['is_approved'] = false;

        return $data;
    }

}
