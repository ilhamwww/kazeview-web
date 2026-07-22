<?php

namespace App\Filament\Pages;

use App\Models\ScraperSetting;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class ScraperSettingPage extends Page implements HasForms
{
    use InteractsWithForms, \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Scraper';
    protected static ?string $navigationLabel = 'Scraper Settings';
    protected static string $view = 'filament.pages.scraper-setting-page';

    public array $data = [];

    public ?ScraperSetting $record = null;

    public function mount(): void
    {
        $this->record = ScraperSetting::getInstance();
        $this->form->fill($this->record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Anoboy Domain')
                    ->description('Atur domain sumber scraping anime. Default: https://anoboy.be/')
                    ->schema([
                        Forms\Components\TextInput::make('anoboy_domain')
                            ->label('Domain Anoboy')
                            ->placeholder('https://anoboy.be/')
                            ->helperText('Contoh: https://anoboy.be/, https://anoboy.com/, https://anoboy.me/')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->default('https://anoboy.be/'),
                    ]),

                Forms\Components\Section::make('IDLIX Domain')
                    ->description('Atur domain sumber scraping IDLIX. Default: https://z2.idlixku.com/')
                    ->schema([
                        Forms\Components\TextInput::make('idlix_domain')
                            ->label('Domain IDLIX')
                            ->placeholder('https://z2.idlixku.com/')
                            ->helperText('Contoh: https://z2.idlixku.com/')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->default('https://z2.idlixku.com/'),
                    ]),

                Forms\Components\Section::make('Dramabuzz API (Dracin)')
                    ->description('Atur API URL dan API Key untuk mengambil daftar platform drama (Dracin).')
                    ->schema([
                        Forms\Components\TextInput::make('dramabuzz_api_url')
                            ->label('API URL')
                            ->placeholder('https://api.dramabuzz.sbs/api/status')
                            ->helperText('Contoh: https://api.dramabuzz.sbs/api/status')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->default('https://api.dramabuzz.sbs/api/status'),
                        Forms\Components\TextInput::make('dramabuzz_api_key')
                            ->label('API Key (key)')
                            ->placeholder('5193CD21848193E43FC399BA4D73BB13')
                            ->required()
                            ->maxLength(255)
                            ->default('5193CD21848193E43FC399BA4D73BB13'),
                        Forms\Components\CheckboxList::make('dramabuzz_network_statuses')
                            ->label('Jaringan Online (Aktif)')
                            ->helperText('Pilih jaringan penyiaran drama yang ingin DIAKTIFKAN (status Online, bisa diklik). Jaringan yang TIDAK dipilih akan berstatus Offline.')
                            ->bulkToggleable()
                            ->options(function () {
                                return \Illuminate\Support\Facades\Cache::remember('dramabuzz_networks_options', 300, function () {
                                    $options = [];
                                    $setting = \App\Models\ScraperSetting::first();
                                    if ($setting && !empty($setting->dramabuzz_api_url)) {
                                        try {
                                            $response = \Illuminate\Support\Facades\Http::get($setting->dramabuzz_api_url, [
                                                'key' => $setting->dramabuzz_api_key
                                            ]);
                                            if ($response->successful()) {
                                                $platforms = $response->json('platforms') ?: [];
                                                $options = collect($platforms)->pluck('name', 'id')->toArray();
                                            }
                                        } catch (\Exception $e) {
                                            // Ignore
                                        }
                                    }

                                    // Jaringan tambahan yang tidak muncul dari API DramaBuzz status
                                    if (!isset($options['pinedrama'])) {
                                        $options['pinedrama'] = 'PineDrama';
                                    }
                                    if (!isset($options['netshort'])) {
                                        $options['netshort'] = 'Netshort';
                                    }
                                    if (!isset($options['shortmax'])) {
                                        $options['shortmax'] = 'Shortmax';
                                    }

                                    if (!isset($options['raptdrama'])) {
                                        $options['raptdrama'] = 'RaptDrama';
                                    }

                                    if (!isset($options['goodshort'])) {
                                        $options['goodshort'] = 'GoodShort';
                                    }

                                    return $options;
                                });
                            })
                            ->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);

        Notification::make()
            ->title('Scraper settings berhasil disimpan')
            ->success()
            ->send();
    }
}