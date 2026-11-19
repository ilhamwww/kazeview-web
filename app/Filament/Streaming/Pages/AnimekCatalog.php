<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

/**
 * Beranda anime: hero slider, top 10, episode terbaru, jadwal hari ini,
 * genre, dan rak turunan katalog.
 */
class AnimekCatalog extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Anime - KZSTRM';
    protected static ?string $slug = 'animek';
    protected static string $view = 'filament.streaming.pages.animek';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $sliders = [];
    public array $trending = [];
    public array $latest = [];
    public array $today = [];
    public string $todayName = '';
    public array $genres = [];
    public array $rails = [];
    public int $total = 0;

    public function mount(): void
    {
        $home = AnimekompiService::home();

        $this->sliders = array_slice($home['sliders'] ?? [], 0, 7);
        $this->trending = array_slice($home['trending'] ?? [], 0, 10);
        $this->latest = $home['latest'] ?? [];

        $this->todayName = AnimekompiService::todayName();

        foreach (AnimekompiService::schedule() as $day) {
            if (strcasecmp($day['day'], $this->todayName) === 0) {
                $this->today = $day['items'];
                break;
            }
        }

        $this->genres = array_slice(AnimekompiService::genres(), 0, 18);
        $this->rails = AnimekompiService::rails();
        $this->total = (int) ($this->rails['total'] ?? 0);
    }
}
