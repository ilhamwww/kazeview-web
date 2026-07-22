<?php

namespace App\Filament\Pages;

use App\Models\ContactSetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ContactSettingsPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Contact Settings';
    protected static ?string $title = 'Contact Settings';
    protected static string $view = 'filament.pages.contact-settings-page';

    public array $data = [];
    public ?ContactSetting $record = null;

    public function mount(): void
    {
        $this->record = ContactSetting::current();
        $this->form->fill($this->record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Intro')
                    ->schema([
                        Forms\Components\TextInput::make('eyebrow')->required()->maxLength(255),
                        Forms\Components\TextInput::make('headline')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('intro')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Contact Image')
                            ->disk('public')
                            ->directory('contact')
                            ->image()
                            ->imageEditor()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp Number')
                            ->helperText('Contoh: 081234567890 atau 628123456789.')
                            ->maxLength(30),
                        Forms\Components\TextInput::make('location')->required()->maxLength(255),
                        Forms\Components\TextInput::make('availability')->required()->maxLength(255),
                        Forms\Components\TextInput::make('response_time')->required()->maxLength(255),
                        Forms\Components\Textarea::make('whatsapp_message')
                            ->label('Default WhatsApp Message')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Social Links')
                    ->schema([
                        Forms\Components\Repeater::make('social_links')
                            ->schema([
                                Forms\Components\TextInput::make('label')->required()->maxLength(50),
                                Forms\Components\TextInput::make('url')->required()->url(),
                            ])
                            ->default(ContactSetting::defaults()['social_links'])
                            ->reorderable()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Publish')
                    ->schema([
                        Forms\Components\TextInput::make('cta_label')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Publish Contact page')
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
            ->title('Contact settings berhasil disimpan')
            ->success()
            ->send();
    }
}