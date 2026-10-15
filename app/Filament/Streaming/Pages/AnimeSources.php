<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;

/**
 * Halaman pemilih sumber anime (V1 / V2 / V3) di /streaming/anime.
 */
class AnimeSources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Anime';
    protected static ?string $title = 'Anime';
    protected static ?string $slug = 'anime';
    protected static string $view = 'filament.streaming.pages.anime-sources';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $sources = [];

    public function mount(): void
    {
        $this->sources = [
            [
                'key' => 'v1',
                'label' => 'V1',
                'name' => 'Server 1',
                'description' => 'Koleksi anime TV, ONA & rilisan web terbaru dengan streaming Direct MP4 jernih tanpa iklan.',
                'features' => ['Direct MP4 HD', 'ONA & Series', 'No Download'],
                'available' => true,
                'url' => ExploreAnime::getUrl(panel: 'streaming'),
                'accent' => 'from-emerald-700/30 via-zinc-900 to-zinc-950',
                'color' => '#10B981',
            ],
            [
                'key' => 'v2',
                'label' => 'V2',
                'name' => 'Server 2',
                'description' => 'Koleksi anime TV & Movie lengkap dengan multi mirror server serta opsi download resolusi.',
                'features' => ['Streaming', 'Multi Mirror', 'Download'],
                'available' => true,
                'url' => ExploreAnimeV2::getUrl(panel: 'streaming'),
                'accent' => 'from-blue-700/30 via-zinc-900 to-zinc-950',
                'color' => '#3B82F6',
            ],
            [
                'key' => 'v3',
                'label' => 'V3',
                'name' => 'Server 3',
                'description' => 'Katalog anime lengkap berbagai generasi khusus streaming langsung tanpa download.',
                'features' => ['Streaming', 'Multi Mirror', 'No Download'],
                'available' => true,
                'url' => ExploreAnimeV3::getUrl(panel: 'streaming'),
                'accent' => 'from-purple-700/30 via-zinc-900 to-zinc-950',
                'color' => '#9333EA',
            ],
        ];
    }
}
