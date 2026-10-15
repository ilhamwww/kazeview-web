<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use App\Services\StreamingService;
use Livewire\Attributes\Url;

class NetworkDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'Network Detail';
    protected static ?string $title = 'Network';
    protected static ?string $slug = 'network/{slug}';
    protected static string $view = 'filament.streaming.pages.network-detail';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    // Allowed filter values (whitelist untuk mencegah parameter liar diteruskan ke API upstream)
    // Upstream /api/browse tidak mendukung filter tipe per network, jadi hanya sort.
    public const SORTS = ['latest' => 'Terbaru', 'views' => 'Terpopuler', 'rating' => 'Rating Tertinggi'];

    public string $networkSlug = '';
    public ?array $network = null;

    #[Url(as: 'sort')]
    public string $sort = 'latest';

    #[Url(as: 'page')]
    public int $page = 1;

    public array $items = [];
    public array $pagination = [];

    public function mount(string $slug): void
    {
        $this->networkSlug = $slug;
        $this->network = StreamingService::getNetworkDetail($slug);

        if (!$this->network) {
            abort(404);
        }

        $this->sanitize();
        $this->loadItems();
    }

    public function getTitle(): string
    {
        return $this->network['title'] ?? 'Network';
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        $this->page = 1;
        $this->sanitize();
        $this->loadItems();
    }

    public function goToPage(int $page): void
    {
        $this->page = $page;
        $this->sanitize();
        $this->loadItems();
        $this->dispatch('network-page-changed');
    }

    private function sanitize(): void
    {
        if (!array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'latest';
        }
        $this->page = max(1, min($this->page, 500));
    }

    private function loadItems(): void
    {
        $result = StreamingService::getNetworkMedia(
            $this->networkSlug,
            $this->page,
            36,
            $this->sort
        );

        $this->items = array_map(function (array $item) {
            $isSeries = ($item['type'] ?? '') === 'tv_series';
            $item['url'] = empty($item['slug']) ? '#' : ($isSeries
                ? ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                : WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming'));
            return $item;
        }, $result['data'] ?? []);

        $this->pagination = $result['pagination'] ?? ['page' => $this->page, 'totalPages' => 1, 'total' => 0];
    }
}
