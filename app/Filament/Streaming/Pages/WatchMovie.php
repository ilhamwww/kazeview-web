<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class WatchMovie extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Watch';
    protected static ?string $title = 'Watch Media';
    protected static ?string $slug = 'movie/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-movie';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public $movieSlug;
    public ?array $media = null;
    public array $recommendations = [];
    public bool $isFavorited = false;

    public function mount($slug): void
    {
        $this->movieSlug = $slug;
        $this->media = StreamingService::getMovieDetail($slug);

        if (!$this->media) {
            abort(404);
        }

        // Server-side subscription check
        if (auth()->check() && auth()->user()->isSubscriptionExpired()) {
            $this->media['video_url'] = '';
            $this->media['subtitles'] = [];
            $this->recommendations = [];
            return;
        }

        $stream = !empty($this->media['id']) ? StreamingService::getMovieStream($this->media['id']) : null;
        if ($stream) {
            $this->media['video_url'] = $stream['video_url'] ?? '';
            $this->media['subtitles'] = $stream['subtitles'] ?? [];
        }

        if (empty($this->media['video_url'])) {
            $this->media['video_url'] = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        }

        // Get recommendations
        $homeData = StreamingService::getHomepageData();
        $recommendations = [];
        foreach ($homeData['sections'] ?? [] as $section) {
            $recommendations = array_merge($recommendations, $section['items'] ?? []);
        }

        if (empty($recommendations)) {
            $recommendations = StreamingService::getDummyData();
        }

        $this->recommendations = array_values(array_filter($recommendations, fn($item) => ($item['slug'] ?? '') !== $slug));

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', $this->movieSlug)
                ->where('item_type', 'movie')
                ->exists()
            : false;
    }

    public function toggleFavorite()
    {
        if (!auth()->check()) {
            return redirect()->to(route('filament.streaming.auth.login'));
        }

        $user = auth()->user();
        $favorite = $user->favorites()
            ->where('item_id', $this->movieSlug)
            ->where('item_type', 'movie')
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => $this->movieSlug,
                'item_type' => 'movie',
                'title' => $this->media['title'] ?? 'Movie Detail',
                'thumbnail' => $this->media['poster'] ?? $this->media['thumbnail'] ?? null,
                'url' => request()->url(),
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Favorit')
                ->success()
                ->send();
        }
    }
}
