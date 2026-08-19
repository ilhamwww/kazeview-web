<?php

namespace App\Filament\Resources\CollectionCategoryResource\Pages;

use App\Filament\Resources\CollectionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCollectionCategory extends EditRecord
{
    protected static string $resource = CollectionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(fn (): bool => $this->record->collections()->exists())
                ->tooltip(
                    fn (): ?string =>
                        $this->record->collections()->exists()
                            ? 'Kategori masih digunakan oleh Collection dan tidak dapat dihapus.'
                            : null,
                ),
        ];
    }
}