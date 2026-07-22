<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class StreamingHome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Home';
    protected static ?string $title = 'Streaming Home';
    protected static ?string $slug = '';
    protected static string $view = 'filament.streaming.pages.streaming-home';
    protected static string $layout = 'filament.streaming.layout';

    public array $heroes = [];
    public array $sections = [];

    public function mount(): void
    {
        $homepageData = StreamingService::getHomepageData();
        
        $this->heroes = $homepageData['heroes'] ?? [];
        $this->sections = $homepageData['sections'] ?? [];

        // Fallback to dummy data if API fails or returns empty
        if (empty($this->sections) && empty($this->heroes)) {
            $dummy = StreamingService::getDummyData();
            $this->heroes = array_slice($dummy, 0, 5);
            $this->sections = [
                [
                    'title' => 'Latest Movies',
                    'slug' => 'latest-movies',
                    'items' => $dummy
                ]
            ];
        }
    }
}