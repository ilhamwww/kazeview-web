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

    public function mount(string $id): void
    {
        $this->mangaId = $id;

        if (str_starts_with($id, 'kiryuu__')) {
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
