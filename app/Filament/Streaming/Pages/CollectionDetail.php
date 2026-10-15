<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;

class CollectionDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Collection Detail';
    protected static ?string $title = 'Collection Detail';
    protected static ?string $slug = 'collection/{slug}';
    protected static string $view = 'filament.streaming.pages.collection-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $collectionSlug;
    public ?array $collectionData = null;
    public ?array $collection = null;
    public array $breadcrumbs = [];
    public array $sections = [];

    public function mount(string $slug): void
    {
        $this->collectionSlug = $slug;
        $this->collectionData = StreamingService::getCollectionDetail($slug);

        if (!$this->collectionData || empty($this->collectionData['collection'])) {
            abort(404);
        }

        $this->collection = $this->collectionData['collection'];
        $this->breadcrumbs = $this->collectionData['breadcrumbs'] ?? [];
        $this->sections = $this->collectionData['sections'] ?? [];
    }
}
