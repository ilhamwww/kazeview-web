<?php

namespace App\Filament\Resources\ScraperLogResource\Pages;

use App\Filament\Resources\ScraperLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScraperLogs extends ListRecords
{
    protected static string $resource = ScraperLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
