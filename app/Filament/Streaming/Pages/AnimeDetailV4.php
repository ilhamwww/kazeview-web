<?php

namespace App\Filament\Streaming\Pages;

use App\Services\SamehadakuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeDetailV4 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime Detail V4';
    protected static ?string $title = 'Anime Detail';
    protected static ?string $slug = 'anime-v4/{slug}';
    protected static string $view = 'filament.streaming.pages.anime-detail-v4';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public ?array $detail = null;
    public string $animeUrl = '';
    public bool $isFavorited = false;

    public function mount(string $slug): mixed
    {
        $cleanSlug = preg_replace('/-episode-\d+.*$/i', '', $slug);
        if ($cleanSlug !== $slug) {
            return redirect()->to(static::getUrl(['slug' => $cleanSlug], panel: 'streaming'));
        }

        $this->animeSlug = $cleanSlug;

        $scraper = new SamehadakuScraperService();
        $this->animeUrl = rtrim($scraper->getBaseUrl(), '/') . '/anime/' . $cleanSlug . '/';

        $this->detail = $scraper->scrapeAnimeSeriesDetail($cleanSlug);

        if (empty($this->detail)) {
            abort(404, 'Detail anime tidak ditemukan');
        }

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v4:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;

        return null;
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
                'title' => $this->detail['title'] ?? 'Anime Detail',
                'thumbnail' => $this->detail['thumbnail'] ?? null,
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
