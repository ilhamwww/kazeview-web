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

        static::$title = ($this->detail['title'] ?? 'Detail Doujin') . ' - KZSTRM';
    }
}
