<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class Collections extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Collections';
    protected static ?string $title = 'Collections';
    protected static ?string $slug = 'collection';
    protected static string $view = 'filament.streaming.pages.collections';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $collections = [];

    public function mount(): void
    {
        $this->collections = StreamingService::getCollections();
    }
}
