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
    public ?array $current = null;
    public ?array $prev = null;
    public ?array $next = null;
    public ?array $stream = null;

    public function mount(string $path, ?string $episode = null): void
    {
        $this->path = $path;
        $this->media = AnimekompiService::detail($path);

        if (!$this->media) {
            abort(404);
        }

        // Urut naik (episode 1 dulu) dari service.
        $this->episodes = $this->media['episodes'] ?? [];
        $ids = array_column($this->episodes, 'id');
        $pos = $episode !== null ? array_search($episode, $ids, true) : false;

        if ($pos === false) {
            $pos = count($ids) - 1; // episode terbaru
        }

        if ($pos < 0) {
            return;
        }

        $this->current = $this->episodes[$pos];
        $this->currentId = $this->current['id'];
        $this->prev = $this->episodes[$pos - 1] ?? null;
        $this->next = $this->episodes[$pos + 1] ?? null;
        $this->stream = AnimekompiService::play($this->currentId);
        $this->attachDriveServer();
    }

    /**
     * Kalau episode ini sudah ada salinan 1080p di Drive, tampilkan sebagai
     * server "Native" dan jadikan sumber utama.
     */
    protected function attachDriveServer(): void
    {
        if (! $this->stream) {
            return;
        }

        $row = \App\Models\DriveEpisode::where('episode_id', $this->currentId)->first();

        if (! $row || trim((string) $row->rel_path) === '') {
            return;
        }

        $url = \App\Services\DriveStream::url($row->rel_path);
        $servers = $this->stream['servers'] ?? [];

        array_unshift($servers, [
            'name' => 'Native',
            'url' => $url,
            'kind' => 'video',
        ]);

        $this->stream['servers'] = $servers;
        $this->stream['video_url'] = $url;
    }

    public function getTitle(): string
    {
        $ep = $this->current['number'] ?? '';

        return ($this->media['title'] ?? 'Anime') . ($ep !== '' ? ' Episode ' . $ep : '') . ' - KZSTRM';
    }
}
