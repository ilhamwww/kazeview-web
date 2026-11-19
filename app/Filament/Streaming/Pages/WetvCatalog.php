<?php

namespace App\Filament\Streaming\Pages;

use App\Services\WeTVService;
use Filament\Pages\Page;

class WetvCatalog extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'WeTV';
    protected static ?string $title = 'Drama WeTV - KZSTRM';
    protected static ?string $slug = 'wetv';
    protected static string $view = 'filament.streaming.pages.wetv';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $items = [];

    public function mount(): void
    {
        $this->items = WeTVService::rails();
    }
}
