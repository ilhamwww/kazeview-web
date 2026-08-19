<?php

namespace App\Filament\Resources\CollectionCategoryResource\Pages;

use App\Filament\Resources\CollectionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCollectionCategories extends ListRecords
{
    protected static string $resource = CollectionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kategori'),
        ];
    }
}