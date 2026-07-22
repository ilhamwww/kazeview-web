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

    $this->form->fill($this->record->toArray());
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
                                Forms\Components\TextInput::make('url')->required(),
                            ]),
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
