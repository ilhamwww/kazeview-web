<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimeDownloadResolverService;
use App\Services\AnimeMetadataService;
use App\Services\MirainimeScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class AnimeDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Anime Detail';
    protected static ?string $title = 'Anime Detail';
    protected static ?string $slug = 'anime/{slug}';
    protected static string $view = 'filament.streaming.pages.anime-detail-v1';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $animeSlug = '';
    public ?array $detail = null;
    public ?array $metadata = null;
    public ?array $downloads = null;
    public string $selectedDownloadEp = '1';
    public bool $isFavorited = false;

    public function mount($slug): void
    {
        $this->animeSlug = $slug;

        $scraper = new MirainimeScraperService();
        $cacheKey = 'v1_detail_' . md5($slug);
        $this->detail = Cache::remember($cacheKey, 43200, function () use ($scraper, $slug) {
            return $scraper->scrapeAnimeSeriesDetail($slug);
        });

        if (!$this->detail) {
            abort(404, 'Detail anime tidak ditemukan');
        }

        $title = $this->detail['title'] ?? '';

        // 1. Ambil Metadata Resmi (MyAnimeList / AniList)
        $this->metadata = AnimeMetadataService::getMetadata($title);

        // 2. Ambil Opsi Download Resolusi Otakudesu (Episode 1 & Batch)
        $firstEp = !empty($this->detail['episodes'][0]['episode']) ? (string) $this->detail['episodes'][0]['episode'] : '1';
        $this->selectedDownloadEp = $firstEp;
        $this->downloads = AnimeDownloadResolverService::getDownloads($title, $this->selectedDownloadEp);

        $this->isFavorited = auth()->user()
            ? auth()->user()->favorites()
                ->where('item_id', 'v1:' . $this->animeSlug)
                ->where('item_type', 'anime')
                ->exists()
            : false;
    }

    public function selectDownloadEpisode(string $ep): void
    {
        $this->selectedDownloadEp = $ep;
        $title = $this->detail['title'] ?? '';
        $this->downloads = AnimeDownloadResolverService::getDownloads($title, $ep);
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'v1:' . $this->animeSlug)
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
                'item_id' => 'v1:' . $this->animeSlug,
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
