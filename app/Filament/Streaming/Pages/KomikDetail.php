<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\ShinigamiService;
use App\Services\KiryuuScraperService;
use App\Services\IzanamiScansService;
use App\Services\WurmzScraperService;

class KomikDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Detail Webtoon';
    protected static ?string $title = 'Detail Webtoon - KZSTRM';
    // Detail komik dipindah ke /komik/detail/{id} agar /komik/{provider} murni menjadi route katalog provider
protected static ?string $slug = 'komik/detail/{id}';
    protected static string $view = 'filament.streaming.pages.komik-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $mangaId;
    public ?array $comic = null;
    public array $chapters = [];
    public string $searchChapter = '';
    public string $sortOrder = 'desc'; // desc = terbaru, asc = awal
    public bool $isFavorited = false;
    public ?array $serverHistory = null;

    public function mount(string $id): void
    {
        $this->mangaId = $id;

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

        static::$title = ($this->comic['title'] ?? 'Detail Webtoon') . ' - KZSTRM';
        $this->chapters = $service->getChapters($id);

        if (auth()->check()) {
            $this->isFavorited = auth()->user()->favorites()
                ->where('item_id', 'comic:' . $this->mangaId)
                ->where('item_type', 'komik')
                ->exists();

            try {
                $hist = \App\Models\ComicReadHistory::where('user_id', auth()->id())
                    ->where('comic_id', $this->mangaId)
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
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            \Filament\Notifications\Notification::make()
                ->title('Silakan login terlebih dahulu untuk menyimpan ke Favorit')
                ->warning()
                ->send();
            return;
        }

        $user = auth()->user();
        $fav = $user->favorites()
            ->where('item_id', 'comic:' . $this->mangaId)
            ->where('item_type', 'komik')
            ->first();

        if ($fav) {
            $fav->delete();
            $this->isFavorited = false;
            \Filament\Notifications\Notification::make()
                ->title('Dihapus dari Daftar Favorit')
                ->success()
                ->send();
        } else {
            $user->favorites()->create([
                'item_id' => 'comic:' . $this->mangaId,
                'item_type' => 'komik',
                'title' => $this->comic['title'] ?? 'Komik',
                'thumbnail' => $this->comic['cover'] ?? null,
                'url' => '/komik/detail/' . $this->mangaId,
            ]);
            $this->isFavorited = true;
            \Filament\Notifications\Notification::make()
                ->title('Ditambahkan ke Daftar Favorit')
                ->success()
                ->send();
        }
    }

    public function toggleSort(): void
    {
        $this->sortOrder = $this->sortOrder === 'desc' ? 'asc' : 'desc';
    }

    public function getFilteredChaptersProperty(): array
    {
        $list = $this->chapters;

        if (!empty($this->searchChapter)) {
            $q = strtolower(trim($this->searchChapter));
            $list = array_filter($list, function ($c) use ($q) {
                return str_contains(strtolower($c['label']), $q) || str_contains((string) $c['number'], $q);
            });
        }

        if ($this->sortOrder === 'asc') {
            usort($list, fn($a, $b) => $a['number'] <=> $b['number']);
        } else {
            usort($list, fn($a, $b) => $b['number'] <=> $a['number']);
        }

        return array_values($list);
    }
}
