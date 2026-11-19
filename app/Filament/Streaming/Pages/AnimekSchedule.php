<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

class AnimekSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'Jadwal Tayang Anime - KZSTRM';
    protected static ?string $slug = 'animek/jadwal';
    protected static string $view = 'filament.streaming.pages.animek-schedule';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $days = [];
    public string $today = '';

    public function mount(): void
    {
        $this->days = AnimekompiService::schedule();
        $this->today = AnimekompiService::todayName();
    }
}
