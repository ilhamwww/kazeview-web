<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
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
use Joaopaulolndev\FilamentCheckSslWidget\FilamentCheckSslWidgetPlugin;
use Joaopaulolndev\FilamentWorldClock\FilamentWorldClockPlugin;
use Hardikkhorasiya09\ChangePassword\ChangePasswordPlugin;
use App\Filament\Pages\Dashboard;
use Filament\Support\Enums\MaxWidth;
use App\Models\WebsiteSetting;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->maxContentWidth(MaxWidth::Full)
            ->path('admin')
            ->brandLogo(asset('storage/' . WebsiteSetting::first()->logo ?? ''))
            ->favicon(asset('storage/' . WebsiteSetting::first()->logo ?? ''))
            ->login(\App\Filament\Pages\Auth\AdminLogin::class)
            ->brandName(WebsiteSetting::first()->name ?? 'Admin Panel')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                \App\Filament\Resources\ContentResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                    // Pages\Dashboard::class,
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\FilamentInfoWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Server Panel')
                    ->icon('heroicon-o-server-stack')
                    ->url('http://139.99.33.19:8090/', shouldOpenInNewTab: true)
                    ->sort(999)
                    ->visible(
                        fn(): bool => auth()->user()?->hasRole(
                            'super_admin',
                        ) ?? false,
                    ),
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                ChangePasswordPlugin::make(),
                FilamentCheckSslWidgetPlugin::make()
                    ->domains([
                        'kazeview.site'
                    ]),
                // \TomatoPHP\FilamentMediaManager\FilamentMediaManagerPlugin::make(),
                FilamentWorldClockPlugin::make()
                    ->timezones([
                        'Asia/Jakarta',
                        'Asia/Tokyo',
                        'Asia/Shanghai',
                        'Europe/Moscow',
                        'America/New_York',
                        'Europe/Berlin',
                    ])
            ]);
    }

}
