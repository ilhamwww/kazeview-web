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
        $this->media = AnimekompiService::detail($path);

        if (!$this->media) {
            abort(404);
        }

        $this->similar = AnimekompiService::similar($this->media, 12);
        $this->resume = $this->buildResume($path);
    }

    /**
     * Episode terakhir yang ditonton user untuk judul ini (kalau ada).
     */
    protected function buildResume(string $path): ?array
    {
        if (!auth()->check()) {
            return null;
        }

        $row = \App\Models\WatchHistory::where('user_id', auth()->id())
            ->where('item_type', 'anime')
            ->where('item_id', 'animek:' . $path)
            ->first();

        if (!$row || !$row->last_episode) {
            return null;
        }

        $target = null;
        foreach ($this->media['episodes'] ?? [] as $ep) {
            if ((int) $ep['number'] === (int) $row->last_episode) {
                $target = $ep;
                break;
            }
        }

        return [
            'number' => (int) $row->last_episode,
            'url' => $row->url ?: ($target['url'] ?? null),
            'episode_id' => $target['id'] ?? null,
            'percent' => (int) ($row->progress_percent ?? 0),
            'seconds' => (int) ($row->progress_seconds ?? 0),
            'ago' => $row->updated_at ? $row->updated_at->diffForHumans() : null,
        ];
    }

    public function getTitle(): string
    {
        return ($this->media['title'] ?? 'Anime') . ' Sub Indo - KZSTRM';
    }
}
