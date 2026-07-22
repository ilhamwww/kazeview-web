<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnoboyScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime Detail';
    protected static ?string $title = 'Anime Detail';
    protected static ?string $slug = 'anime/{slug}';
    protected static string $view = 'filament.streaming.pages.anime-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public ?array $detail = null;
    public string $animeUrl = '';
    public bool $isFavorited = false;

    public function mount($slug): void
    {
        $this->animeSlug = $slug;

        // Build the full URL from the slug
        $scraper = new AnoboyScraperService();
        $this->animeUrl = rtrim($scraper->getBaseUrl(), '/') . '/anime/' . $slug . '/';

        $cacheKey = 'anoboy_explore_detail_' . md5($this->animeUrl);
        $this->detail = Cache::remember($cacheKey, 86400, function () {
            $scraper = new AnoboyScraperService();
            return $scraper->scrapeAnimeSeriesDetail($this->animeUrl);
        });

        if (!$this->detail) {
            abort(404);
        }

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', $this->animeSlug)
                ->where('item_type', 'anime')
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
            ->where('item_id', $this->animeSlug)
            ->where('item_type', 'anime')
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
                'item_id' => $this->animeSlug,
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
