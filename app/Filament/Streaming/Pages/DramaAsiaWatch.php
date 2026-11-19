<?php

namespace App\Filament\Streaming\Pages;

use App\Services\WeTVService;
use Filament\Pages\Page;

class DramaAsiaWatch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Drama Asia';
    protected static ?string $title = 'Nonton - KZSTRM';
    protected static ?string $slug = 'drama-asia/{cid}/{vid}';
    protected static string $view = 'filament.streaming.pages.watch-drama-asia';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $cid = '';
    public ?array $media = null;
    public array $episodes = [];
    public string $currentVid = '';
    public ?array $stream = null;

    public function mount(string $cid, ?string $vid = null): void
    {
        $this->cid = $cid;
        $this->media = WeTVService::detail($cid);

        if (!$this->media) {
            abort(404);
        }

        $this->episodes = $this->media['episodes'] ?? [];

        $chosen = $this->episodes[0]['vid'] ?? '';

        if ($vid !== null && $vid !== '') {
            foreach ($this->episodes as $ep) {
                if ($ep['vid'] === $vid) {
                    $chosen = $vid;
                    break;
                }
            }
        }

        $this->currentVid = $chosen;

        if ($chosen !== '') {
            $this->stream = WeTVService::play($cid, $chosen);
        }
    }

    public function getTitle(): string
    {
        return ($this->media['title'] ?? 'Nonton') . ' - KZSTRM';
    }
}
