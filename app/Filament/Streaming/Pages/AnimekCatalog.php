<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

class AnimekCatalog extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Anime - KZSTRM';
    protected static ?string $slug = 'animek';
    protected static string $view = 'filament.streaming.pages.animek';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $trending = [];
    public array $latest = [];
    public array $all = [];
    public int $total = 0;

    public function mount(): void
    {
        $home = AnimekompiService::home();

        $this->trending = $home['trending'] ?? [];
        $this->latest = $home['latest'] ?? [];
        $this->all = AnimekompiService::catalog();
        $this->total = count($this->all);
        // Grid dibatasi agar halaman tetap ringan.
        $this->all = array_slice($this->all, 0, 120);
    }
}
