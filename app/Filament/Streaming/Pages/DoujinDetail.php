<?php

namespace App\Filament\Streaming\Pages;

use App\Services\DoujindesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DoujinDetail extends Page
{
    protected static ?string $title = 'Detail Doujin 18+ - KZSTRM';
    protected static ?string $slug = 'doujin/detail/{mangaSlug}';
    protected static string $view = 'filament.streaming.pages.doujin-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $mangaSlug = '';
    public ?array $detail = null;
    public string $viewMode = 'list'; // list, kotatsu
    public ?array $serverHistory = null;
    public array $readChapters = [];
    public bool $isFavorited = false;

    public function mount(string $mangaSlug): void
    {
        $service = app(DoujindesuScraperService::class);
        $user = Auth::user();
        if (!$user || empty($user->nsfw_enabled)) {
            $this->redirect('/profile');
            return;
        }

        $this->mangaSlug = $mangaSlug;
        $this->detail = $service->getMangaDetail($mangaSlug);

        if (!$this->detail) {
            abort(404, 'Manga tidak ditemukan.');
        }

        if (Auth::check()) {
            $this->isFavorited = Auth::user()->favorites()
                ->where('item_type', 'doujin')
                ->where('item_id', 'doujin:' . $this->mangaSlug)
                ->exists();

            try {
                $hist = \App\Models\ComicReadHistory::where('user_id', Auth::id())
                    ->where('comic_id', 'doujin__' . $this->mangaSlug)
                    ->first();
                if ($hist) {
                    $this->readChapters = is_array($hist->read_chapters) ? $hist->read_chapters : [];
                    if ($hist->last_chapter_id) {
                        $this->serverHistory = [
                            'chapterId' => $hist->last_chapter_id,
                            'label' => $hist->last_chapter_label,
                            'number' => (float) ($hist->last_chapter_number ?? 0),
                            'progress' => (int) ($hist->last_progress ?? 0),
                            'panelIndex' => (int) ($hist->last_panel_index ?? 1),
                            'scrollY' => (int) ($hist->last_scroll_y ?? 0),
                            'updatedAt' => $hist->updated_at ? $hist->updated_at->getTimestampMs() : 0,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        static::$title = ($this->detail['title'] ?? 'Detail Doujin') . ' - KZSTRM';
    }

    public function toggleFavorite(): void
    {
        if (!Auth::check()) {
            \Filament\Notifications\Notification::make()
                ->title('Silakan login terlebih dahulu untuk menyimpan ke Favorit')
                ->warning()
                ->send();
            return;
        }

        $user = Auth::user();
        $favorite = $user->favorites()
            ->where('item_type', 'doujin')
            ->where('item_id', 'doujin:' . $this->mangaSlug)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()->title('Dihapus dari Favorit')->success()->send();
            return;
        }

        $user->favorites()->create([
            'item_id' => 'doujin:' . $this->mangaSlug,
            'item_type' => 'doujin',
            'title' => $this->detail['title'] ?? 'Doujin',
            'thumbnail' => $this->detail['cover'] ?? null,
            'url' => '/doujin/detail/' . $this->mangaSlug,
        ]);
        $this->isFavorited = true;
        \Filament\Notifications\Notification::make()->title('Ditambahkan ke Favorit')->success()->send();
    }
}
