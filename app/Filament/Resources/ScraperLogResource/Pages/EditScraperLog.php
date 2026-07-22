<?php

namespace App\Filament\Resources\ScraperLogResource\Pages;

use App\Filament\Resources\ScraperLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScraperLog extends EditRecord
{
    protected static string $resource = ScraperLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
