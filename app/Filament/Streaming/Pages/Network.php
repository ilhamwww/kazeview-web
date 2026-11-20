<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;

/**
 * /network — daftar penyedia (versi kita, tanpa nama upstream Indocast).
 */
class Network extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $title = 'Network - KZSTRM';
    protected static ?string $slug = 'network';
    protected static string $view = 'filament.streaming.pages.network';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $providers = [];

    public static function list(): array
    {
        return [
            [
                'id' => 'video',
                'api' => 'vidio',
                'name' => 'Video',
                'tag' => 'Film & Serial',
                'desc' => 'Film Indonesia dan serial orisinal pilihan.',
                'status' => 'soon',
                'gradient' => 'from-sky-500/25 to-blue-700/10',
                'ring' => 'ring-sky-400/25',
                'dot' => 'bg-sky-400',
                'initial' => 'V',
            ],
            [
                'id' => 'wetv',
                'name' => 'weTV',
                'tag' => 'Drama Asia',
                'desc' => 'Drama Mandarin, Korea, dan Thailand sub Indo.',
                'status' => 'ready',
                'route' => '/drama-asia',
                'gradient' => 'from-emerald-500/25 to-teal-700/10',
                'ring' => 'ring-emerald-400/25',
                'dot' => 'bg-emerald-400',
                'initial' => 'W',
            ],
            [
                'id' => 'sinetron',
                'name' => 'Sinetron',
                'tag' => 'Serial TV',
                'desc' => 'Sinetron dan serial televisi Indonesia.',
                'status' => 'soon',
                'gradient' => 'from-amber-500/25 to-orange-700/10',
                'ring' => 'ring-amber-400/25',
                'dot' => 'bg-amber-400',
                'initial' => 'S',
            ],
            [
                'id' => 'iqiyi',
                'name' => 'iQIYI',
                'tag' => 'Drama & Film',
                'desc' => 'Drama Asia, anime, dan film layar lebar.',
                'status' => 'soon',
                'gradient' => 'from-green-500/25 to-lime-700/10',
                'ring' => 'ring-green-400/25',
                'dot' => 'bg-green-400',
                'initial' => 'i',
            ],
            [
                'id' => 'viu',
                'name' => 'Viu',
                'tag' => 'Drama Asia',
                'desc' => 'Drama Korea, Jepang, dan Asia Tenggara.',
                'status' => 'soon',
                'gradient' => 'from-yellow-500/25 to-amber-700/10',
                'ring' => 'ring-yellow-400/25',
                'dot' => 'bg-yellow-400',
                'initial' => 'V',
            ],
            [
                'id' => 'hbo',
                'name' => 'HBO Max',
                'tag' => 'Film & Serial',
                'desc' => 'Serial dan film premium internasional.',
                'status' => 'soon',
                'gradient' => 'from-violet-500/25 to-purple-800/10',
                'ring' => 'ring-violet-400/25',
                'dot' => 'bg-violet-400',
                'initial' => 'H',
            ],
        ];
    }

    public function mount(): void
    {
        $this->providers = static::list();
    }
}
