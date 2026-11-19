<?php

namespace App\Filament\Streaming\Pages;

use App\Services\VidioService;
use Filament\Pages\Page;

class WatchVidio extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Vidio';
    protected static ?string $title = 'Nonton - KZSTRM';
    protected static ?string $slug = 'original/{id}/{episode}';
    protected static string $view = 'filament.streaming.pages.watch-vidio';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $vidioId = '';
    public ?string $episodeId = null;
    public ?array $media = null;
    public ?array $stream = null;
    public array $episodes = [];

    public function mount(string $id, ?string $episode = null): void
    {
        $this->vidioId = $id;
        $this->media = VidioService::detail($id);

        if (!$this->media) {
            abort(404);
        }

        $this->episodes = $this->media['episodes'] ?? [];

        if (!empty($this->episodes)) {
            $chosen = null;

            if ($episode !== null && $episode !== '') {
                foreach ($this->episodes as $ep) {
                    if ((string) $ep['id'] === (string) $episode) {
                        $chosen = $ep;
                        break;
                    }
                }
            }

            $chosen ??= $this->episodes[0];
            $this->episodeId = (string) $chosen['id'];
        } else {
            // Film: id judul juga id video.
            $this->episodeId = (string) $this->media['id'];
        }

        $this->stream = VidioService::play($this->episodeId);
        $this->media['current_episode_title'] = collect($this->episodes)
            ->firstWhere('id', $this->episodeId)['title'] ?? null;
    }

    public function getTitle(): string
    {
        return ($this->media['title'] ?? 'Nonton') . ' - KZSTRM';
    }
}
