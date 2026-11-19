<?php

namespace App\Filament\Streaming\Pages;

use App\Services\Lk21ScraperService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class WatchServerLk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play';
    protected static ?string $navigationLabel = 'Watch Server LK';
    protected static ?string $title = 'Nonton Film';
    protected static ?string $slug = 'server-lk/{slug}';
    protected static string $view = 'filament.streaming.pages.watch-server-lk';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $movieSlug = '';
    public ?array $movie = null;
    public array $servers = [];
    public int $activeServerIndex = 0;
    public string $currentEmbedUrl = '';
    public bool $isFavorited = false;
    public array $related = [];

    public function mount(string $slug): mixed
    {
        $this->movieSlug = trim($slug);

        $scraper = new Lk21ScraperService();
        $this->movie = $scraper->getMovieDetail($this->movieSlug);

        if (!$this->movie || empty($this->movie['title'])) {
            abort(404, 'Film tidak ditemukan atau sedang tidak tersedia.');
        }

        static::$title = $this->movie['title'] . ' - Server LK';

        $this->servers = $this->movie['servers'] ?? [];
        $this->related = $this->movie['related'] ?? [];

        if (!empty($this->servers)) {
            // Prefer Server with cleanest embed (Morencius/EarnVids or HGCloud) for instant playback
            $preferredIndex = 0;
            foreach ($this->servers as $idx => $s) {
                $u = strtolower($s['embed_url'] ?? '');
                if (str_contains($u, 'morencius') || str_contains($u, 'earnvids')) {
                    $preferredIndex = $idx;
                    break;
                }
            }
            $this->activeServerIndex = $preferredIndex;
            $this->currentEmbedUrl = $this->servers[$preferredIndex]['embed_url'] ?? ($this->servers[0]['embed_url'] ?? '');
        }

        // Check watchlist favorite (no auto tracking for 18+ content)
        if (auth()->check()) {
            $user = auth()->user();
            $this->isFavorited = $user->favorites()
                ->where('item_id', 'lk:' . $this->movieSlug)
                ->where('item_type', 'movie')
                ->exists();
        }

        return null;
    }

    public function switchServer(int $index): void
    {
        if (isset($this->servers[$index])) {
            $this->activeServerIndex = $index;
            $this->currentEmbedUrl = $this->servers[$index]['embed_url'] ?? '';
        }
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            Notification::make()
                ->title('Silakan login terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'lk:' . $this->movieSlug)
            ->where('item_type', 'movie')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;
            Notification::make()
                ->title('Dihapus dari Daftar Tonton')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => 'lk:' . $this->movieSlug,
                'item_type' => 'movie',
                'title' => $this->movie['title'] ?? 'Film',
                'thumbnail' => $this->movie['poster'] ?? null,
                'url' => static::getUrl(['slug' => $this->movieSlug], panel: 'streaming'),
            ]);
            $this->isFavorited = true;
            Notification::make()
                ->title('Ditambahkan ke Daftar Tonton')
                ->success()
                ->send();
        }
    }
}
