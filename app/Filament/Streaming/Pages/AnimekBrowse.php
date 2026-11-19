<?php

namespace App\Filament\Streaming\Pages;

use App\Services\AnimekompiService;
use Filament\Pages\Page;

/**
 * Katalog lengkap dengan pencarian, filter tipe/status/tahun, urutan, dan paginasi.
 * Filter lewat query string biasa supaya URL bisa dibagikan.
 */
class AnimekBrowse extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $title = 'Jelajah Anime - KZSTRM';
    protected static ?string $slug = 'animek/jelajah';
    protected static string $view = 'filament.streaming.pages.animek-browse';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $filters = [];
    public array $result = [];
    public array $years = [];

    public function mount(): void
    {
        $req = request();
        $kind = (string) $req->query('kind', '');
        $status = (string) $req->query('status', '');
        $year = (string) $req->query('year', '');
        $sort = (string) $req->query('sort', 'terbaru');

        $this->filters = [
            'q' => mb_substr(trim((string) $req->query('q', '')), 0, 80),
            'kind' => in_array($kind, AnimekompiService::KINDS, true) ? $kind : '',
            'status' => array_key_exists($status, AnimekompiService::STATUSES) ? $status : '',
            'year' => preg_match('/^(19|20)\d{2}$/', $year) ? $year : '',
            'sort' => array_key_exists($sort, AnimekompiService::SORTS) ? $sort : 'terbaru',
        ];

        $this->result = AnimekompiService::browse($this->filters, max(1, (int) $req->query('page', 1)), 36);
        $this->years = AnimekompiService::years();
    }

    public function getTitle(): string
    {
        return $this->filters['q'] !== ''
            ? 'Cari "' . $this->filters['q'] . '" - Anime KZSTRM'
            : 'Jelajah Anime - KZSTRM';
    }
}
