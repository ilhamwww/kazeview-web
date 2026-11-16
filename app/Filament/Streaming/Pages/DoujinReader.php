<?php

namespace App\Filament\Streaming\Pages;

use App\Services\DoujindesuScraperService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DoujinReader extends Page
{
    protected static ?string $title = 'Baca Doujin 18+ - KZSTRM';
    protected static ?string $slug = 'doujin/read/{mangaSlug}/{chapterId}';
    protected static string $view = 'filament.streaming.pages.doujin-reader';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $mangaSlug = '';
    public string $chapterId = '';
    public ?array $manga = null;
    public ?array $chapter = null;
    public ?string $prevChapterId = null;
    public ?string $nextChapterId = null;
    public ?array $serverHistory = null;

    public function mount(string $mangaSlug, string $chapterId): void
    {
        $service = app(DoujindesuScraperService::class);
        $user = Auth::user();
        if (!$user || empty($user->nsfw_enabled)) {
            $this->redirect('/profile');
            return;
        }

        $this->mangaSlug = $mangaSlug;
        $this->chapterId = $chapterId;
        $this->manga = $service->getMangaDetail($mangaSlug);
        $this->chapter = $service->getChapterPages($chapterId);

        if (!$this->chapter) {
            abort(404, 'Chapter tidak ditemukan.');
        }

        // Cari prev & next chapter
        if (!empty($this->manga['chapters'])) {
            $chapters = $this->manga['chapters'];
            // Chapters biasanya descending (terbaru di index 0)
            foreach ($chapters as $idx => $ch) {
                if ($ch['id'] === $chapterId) {
                    $this->nextChapterId = $chapters[$idx - 1]['id'] ?? null;
                    $this->prevChapterId = $chapters[$idx + 1]['id'] ?? null;
                    break;
                }
            }
        }

        if (Auth::check()) {
            try {
                $hist = \App\Models\ComicReadHistory::where('user_id', Auth::id())
                    ->where('comic_id', 'doujin__' . $this->mangaSlug)
                    ->first();
                if ($hist && $hist->last_chapter_id) {
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
            } catch (\Throwable $e) {}
        }

        static::$title = ($this->manga['title'] ?? 'Doujin') . ' - Chapter ' . ($this->chapter['chapter_number'] ?? '') . ' - KZSTRM';
    }
}
