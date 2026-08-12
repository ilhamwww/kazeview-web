<?php

namespace App\Filament\Resources\ContentFilterCategoryResource\Pages;

use App\Filament\Resources\ContentFilterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentFilterCategory extends EditRecord
{
    protected static string $resource = ContentFilterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}