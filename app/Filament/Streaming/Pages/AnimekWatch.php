<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

class AnimekWatch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Nonton Anime - KZSTRM';
    protected static ?string $slug = 'animek/{path}/{episode}';
    protected static string $view = 'filament.streaming.pages.watch-animek';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $path = '';
    public ?array $media = null;
    public array $episodes = [];
    public string $currentId = '';
    public ?array $stream = null;

    public function mount(string $path, ?string $episode = null): void
    {
        $this->path = $path;
        $this->media = AnimekompiService::detail($path);

        if (!$this->media) {
            abort(404);
        }

        $this->episodes = array_reverse($this->media['episodes'] ?? []);
        $ids = array_column($this->episodes, 'id');

        $chosen = $episode !== null && in_array($episode, $ids, true)
            ? $episode
            : ($ids[0] ?? '');

        $this->currentId = $chosen;

        if ($chosen !== '') {
            $this->stream = AnimekompiService::play($chosen);
        }
    }

    public function getTitle(): string
    {
        return ($this->media['title'] ?? 'Anime') . ' - KZSTRM';
    }
}
