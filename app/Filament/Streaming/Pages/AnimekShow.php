<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

/**
 * Detail anime: info, genre, sinopsis, daftar episode, rekomendasi.
 */
class AnimekShow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $title = 'Anime - KZSTRM';
    protected static ?string $slug = 'animek/{path}';
    protected static string $view = 'filament.streaming.pages.animek-show';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $media = null;
    public array $similar = [];
    public ?array $resume = null;

    public bool $isFavorited = false;

    public string $animePath = '';

    /**
     * Jangan tangkap halaman statis /animek/jelajah, /genre, /jadwal.
     */
    public static function routes(Panel $panel): void
    {
        Route::get(static::getRoutePath(), static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->where('path', '(?!jelajah$|genre$|jadwal$)[A-Za-z0-9-]+')
            ->name(static::getRelativeRouteName());
    }

    public function mount(string $path): void
    {
        $this->animePath = $path;
        $this->media = AnimekompiService::detail($path);

        if (!$this->media) {
            abort(404);
        }

        $this->similar = AnimekompiService::similar($this->media, 12);
        $this->resume = $this->buildResume($path);

        $this->isFavorited = auth()->check()
            && auth()->user()->favorites()
                ->where('item_id', 'animek:' . $path)
                ->where('item_type', 'anime')
                ->exists();
    }

    /**
     * Episode terakhir yang ditonton user untuk judul ini (kalau ada).
     */
    protected function buildResume(string $path): ?array
    {
        if (!auth()->check()) {
            return null;
        }

        // Riwayat animek tersimpan dalam dua bentuk: "animek:<path>" dengan
        // nomor episode, dan "<path>" dengan id/slug episode.
        $row = \App\Models\WatchHistory::where('user_id', auth()->id())
            ->where('item_type', 'anime')
            ->whereIn('item_id', ['animek:' . $path, $path])
            ->orderByDesc('updated_at')
            ->first();

        if (!$row || !$row->last_episode) {
            return null;
        }

        $target = null;
        $number = 0;

        foreach ($this->media['episodes'] ?? [] as $ep) {
            $epId = (string) ($ep['id'] ?? '');
            $epNumber = (int) ($ep['number'] ?? 0);

            if ($epId !== '' && $epId === (string) $row->last_episode) {
                $target = $ep;
                $number = $epNumber;
                break;
            }

            if ($epNumber > 0 && $epNumber === (int) $row->last_episode) {
                $target = $ep;
                $number = $epNumber;
                break;
            }
        }

        $number = $number ?: (int) preg_replace('/\D+/', '', (string) $row->last_episode);

        return [
            'number' => $number,
            'url' => $row->url ?: ($target['url'] ?? null),
            'episode_id' => $target['id'] ?? null,
            'percent' => (int) ($row->progress_percent ?? 0),
            'seconds' => (int) ($row->progress_seconds ?? 0),
            'ago' => $row->updated_at ? $row->updated_at->diffForHumans() : null,
        ];
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $itemId = 'animek:' . $this->animePath;

        $fav = $user->favorites()
            ->where('item_id', $itemId)
            ->where('item_type', 'anime')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;

            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Favorit')
                ->success()
                ->send();

            return;
        }

        $user->favorites()->create([
            'item_id' => $itemId,
            'item_type' => 'anime',
            'title' => $this->media['title'] ?? 'Anime',
            'thumbnail' => $this->media['poster'] ?? ($this->media['thumbnail'] ?? null),
            'url' => '/animek/' . $this->animePath,
        ]);

        $this->isFavorited = true;

        \Filament\Notifications\Notification::make()
            ->title('Ditambahkan ke Favorit')
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return ($this->media['title'] ?? 'Anime') . ' Sub Indo - KZSTRM';
    }
}
