<?php

namespace App\Filament\Pages;

use App\Models\WebsiteSetting;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class WebsiteSettingPage extends Page implements HasForms
{
    use InteractsWithForms, \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Website Settings';
    protected static string $view = 'filament.pages.website-setting-page';

    /** 🔥 INI YANG KAMU BELUM PUNYA */
    public array $data = [];

    public ?WebsiteSetting $record = null;

public function mount(): void
{
    $this->record = WebsiteSetting::first()
        ?? WebsiteSetting::create([]);

    $state = $this->record->toArray();
    $state['seo_settings'] = array_replace_recursive(
        WebsiteSetting::seoDefaults(),
        $this->record->seo_settings ?? [],
    );

    $this->form->fill($state);
}



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Website')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('first_name')->required(),
                        Forms\Components\TextInput::make('last_name')->required(),
                        Forms\Components\Textarea::make('description'),
                        Forms\Components\TextInput::make('wa')->prefix('+62')->required(),
                    ]),

                Forms\Components\Section::make('Logo dan Gambar Depan')
                    ->schema([

                        Forms\Components\FileUpload::make('logo')
    ->disk('public')
    ->directory('logo-website')
    ->image()
    ->multiple(false),

Forms\Components\FileUpload::make('hero_image')
    ->disk('public')
    ->directory('hero-images')
    ->image()
    ->multiple(false),


                    ]),

                Forms\Components\Section::make('Link Sosial / Tautan')
                    ->schema([
                        Forms\Components\Repeater::make('links')
                            ->schema([
                                Forms\Components\TextInput::make('label')->required(),
                                Forms\Components\TextInput::make('url')->url()->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('SEO Utama')
                    ->description('Pengaturan global. Judul dan deskripsi khusus halaman tetap digunakan jika tersedia.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('seo_settings.title')
                            ->label('SEO Title Global')
                            ->helperText('Disarankan 50–60 karakter.')
                            ->maxLength(60)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('seo_settings.description')
                            ->label('Meta Description Global')
                            ->helperText('Disarankan 140–160 karakter.')
                            ->rows(3)
                            ->maxLength(160)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('seo_settings.keywords')
                            ->label('Keywords')
                            ->helperText('Pisahkan dengan koma. Digunakan sebagai metadata pendukung.')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('seo_settings.author')
                            ->label('Author / Publisher')
                            ->maxLength(100),
                        Forms\Components\ColorPicker::make('seo_settings.theme_color')
                            ->label('Browser Theme Color'),
                        Forms\Components\TextInput::make('seo_settings.canonical_url')
                            ->label('Canonical Base URL')
                            ->placeholder(config('app.url'))
                            ->url()
                            ->helperText('Kosongkan untuk memakai APP_URL. Jangan tambahkan path halaman.')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('seo_settings.robots')
                            ->label('Default Robots')
                            ->options([
                                'index, follow' => 'Index, Follow',
                                'index, nofollow' => 'Index, No Follow',
                                'noindex, follow' => 'No Index, Follow',
                                'noindex, nofollow' => 'No Index, No Follow',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Open Graph & Social Preview')
                    ->description('Mengatur preview saat halaman dibagikan ke WhatsApp, Facebook, X, LinkedIn, dan platform lain.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('seo_image')
                            ->label('Default Social Share Image')
                            ->disk('public')
                            ->directory('seo-images')
                            ->image()
                            ->imageEditor()
                            ->helperText('Ideal 1200 × 630 px, JPG/PNG/WebP, maksimal 2 MB.')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('seo_settings.og_title')
                            ->label('Open Graph Title')
                            ->maxLength(60),
                        Forms\Components\TextInput::make('seo_settings.twitter_title')
                            ->label('X / Twitter Title')
                            ->maxLength(60),
                        Forms\Components\Textarea::make('seo_settings.og_description')
                            ->label('Open Graph Description')
                            ->rows(3)
                            ->maxLength(200),
                        Forms\Components\Textarea::make('seo_settings.twitter_description')
                            ->label('X / Twitter Description')
                            ->rows(3)
                            ->maxLength(200),
                        Forms\Components\Select::make('seo_settings.twitter_card')
                            ->label('Twitter Card Type')
                            ->options([
                                'summary_large_image' => 'Summary Large Image',
                                'summary' => 'Summary',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Search Engine Verification')
                    ->description('Masukkan hanya token content, bukan seluruh tag meta.')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('seo_settings.google_verification')
                            ->label('Google Search Console Token')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('seo_settings.bing_verification')
                            ->label('Bing Webmaster Token')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Structured Data / Schema.org')
                    ->description('Data ini menghasilkan JSON-LD Organization/ProfessionalService pada seluruh landing page.')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('seo_settings.organization_type')
                            ->label('Organization Type')
                            ->options([
                                'ProfessionalService' => 'Professional Service',
                                'LocalBusiness' => 'Local Business',
                                'Organization' => 'Organization',
                                'Corporation' => 'Corporation',
                                'Person' => 'Person / Personal Brand',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('seo_settings.organization_description')
                            ->label('Organization Description')
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('seo_settings.same_as')
                            ->label('Official Profile URLs')
                            ->simple(
                                Forms\Components\TextInput::make('url')
                                    ->url()
                                    ->required(),
                            )
                            ->helperText('Instagram, YouTube, TikTok, Behance, LinkedIn, dan profil resmi lainnya.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

public function save(): void
{
    $data = $this->form->getState();

    $this->record->update($data);

    Notification::make()
        ->title('Website settings berhasil disimpan')
        ->success()
        ->send();
}

}
