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

            // Ensure DramaBox is included
            if (!collect($activePlatforms)->contains('id', 'dramabox')) {
                $activePlatforms[] = [
                    'id' => 'dramabox',
                    'name' => 'DramaBox',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dramabox.svg',
                    'api' => 'https://dramabox.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ReelBuzz is included
            if (!collect($activePlatforms)->contains('id', 'reelbuzz')) {
                $activePlatforms[] = [
                    'id' => 'reelbuzz',
                    'name' => 'ReelBuzz',
                    'logo' => 'https://api.dramabuzz.sbs/logos/reelbuzz.png',
                    'api' => 'https://reelbuzz.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ReelShort is included
            if (!collect($activePlatforms)->contains('id', 'reelshort')) {
                $activePlatforms[] = [
                    'id' => 'reelshort',
                    'name' => 'ReelShort',
                    'logo' => 'https://api.dramabuzz.sbs/logos/reelshort.svg',
                    'api' => 'https://reelshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure VibeShort is included
            if (!collect($activePlatforms)->contains('id', 'vibeshort')) {
                $activePlatforms[] = [
                    'id' => 'vibeshort',
                    'name' => 'VibeShort',
                    'logo' => null,
                    'api' => 'https://vibeshort.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StoryReel is included
            if (!collect($activePlatforms)->contains('id', 'storyreel')) {
                $activePlatforms[] = [
                    'id' => 'storyreel',
                    'name' => 'StoryReel',
                    'logo' => 'https://api.dramabuzz.sbs/logos/storyreel.png',
                    'api' => 'https://storyreel.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure MoboReels is included
            if (!collect($activePlatforms)->contains('id', 'moboreels')) {
                $activePlatforms[] = [
                    'id' => 'moboreels',
                    'name' => 'MoboReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/moboreels.svg',
                    'api' => 'https://moboreels.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StardustTV is included
            if (!collect($activePlatforms)->contains('id', 'stardusttv')) {
                $activePlatforms[] = [
                    'id' => 'stardusttv',
                    'name' => 'StardustTV',
                    'logo' => 'https://api.dramabuzz.sbs/logos/stardusttv.png',
                    'api' => 'https://stardusttv.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Normalize serialplus from API to serealplus
            $activePlatforms = collect($activePlatforms)->map(function ($p) {
                if ($p['id'] === 'serialplus') {
                    $p['id'] = 'serealplus';
                    $p['name'] = 'Sereal+';
                    $p['logo'] = 'https://api.dramabuzz.sbs/logos/serialplus.png';
                    $p['api'] = 'https://serealplus.goodbos.online';
                }
                return $p;
            })->unique('id')->values()->toArray();

            // Ensure Sereal+ is included
            if (!collect($activePlatforms)->contains('id', 'serealplus')) {
                $activePlatforms[] = [
                    'id' => 'serealplus',
                    'name' => 'Sereal+',
                    'logo' => 'https://api.dramabuzz.sbs/logos/serialplus.png',
                    'api' => 'https://serealplus.goodbos.online',
                    'status' => 'active',
                ];
            }

            return $activePlatforms;
        });

        $setting = ScraperSetting::getInstance();
        $onlineNetworks = $setting->dramabuzz_network_statuses;

        $nameMap = [
            'serealplus' => 'Sereal+',
            'serialplus' => 'Sereal+',
            'stardusttv' => 'StardustTV',
            'vibeshort' => 'VibeShort',
            'storyreel' => 'StoryReel',
            'reelshort' => 'ReelShort',
            'moboreels' => 'MoboReels',
            'shortmax' => 'ShortMax',
            'goodshort' => 'GoodShort',
            'dramabox' => 'DramaBox',
            'pinedrama' => 'PineDrama',
            'raptdrama' => 'RaptDrama',
            'reelbuzz' => 'ReelBuzz',
            'melolo' => 'Melolo',
        ];

        $this->networks = collect($rawNetworks)->map(function ($platform) use ($onlineNetworks, $nameMap) {
            if (isset($nameMap[$platform['id']])) {
                $platform['name'] = $nameMap[$platform['id']];
            }
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
