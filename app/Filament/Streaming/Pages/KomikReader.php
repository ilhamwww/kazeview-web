<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\ShinigamiService;
use App\Services\KiryuuScraperService;
use App\Services\IzanamiScansService;
use App\Services\WurmzScraperService;

class KomikReader extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Baca Webtoon';
    protected static ?string $title = 'Baca Webtoon - KZSTRM';
    protected static ?string $slug = 'komik/{id}/read/{chapter_id}';
    protected static string $view = 'filament.streaming.pages.komik-reader';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $mangaId;
    public string $chapterId;
    public ?array $comic = null;
    public ?array $currentChapter = null;
    public ?array $prevChapter = null;
    public ?array $nextChapter = null;
    public array $allChapters = [];
    public array $pages = [];
    public ?array $serverHistory = null;

    public function mount(string $id, string $chapter_id): void
    {
        $this->mangaId = $id;
        $this->chapterId = $chapter_id;

        if (str_starts_with($id, 'native__')) {
            $service = app(\App\Services\NativeComicService::class);
        } elseif (str_starts_with($id, 'kiryuu__')) {
            $service = app(KiryuuScraperService::class);
        } elseif (str_starts_with($id, 'izanami__')) {
            $service = app(IzanamiScansService::class);
        } elseif (str_starts_with($id, 'wurmz__')) {
            $service = app(WurmzScraperService::class);
        } else {
            $service = app(ShinigamiService::class);
        }

        $this->comic = $service->getDetail($id);
        if (!$this->comic) {
            abort(404, 'Webtoon tidak ditemukan');
        }

        $allCh = $service->getChapters($id);
        // Urutkan ascending berdasarkan nomor chapter agar prev/next konsisten
        usort($allCh, fn($a, $b) => $a['number'] <=> $b['number']);
        $this->allChapters = $allCh;

        $currentIndex = -1;
        foreach ($this->allChapters as $idx => $ch) {
            if ($ch['id'] === $chapter_id) {
                $this->currentChapter = $ch;
                $currentIndex = $idx;
                break;
            }
        }

        if (!$this->currentChapter) {
            abort(404, 'Chapter tidak ditemukan');
        }

        // Tentukan prev dan next (karena ascending: prev = idx - 1, next = idx + 1)
        if ($currentIndex > 0) {
            $this->prevChapter = $this->allChapters[$currentIndex - 1];
        }
        if ($currentIndex >= 0 && $currentIndex < count($this->allChapters) - 1) {
            $this->nextChapter = $this->allChapters[$currentIndex + 1];
        }

        static::$title = ($this->comic['title'] ?? 'Webtoon') . ' - ' . $this->currentChapter['label'] . ' - KZSTRM';

        // Riwayat baca di server (untuk statistik /traffic & cloud sync). Gagal simpan tidak boleh mengganggu reader.
        if (auth()->check()) {
            try {
                $hist = \App\Models\ComicReadHistory::record(auth()->id(), $id, $this->comic, $this->currentChapter);
                if ($hist) {
                    $this->serverHistory = [
                        'chapterId' => $hist->last_chapter_id,
                        'label' => $hist->last_chapter_label,
                        'number' => (float) ($hist->last_chapter_number ?? ($this->currentChapter['number'] ?? 0)),
                        'progress' => (int) ($hist->last_progress ?? 0),
                        'panelIndex' => (int) ($hist->last_panel_index ?? 1),
                        'scrollY' => (int) ($hist->last_scroll_y ?? 0),
                        'updatedAt' => $hist->updated_at ? $hist->updated_at->getTimestampMs() : 0,
                    ];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Comic history skipped', ['error' => $e->getMessage()]);
            }
        }

        if ($service instanceof KiryuuScraperService) {
            $pagesData = $service->getChapterPages($chapter_id, $id);
        } elseif ($service instanceof WurmzScraperService || $service instanceof \App\Services\NativeComicService) {
            $this->pages = $service->getChapterPages($id, $chapter_id);
            return;
        } else {
            $pagesData = $service->getChapterPages($chapter_id);
        }
        $this->pages = $pagesData['pages'] ?? [];
    }
}
