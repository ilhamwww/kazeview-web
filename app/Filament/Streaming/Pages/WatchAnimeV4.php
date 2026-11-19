<?php

namespace App\Filament\Streaming\Pages;

use App\Services\SamehadakuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnimeV4 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Anime V4';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime-v4/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v4';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;

    public array $mirrors = [];
    public array $downloads = [];
    public int $activeMirrorIndex = 0;

    public string $currentStreamUrl = '';
    public string $currentQuality = '720p HD';
    public string $currentServer = 'Default';

    public bool $isFavorited = false;

    public function mount(string $anime_slug, string $episode_slug): void
    {
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $scraper = new SamehadakuScraperService();

        $cacheKey = "v4_samehada_ep_{$anime_slug}_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 21600, function () use ($scraper, $episode_slug) {
            return $scraper->scrapeEpisodeDetail($episode_slug);
        });

        if (empty($this->episodeDetail)) {
            abort(404, 'Episode anime tidak ditemukan');
        }

        // Load series detail
        $seriesSlug = $this->episodeDetail['anime_slug'] ?: preg_replace('/-episode-\d+.*$/i', '', $anime_slug);
        $this->animeSlug = $seriesSlug;
        $this->seriesDetail = $scraper->scrapeAnimeSeriesDetail($seriesSlug);

        $this->mirrors = $this->episodeDetail['mirrors'] ?? [];
        $this->downloads = $this->episodeDetail['downloads'] ?? [];

        // Set default active stream
        $defaultStream = $this->episodeDetail['default_stream'] ?? [];
        $this->currentStreamUrl = $defaultStream['stream_url'] ?? '';
        $this->currentQuality = $defaultStream['quality'] ?? '720p HD';
        $this->currentServer = $defaultStream['server'] ?? ($defaultStream['name'] ?? 'Server Utama');
        $this->activeMirrorIndex = 0;

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v4:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime V4');
            $thumbnail = $this->seriesDetail['thumbnail'] ?? null;
            $epNum = (string) ($this->episodeDetail['episode_number'] ?? '1');

            $history = $user->watchHistories()
                ->where('item_id', 'anime_v4:' . $this->animeSlug)
                ->first();

            $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
            if (!in_array($epNum, $watchedList, true)) {
                $watchedList[] = $epNum;
            }

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => 'anime_v4:' . $this->animeSlug,
                    'item_type' => 'anime',
                ],
                [
                    'title' => $animeTitle,
                    'thumbnail' => $thumbnail,
                    'last_episode' => $epNum,
                    'watched_episodes' => $watchedList,
                    'url' => request()->url(),
                ]
            );
        }
    }

    public function switchMirror(int $index): void
    {
        if (!isset($this->mirrors[$index])) {
            return;
        }

        $target = $this->mirrors[$index];
        $this->activeMirrorIndex = $index;

        if (!empty($target['stream_url'])) {
            $this->currentStreamUrl = $target['stream_url'];
            $this->currentQuality = $target['quality'] ?? '720p HD';
            $this->currentServer = $target['server'] ?? $target['name'];

            \Filament\Notifications\Notification::make()
                ->title("Beralih ke Server {$target['label']}")
                ->success()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title("Server {$target['label']} tidak tersedia.")
                ->danger()
                ->send();
        }
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v4:' . $this->animeSlug)
            ->where('item_type', 'anime')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => 'v4:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->seriesDetail['title'] ?? 'Anime',
                'thumbnail' => $this->seriesDetail['thumbnail'] ?? null,
                'url' => \App\Filament\Streaming\Pages\AnimeDetailV4::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
