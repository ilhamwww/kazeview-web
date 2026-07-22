<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

use Livewire\Attributes\Url;

class WatchSeries extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Series';
    protected static ?string $title = 'Watch Series';
    protected static ?string $slug = 'series/{slug}/season/{seasonNumber}/episode/{episodeNumber}';
    protected static string $view = 'filament.streaming.pages.watch-series';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public $seriesSlug;
    public $seasonNumber;
    public $episodeNumber;
    public ?array $series = null;
    public ?array $streamData = null;
    public ?array $activeEpisode = null;
    public array $episodes = [];

    public function mount($slug, $seasonNumber, $episodeNumber): void
    {
        $this->seriesSlug = $slug;
        $this->seasonNumber = (int)$seasonNumber;
        $this->episodeNumber = (int)$episodeNumber;
        $this->series = StreamingService::getSeriesDetail($slug);

        if (!$this->series) {
            abort(404);
        }

        $defaultSeasonNumber = $this->series['defaultSeason']['seasonNumber'] ?? 1;

        if ($this->seasonNumber === (int)$defaultSeasonNumber) {
            $this->episodes = $this->series['defaultSeason']['episodes'] ?? [];
        } else {
            $seasonDetail = StreamingService::getSeriesSeasonDetail($slug, $this->seasonNumber);
            if ($seasonDetail && isset($seasonDetail['season']['episodes'])) {
                $this->episodes = $seasonDetail['season']['episodes'];
            }
        }

        // Find active episode info from current season's episodes
        foreach ($this->episodes as $ep) {
            if ((int)$ep['episodeNumber'] === $this->episodeNumber) {
                $this->activeEpisode = $ep;
                break;
            }
        }

        if ($this->activeEpisode) {
            // Server-side subscription check
            if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
                $this->streamData = [
                    'video_url' => '',
                    'subtitles' => [],
                    'title' => $this->activeEpisode['name'] ?? '',
                ];
            } else {
                $this->streamData = !empty($this->activeEpisode['id']) ? StreamingService::getEpisodeStream($this->activeEpisode['id']) : null;
            
                if (empty($this->streamData) || empty($this->streamData['video_url'])) {
                    $this->streamData = [
                        'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                        'subtitles' => [],
                        'title' => $this->activeEpisode['name'] ?? '',
                    ];
                }
            }
        } else {
            abort(404);
        }
    }
}
