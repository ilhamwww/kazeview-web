<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

/**
 * /animek/genre        -> semua genre
 * /animek/genre?g=xxx  -> judul dalam genre itu (24 per halaman dari API)
 */
class AnimekGenre extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $title = 'Genre Anime - KZSTRM';
    protected static ?string $slug = 'animek/genre';
    protected static string $view = 'filament.streaming.pages.animek-genre';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $genres = [];
    public string $genre = '';
    public array $result = [];

    public function mount(): void
    {
        $this->genres = AnimekompiService::genres();
        $g = strtolower(trim((string) request()->query('g', '')));

        if ($g !== '' && preg_match('/^[a-z0-9-]+$/', $g)) {
            $this->genre = $g;
            $this->result = AnimekompiService::genreItems($g, max(1, (int) request()->query('page', 1)));

            if (empty($this->result['items']) && (int) request()->query('page', 1) === 1) {
                abort(404);
            }
        }
    }

    public function getTitle(): string
    {
        return $this->genre !== ''
            ? 'Anime ' . ($this->result['name'] ?? $this->genre) . ' - KZSTRM'
            : 'Genre Anime - KZSTRM';
    }
}
