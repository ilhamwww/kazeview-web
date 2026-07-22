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

        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

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
}