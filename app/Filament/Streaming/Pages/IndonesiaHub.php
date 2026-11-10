<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Models\IndonesiaMediaItem;

class IndonesiaHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';
    protected static ?string $navigationLabel = 'Indonesia';
    protected static ?string $title = 'Film & Series Indonesia - Streaming Networks';
    protected static ?string $slug = 'indonesia';
    protected static string $view = 'filament.streaming.pages.indonesia-hub';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $networks = [];

    public function mount(): void
    {
        $rebahinCount = IndonesiaMediaItem::where('source', 'server2')->where('is_published', true)->count();

        $this->networks = [
            [
                'slug' => 'rebahin',
                'name' => 'Rebahin',
                'title' => 'Rebahin Indonesia',
                'description' => 'Koleksi lengkap film bioskop lokal dan serial web series Indonesia dengan server pemutar stabil.',
                'color' => '#E50914',
                'accent' => 'from-red-600/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-red-600/50',
                'badge' => ($rebahinCount > 0 ? $rebahinCount : '112+') . ' Judul',
                'url' => '/indonesia/rebahin',
                'logo_text' => 'REBAHIN',
                'is_active' => true,
            ],
            [
                'slug' => 'lk21',
                'name' => 'Layarkaca21',
                'title' => 'LK21 Indonesia',
                'description' => 'Katalog Movie & Series bioskop Indonesia terbaru 2026 dengan direct MP4 HD bebas buffering.',
                'color' => '#EAB308',
                'accent' => 'from-amber-500/20 via-zinc-900 to-zinc-950',
                'border' => 'hover:border-amber-500/50',
                'badge' => '195+ Judul',
                'url' => '/indonesia/lk21',
                'logo_text' => 'LAYARKACA21',
                'is_active' => true,
            ],
        ];
    }
}
