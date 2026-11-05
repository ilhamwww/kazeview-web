<?php

namespace App\Filament\Pages;

use App\Models\ScraperSetting;
use App\Services\DramabuzzService;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

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
        $data = $this->record->toArray();
        if (empty($data['dramabuzz_api_keys'])) {
            $data['dramabuzz_api_keys'] = $this->record->getDramabuzzApiKeysList();
        }
        if (empty($data['adult_keywords'])) {
            $data['adult_keywords'] = $this->record->getAdultKeywordsList();
        }
        if (!isset($data['adult_filter_enabled'])) {
            $data['adult_filter_enabled'] = true;
        }
        $this->form->fill($data);
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

                        Forms\Components\Repeater::make('dramabuzz_api_keys')
                            ->label('Dramabuzz API Keys (Utama & Cadangan)')
                            ->simple(
                                Forms\Components\TextInput::make('key')
                                    ->placeholder('5193CD21848193E43FC399BA4D73BB13')
                                    ->required()
                            )
                            ->addActionLabel('+ Tambah Cadangan API Key')
                            ->reorderable()
                            ->default(['5193CD21848193E43FC399BA4D73BB13'])
                            ->helperText('Bisa masukkan lebih dari 1 key cadangan. Urutan menentukan prioritas: Key #1 dicoba lebih dulu. Jika Key 1 expired/error, sistem otomatis memakai Key 2/3/4 (failover cerdas).'),

                        Forms\Components\CheckboxList::make('dramabuzz_network_statuses')
                            ->label('Jaringan Online (Aktif)')
                            ->helperText('Pilih jaringan penyiaran drama yang ingin DIAKTIFKAN (status Online, bisa diklik). Jaringan yang TIDAK dipilih akan berstatus Offline.')
                            ->bulkToggleable()
                            ->options(function () {
                                return Cache::remember('dramabuzz_networks_options', 300, function () {
                                    $options = [];
                                    $setting = ScraperSetting::first();
                                    if ($setting && !empty($setting->dramabuzz_api_url)) {
                                        try {
                                            $response = DramabuzzService::executeWithFailover(function ($key) use ($setting) {
                                                return \Illuminate\Support\Facades\Http::get($setting->dramabuzz_api_url, [
                                                    'key' => $key
                                                ]);
                                            });
                                            if ($response && $response->successful()) {
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

                Forms\Components\Section::make('Sensor Konten Dewasa (Adult Content Filter)')
                    ->description('Filter otomatis untuk mencegah video bokep, semi, atau konten vulgar masuk ke katalog publik KZSTRM.')
                    ->schema([
                        Forms\Components\Toggle::make('adult_filter_enabled')
                            ->label('Aktifkan Sensor Konten Dewasa')
                            ->helperText('Jika aktif, konten scraper yang judul, slug, atau tag-nya mengandung kata terlarang akan otomatis diblokir.')
                            ->default(true),

                        Forms\Components\TagsInput::make('adult_keywords')
                            ->label('Daftar Kata Kunci Terlarang (Blacklist adultKeywords)')
                            ->placeholder('Tambah kata kunci lalu tekan Enter')
                            ->helperText('Daftar kata kunci yang diblokir otomatis. Pisahkan dengan menekan Enter.')
                            ->suggestions(ScraperSetting::DEFAULT_ADULT_KEYWORDS)
                            ->default(ScraperSetting::DEFAULT_ADULT_KEYWORDS)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Pastikan dramabuzz_api_key utama tersinkronisasi dengan key urutan pertama
        $keys = $data['dramabuzz_api_keys'] ?? [];
        $firstKey = null;
        if (!empty($keys)) {
            $first = reset($keys);
            $firstKey = is_array($first) ? ($first['key'] ?? null) : $first;
        }
        if (!empty($firstKey)) {
            $data['dramabuzz_api_key'] = trim($firstKey);
        }

        $this->record->update($data);

        // Reset cache agar perubahan key langsung aktif
        Cache::forget('dramabuzz_networks');
        Cache::forget('dramabuzz_networks_options');
        Cache::forget('dramabuzz_active_key');
        Cache::forget('kz_adult_keywords_list');
        Cache::forget('kz_adult_filter_enabled');

        Notification::make()
            ->title('Scraper settings berhasil disimpan')
            ->success()
            ->send();
    }
}
