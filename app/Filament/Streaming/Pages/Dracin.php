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

            // Ensure Melolo is included
            if (!collect($activePlatforms)->contains('id', 'melolo')) {
                $activePlatforms[] = [
                    'id' => 'melolo',
                    'name' => 'Melolo',
                    'logo' => 'https://api.dramabuzz.sbs/logos/melolo.svg',
                    'api' => 'https://melolo.goodbos.online',
                    'status' => 'active',
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

            // Ensure FlexTV is included
            if (!collect($activePlatforms)->contains('id', 'flextv')) {
                $activePlatforms[] = [
                    'id' => 'flextv',
                    'name' => 'FlexTV',
                    'logo' => 'https://api.dramabuzz.sbs/logos/flextv.svg',
                    'api' => 'https://flextv.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure ShortsWave is included
            if (!collect($activePlatforms)->contains('id', 'shortswave')) {
                $activePlatforms[] = [
                    'id' => 'shortswave',
                    'name' => 'ShortsWave',
                    'logo' => 'https://api.dramabuzz.sbs/logos/shortwave.png',
                    'api' => 'https://shortwave.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure FreeReels is included
            if (!collect($activePlatforms)->contains('id', 'freereels')) {
                $activePlatforms[] = [
                    'id' => 'freereels',
                    'name' => 'FreeReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/freereels.svg',
                    'api' => 'https://drakula.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure FlickReels is included
            if (!collect($activePlatforms)->contains('id', 'flickreels')) {
                $activePlatforms[] = [
                    'id' => 'flickreels',
                    'name' => 'FlickReels',
                    'logo' => 'https://api.dramabuzz.sbs/logos/flickreels.svg',
                    'api' => 'https://flickreels.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure StarShort is included
            if (!collect($activePlatforms)->contains('id', 'starshort')) {
                $activePlatforms[] = [
                    'id' => 'starshort',
                    'name' => 'StarShort',
                    'logo' => 'https://api.dramabuzz.sbs/logos/starshort.png',
                    'api' => 'https://drakula.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure DotDrama is included
            if (!collect($activePlatforms)->contains('id', 'dotdrama')) {
                $activePlatforms[] = [
                    'id' => 'dotdrama',
                    'name' => 'DotDrama',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dotdrama.svg',
                    'api' => 'https://dotdrama.goodbos.online',
                    'status' => 'active',
                ];
            }

            // Ensure DramaWave is included
            if (!collect($activePlatforms)->contains('id', 'dramawave')) {
                $activePlatforms[] = [
                    'id' => 'dramawave',
                    'name' => 'DramaWave',
                    'logo' => 'https://api.dramabuzz.sbs/logos/dramawave.svg',
                    'api' => 'https://dramawave.goodbos.online',
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
            'flextv' => 'FlexTV',
            'shortswave' => 'ShortsWave',
            'freereels' => 'FreeReels',
            'flickreels' => 'FlickReels',
            'starshort' => 'StarShort',
            'dotdrama' => 'DotDrama',
            'dramawave' => 'DramaWave',
        ];

        $this->networks = collect($rawNetworks)
            ->map(function ($platform) use ($onlineNetworks, $nameMap) {
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
