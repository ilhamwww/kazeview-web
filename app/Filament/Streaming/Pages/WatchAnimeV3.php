<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnoboyScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class WatchAnimeV3 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch Anime V3';
    protected static ?string $title = 'Nonton Anime';
    protected static ?string $slug = 'anime-v3/{anime_slug}/episode/{episode_slug}';
    protected static string $view = 'filament.streaming.pages.watch-anime-v3';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public string $episodeSlug = '';
    public ?array $episodeDetail = null;
    public ?array $seriesDetail = null;

    public array $mirrors = [];
    public int $activeMirrorIndex = 0;
    public string $currentStreamUrl = '';
    public string $currentServer = 'Default';

    public ?string $prevEpisodeSlug = null;
    public ?string $nextEpisodeSlug = null;

    public bool $isFavorited = false;

    public function mount(string $anime_slug, string $episode_slug): void
    {
        $this->animeSlug = $anime_slug;
        $this->episodeSlug = $episode_slug;

        $scraper = new AnoboyScraperService();
        $episodeUrl = rtrim($scraper->getBaseUrl(), '/') . '/' . $episode_slug . '/';

        $cacheKey = "v3_anoboy_ep_{$episode_slug}";
        $this->episodeDetail = Cache::remember($cacheKey, 21600, function () use ($scraper, $episodeUrl) {
            return $scraper->scrapeEpisodeDetail($episodeUrl);
        });

        if (!$this->episodeDetail) {
            abort(404, 'Episode tidak ditemukan');
        }

        // Load series detail
        $seriesUrl = !empty($this->episodeDetail['series_url'])
            ? $this->episodeDetail['series_url']
            : (rtrim($scraper->getBaseUrl(), '/') . '/' . $anime_slug . '/');

        $seriesCacheKey = "v3_anoboy_detail_" . md5($anime_slug);
        $this->seriesDetail = Cache::remember($seriesCacheKey, 43200, function () use ($scraper, $seriesUrl) {
            return $scraper->scrapeAnimeSeriesDetail($seriesUrl);
        });

        // Determine Prev & Next episodes
        $episodes = $this->seriesDetail['episodes'] ?? [];
        $currentIndex = null;
        foreach ($episodes as $idx => $ep) {
            if (($ep['slug'] ?? '') === $this->episodeSlug || str_ends_with($ep['url'] ?? '', '/' . $this->episodeSlug . '/')) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex !== null) {
            $this->prevEpisodeSlug = isset($episodes[$currentIndex - 1]) ? ($episodes[$currentIndex - 1]['slug'] ?? null) : null;
            $this->nextEpisodeSlug = isset($episodes[$currentIndex + 1]) ? ($episodes[$currentIndex + 1]['slug'] ?? null) : null;
        }

        $this->mirrors = $this->episodeDetail['mirrors'] ?? [];
        if (empty($this->mirrors) && !empty($this->episodeDetail['video_url'])) {
            $this->mirrors = [
                ['label' => 'Server Utama', 'url' => $this->episodeDetail['video_url']]
            ];
        }

        $this->activeMirrorIndex = 0;
        $this->currentStreamUrl = $this->mirrors[0]['url'] ?? ($this->episodeDetail['video_url'] ?? '');
        $this->currentServer = $this->mirrors[0]['label'] ?? 'Server Utama';

        // Check favorite
        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v3:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        // Record Watch History
        if (auth()->check()) {
            $user = auth()->user();
            $animeTitle = $this->seriesDetail['title'] ?? ($this->episodeDetail['title'] ?? 'Anime V3');
            $thumbnail = $this->seriesDetail['thumbnail'] ?? null;
            $epNum = (string) ($this->episodeDetail['episode_number'] ?? '1');

            $history = $user->watchHistories()
                ->where('item_id', 'anime_v3:' . $this->animeSlug)
                ->first();

            $watchedList = $history && is_array($history->watched_episodes) ? $history->watched_episodes : [];
            if (!in_array($epNum, $watchedList, true)) {
                $watchedList[] = $epNum;
            }

            $user->watchHistories()->updateOrCreate(
                [
                    'item_id' => 'anime_v3:' . $this->animeSlug,
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

        $this->activeMirrorIndex = $index;
        $this->currentStreamUrl = $this->mirrors[$index]['url'];
        $this->currentServer = $this->mirrors[$index]['label'];

        \Filament\Notifications\Notification::make()
            ->title("Beralih ke {$this->currentServer}")
            ->success()
            ->send();
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v3:' . $this->animeSlug)
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
                'item_id' => 'v3:' . $this->animeSlug,
                'item_type' => 'anime',
                'title' => $this->seriesDetail['title'] ?? 'Anime',
                'thumbnail' => $this->seriesDetail['thumbnail'] ?? null,
                'url' => \App\Filament\Streaming\Pages\AnimeDetailV3::getUrl(['slug' => $this->animeSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
