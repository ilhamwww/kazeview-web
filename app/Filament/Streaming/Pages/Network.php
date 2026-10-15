<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class Network extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'Network';
    protected static ?string $title = 'Network';
    protected static ?string $slug = 'network';
    protected static string $view = 'filament.streaming.pages.network';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $networks = [];

    public function mount(): void
    {
        $this->networks = StreamingService::getNetworksList();
    }
}
