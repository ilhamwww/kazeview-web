<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use App\Models\ScraperSetting;

class Bstation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play';
    protected static ?string $navigationLabel = 'Bstation';
    protected static ?string $title = 'Bstation / WeAnim';
    protected static ?string $slug = 'bstation';
    protected static string $view = 'filament.streaming.pages.bstation';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public string $tab = 'dracin'; // 'dracin' or 'anime'
    public int $page = 1;
    public array $items = [];
    public bool $hasNext = true;
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->items = [];
        $this->page = 1;
        $this->hasNext = true;
        $this->loadItems();
    }

    public function mount(): void
    {
        if (request()->has('tab')) {
            $this->tab = request()->query('tab');
        }
        $this->loadItems();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->search = '';
        $this->page = 1;
        $this->items = [];
        $this->hasNext = true;
        $this->loadItems();
    }

    public function loadMore(): void
    {
        if ($this->hasNext) {
            $this->page++;
            $this->loadItems();
        }
    }

    public function loadItems(): void
    {
        if (!$this->hasNext) return;

        $setting = ScraperSetting::getInstance();
        $apiKey = $setting->dramabuzz_api_key ?? '';

        $baseUrl = 'https://weanim.goodbos.online/api/bstation';
        $isSearch = !empty($this->search);
        
        if ($isSearch) {
            $url = "{$baseUrl}/search?keyword=" . urlencode($this->search) . "&page={$this->page}&pagesize=18&code={$apiKey}";
        } else {
            if ($this->tab === 'dracin') {
                $url = "{$baseUrl}/dracin?page={$this->page}&pagesize=18&code={$apiKey}";
            } else {
                // Anime
                $url = "{$baseUrl}/anime?page={$this->page}&pagesize=18&lang=id&code={$apiKey}";
            }
        }

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful() && $response->json('success')) {
                $data = $response->json('data');
                $newItems = $isSearch ? ($data['ogv'] ?? []) : ($data['list'] ?? []);
                
                $this->items = array_merge($this->items, $newItems);
                $this->hasNext = $isSearch ? !empty($newItems) : ($data['has_next'] ?? false);
            } else {
                $this->hasNext = false;
            }
        } catch (\Exception $e) {
            $this->hasNext = false;
        }
    }

    protected function getViewData(): array
    {
        return [
            'items' => $this->items,
            'currentTab' => $this->tab,
            'hasNext' => $this->hasNext,
        ];
    }
}