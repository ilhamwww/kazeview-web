<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Models\WebsiteSetting;
use Filament\Support\Enums\MaxWidth;
use Filament\Http\Middleware\Authenticate;

class StreamingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('streaming')
            ->path('streaming')
            ->topNavigation()
            ->maxContentWidth(MaxWidth::Full)
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->registration(\App\Filament\Pages\Auth\CustomRegister::class)
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_START,
                fn (): string => '<meta name="referrer" content="no-referrer">',
            )
            ->brandLogo(fn () => asset('storage/' . WebsiteSetting::first()->logo ?? ''))
            ->favicon(fn () => asset('storage/' . WebsiteSetting::first()->logo ?? ''))
            ->brandName(fn () => WebsiteSetting::first()->name ?? 'Streaming Panel')
            ->colors([
                'primary' => Color::Red, // Let's use Red for streaming, or Amber
            ])
            ->discoverResources(in: app_path('Filament/Streaming/Resources'), for: 'App\\Filament\\Streaming\\Resources')
            ->discoverPages(in: app_path('Filament/Streaming/Pages'), for: 'App\\Filament\\Streaming\\Pages')
            ->pages([
                \App\Filament\Streaming\Pages\StreamingHome::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Streaming/Widgets'), for: 'App\\Filament\\Streaming\\Widgets')
            ->widgets([
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \Illuminate\Session\Middleware\AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}