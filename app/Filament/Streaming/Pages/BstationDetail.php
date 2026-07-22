<?php

namespace App\Filament\Streaming\Pages;

use App\Models\ScraperSetting;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class BstationDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Bstation Detail';
    protected static ?string $title = 'Bstation Detail';
    protected static ?string $slug = 'bstation/{id}';
    protected static string $view = 'filament.streaming.pages.bstation-detail-v2';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $seasonId;
    public ?array $detail = null;
    public ?array $episodesData = null;
    public ?array $activeEpisodeData = null;
    public bool $isFavorited = false;
    public ?string $lastWatchedEpisode = null;
    public array $watchedEpisodes = [];

    public function mount(string $id): void
    {
        $this->seasonId = $id;
        $baseUrl = 'https://weanim.goodbos.online/api/bstation';
        $apiKey = ScraperSetting::getInstance()->dramabuzz_api_key ?? '';

        try {
            $response = Http::timeout(10)->get(
                "{$baseUrl}/detail/{$this->seasonId}?code={$apiKey}",
            );

            if (! $response->successful() || ! $response->json('success')) {
                abort(404, 'Detail not found');
            }

            $this->detail = $response->json('data');

            $episodesResponse = Http::timeout(10)->get(
                "{$baseUrl}/episodes/{$this->seasonId}?code={$apiKey}",
            );

            if ($episodesResponse->successful() && $episodesResponse->json('success')) {
                $this->episodesData = $episodesResponse->json('data');
            }
        } catch (\Exception $exception) {
            abort(500, 'Failed to fetch Bstation details');
        }

        static::$title = $this->detail['title'] ?? 'Bstation Detail';

        if (! auth()->check()) {
            return;
        }

        $this->isFavorited = auth()->user()->favorites()
            ->where('item_id', $this->seasonId)
            ->where('item_type', 'bstation')
            ->exists();

        $history = auth()->user()->watchHistories()
            ->where('item_id', $this->seasonId)
            ->where('item_type', 'bstation')
            ->first();

        $this->lastWatchedEpisode = $history?->last_episode;
        $this->watchedEpisodes = is_array($history?->watched_episodes)
            ? $history->watched_episodes
            : [];
    }

    public function autoPlayBstation(): void
    {
        $episode = $this->lastWatchedEpisode
            ? (int) $this->lastWatchedEpisode
            : (int) ($this->episodeList()[0]['ep_id'] ?? 0);

        if ($episode > 0) {
            $this->playEpisode($episode);
        }
    }

    public function toggleFavorite(): mixed
    {
        if (! auth()->check()) {
            return redirect()->to(route('filament.streaming.auth.login'));
        }

        $user = auth()->user();
        $favorite = $user->favorites()
            ->where('item_id', $this->seasonId)
            ->where('item_type', 'bstation')
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;

            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();

            return null;
        }

        $user->favorites()->create([
            'item_id' => $this->seasonId,
            'item_type' => 'bstation',
            'title' => $this->detail['title'] ?? 'Bstation Detail',
            'thumbnail' => $this->detail['cover'] ?? null,
            'url' => request()->url(),
        ]);

        $this->isFavorited = true;

        \Filament\Notifications\Notification::make()
            ->title('Ditambahkan ke Favorit')
            ->success()
            ->send();

        return null;
    }

    public function playEpisode(int $epId): void
    {
        if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
            \Filament\Notifications\Notification::make()
                ->title('Langganan Anda telah berakhir. Hubungi admin untuk memperpanjang.')
                ->danger()
                ->send();

            return;
        }

        $episodeList = $this->episodeList();
        $currentIndex = collect($episodeList)->search(
            fn (array $episode): bool => (int) $episode['ep_id'] === $epId,
        );

        $this->activeEpisodeData = $currentIndex === false
            ? null
            : $episodeList[$currentIndex];

        if (! $this->activeEpisodeData) {
            \Filament\Notifications\Notification::make()
                ->title('Episode tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $baseUrl = 'https://weanim.goodbos.online/api/bstation';
        $apiKey = ScraperSetting::getInstance()->dramabuzz_api_key ?? '';

        try {
            $response = Http::timeout(15)->get(
                "{$baseUrl}/playurl/{$epId}?sid={$this->seasonId}&qn=112&code={$apiKey}",
            );

            if (! $response->successful() || ! $response->json('success')) {
                \Filament\Notifications\Notification::make()
                    ->title('Gagal memuat URL video (playurl)')
                    ->danger()
                    ->send();

                return;
            }

            $playData = $response->json('data');
            $nextEpisodeId = $currentIndex !== false && isset($episodeList[$currentIndex + 1])
                ? (int) $episodeList[$currentIndex + 1]['ep_id']
                : null;

            $this->saveWatchHistory($epId);

            $this->dispatch(
                'init-bstation-player',
                playData: $playData,
                episode: $this->activeEpisodeData,
                epId: $epId,
                nextEpisodeId: $nextEpisodeId,
            );
        } catch (\Exception $exception) {
            \Filament\Notifications\Notification::make()
                ->title('Terjadi kesalahan saat memuat episode.')
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function episodeList(): array
    {
        $episodes = [];

        foreach ($this->episodesData['sections'] ?? [] as $section) {
            foreach ($section['episodes'] ?? [] as $episode) {
                if (empty($episode['ep_id'])) {
                    continue;
                }

                $episode['section_title'] = $section['title'] ?? 'Daftar Episode';
                $episodes[] = $episode;
            }
        }

        return $episodes;
    }

    protected function saveWatchHistory(int $epId): void
    {
        if (! auth()->check()) {
            return;
        }

        $episodeId = (string) $epId;
        $watchedEpisodes = $this->watchedEpisodes;

        if (! in_array($episodeId, $watchedEpisodes, true)) {
            $watchedEpisodes[] = $episodeId;
        }

        auth()->user()->watchHistories()->updateOrCreate(
            [
                'item_id' => $this->seasonId,
                'item_type' => 'bstation',
            ],
            [
                'title' => $this->detail['title'] ?? 'Bstation Detail',
                'thumbnail' => $this->detail['cover'] ?? null,
                'last_episode' => $episodeId,
                'watched_episodes' => $watchedEpisodes,
                'url' => request()->url(),
            ],
        );

        $this->lastWatchedEpisode = $episodeId;
        $this->watchedEpisodes = $watchedEpisodes;
    }

    protected function getViewData(): array
    {
        return [
            'detail' => $this->detail,
            'episodesData' => $this->episodesData,
            'episodeList' => $this->episodeList(),
        ];
    }
}