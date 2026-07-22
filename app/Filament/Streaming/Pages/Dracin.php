<?php

namespace App\Filament\Streaming\Pages;

use Filament\Pages\Page;

use App\Models\ScraperSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class Dracin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationLabel = 'Dracin';
    protected static ?string $title = 'Drama China';
    protected static ?string $slug = 'dracin';
    protected static string $view = 'filament.streaming.pages.dracin';
    protected static string $layout = 'filament.streaming.layout';
    protected static bool $shouldRegisterNavigation = false;

    public array $networks = [];

    public function mount(): void
    {
        $rawNetworks = Cache::remember('dramabuzz_networks', 3600, function () {
            try {
                $setting = ScraperSetting::getInstance();
                $apiUrl = $setting->dramabuzz_api_url;
                $apiKey = $setting->dramabuzz_api_key;

                if (!$apiUrl) return [];

                $response = Http::get($apiUrl, [
                    'key' => $apiKey
                ]);

                if ($response->successful() && $response->json('success')) {
                    $platforms = $response->json('platforms') ?? [];
                    $activePlatforms = collect($platforms)->filter(function ($platform) {
                        return $platform['status'] === 'active';
                    })->values()->toArray();
                } else {
                    $activePlatforms = [];
                }
            } catch (\Exception $e) {
                $activePlatforms = [];
            }
            
            // Ensure PineDrama is included
            if (!collect($activePlatforms)->contains('id', 'pinedrama')) {
                $activePlatforms[] = [
                    'id' => 'pinedrama',
                    'name' => 'PineDrama',
                    'logo' => null,
                    'api' => 'https://pinedrama.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure Netshort is included
            if (!collect($activePlatforms)->contains('id', 'netshort')) {
                $activePlatforms[] = [
                    'id' => 'netshort',
                    'name' => 'Netshort',
                    'logo' => null,
                    'api' => 'https://netshort.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure Shortmax is included
            if (!collect($activePlatforms)->contains('id', 'shortmax')) {
                $activePlatforms[] = [
                    'id' => 'shortmax',
                    'name' => 'Shortmax',
                    'logo' => null,
                    'api' => 'https://shortmax.goodbos.online',
                    'status' => 'active'
                ];
            }

            // Ensure RaptDrama is included
            if (!collect($activePlatforms)->contains('id', 'raptdrama')) {
                $activePlatforms[] = [
                    'id' => 'raptdrama',
                    'name' => 'RaptDrama',
                    'logo' => null,
                    'api' => 'https://raptdrama.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure GoodShort is included
            if (!collect($activePlatforms)->contains('id', 'goodshort')) {
                $activePlatforms[] = [
                    'id' => 'goodshort',
                    'name' => 'GoodShort',
                    'logo' => null,
                    'api' => 'https://goodshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            return $activePlatforms;
        });

        $setting = ScraperSetting::getInstance();
        $onlineNetworks = $setting->dramabuzz_network_statuses;

        $this->networks = collect($rawNetworks)->map(function ($platform) use ($onlineNetworks) {
            if (is_null($onlineNetworks)) {
                $platform['is_online'] = true;
            } else {
                $platform['is_online'] = in_array($platform['id'], $onlineNetworks);
            }
            return $platform;
        })->sortByDesc('is_online')->values()->toArray();
    }

    protected function getViewData(): array
    {
        return [
            'networks' => $this->networks,
        ];
    }
}
