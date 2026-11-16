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
}
