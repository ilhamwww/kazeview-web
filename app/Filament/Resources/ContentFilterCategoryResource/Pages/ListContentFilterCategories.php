<?php

namespace App\Filament\Resources\ContentFilterCategoryResource\Pages;

use App\Filament\Resources\ContentFilterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentFilterCategories extends ListRecords
{
    protected static string $resource = ContentFilterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kategori Filter'),
        ];
    }
}