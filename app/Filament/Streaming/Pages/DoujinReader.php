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

        static::$title = ($this->manga['title'] ?? 'Doujin') . ' - Chapter ' . ($this->chapter['chapter_number'] ?? '') . ' - KZSTRM';
    }
}
