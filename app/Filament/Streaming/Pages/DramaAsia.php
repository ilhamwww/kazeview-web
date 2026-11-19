<?php

namespace App\Filament\Streaming\Pages;

use App\Services\WeTVService;
use Filament\Pages\Page;

class DramaAsia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'Drama Asia';
    protected static ?string $title = 'Drama Asia - KZSTRM';
    protected static ?string $slug = 'drama-asia';
    protected static string $view = 'filament.streaming.pages.drama-asia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $items = [];

    public function mount(): void
    {
        $this->items = WeTVService::rails();
    }
}
