<?php

namespace App\Filament\Streaming\Pages;

use App\Services\FilmboxService;
use Filament\Pages\Page;

/**
 * Katalog Filmbox 18+. Hanya bisa dibuka user dengan toggle NSFW aktif.
 */
class FilmboxAdult extends Page
{
    protected static ?string $slug = '18plus';
    protected static string $view = 'filament.streaming.pages.filmbox-adult';
    protected static string $layout = 'filament.streaming.layout';
    protected static ?string $title = 'Filmbox 18+';

    public array $hero = [];
    public array $sections = [];
    public array $trending = [];

    public function mount(): void
    {
        abort_unless(auth()->check() && ! empty(auth()->user()->nsfw_enabled), 404);

        $data = FilmboxService::adultHome();
        $this->hero = $data['hero'];
        $this->sections = $data['sections'];
        $this->trending = $data['trending'];
    }

    public function getTitle(): string
    {
        return 'Filmbox 18+ - KZSTRM';
    }
}
