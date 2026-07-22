<?php

namespace App\Filament\Pages;

use App\Models\AboutSetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AboutSettingsPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'About Settings';
    protected static ?string $title = 'About Settings';
    protected static string $view = 'filament.pages.about-settings-page';

    public array $data = [];
    public ?AboutSetting $record = null;

    public function mount(): void
    {
        $this->record = AboutSetting::current();
        $this->form->fill($this->record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('About Intro')
                    ->schema([
                        Forms\Components\TextInput::make('eyebrow')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('headline')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('intro')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('hero_image')
                            ->label('Hero Image')
                            ->disk('public')
                            ->directory('about')
                            ->image()
                            ->imageEditor()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Studio Story')
                    ->schema([
                        Forms\Components\TextInput::make('story_title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('story_image')
                            ->label('Story Image')
                            ->disk('public')
                            ->directory('about')
                            ->image()
                            ->imageEditor()
                            ->nullable(),
                        Forms\Components\Textarea::make('story_body')
                            ->rows(7)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Capabilities')
                    ->schema([
                        Forms\Components\Repeater::make('capabilities')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->default(AboutSetting::defaults()['capabilities'])
                            ->addActionLabel('Add Capability')
                            ->reorderable()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Identity and CTA')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('established')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cta_title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cta_label')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('cta_url')
                            ->label('CTA URL')
                            ->url()
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Publish About page')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->record->update($this->form->getState());

        Notification::make()
            ->title('About settings berhasil disimpan')
            ->success()
            ->send();
    }
}