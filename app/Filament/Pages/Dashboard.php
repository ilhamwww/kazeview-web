<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Page;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Guava\Calendar\ValueObjects\CalendarEvent;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // kosongin = hilangin filament info (versi)
        ];
    }
}
