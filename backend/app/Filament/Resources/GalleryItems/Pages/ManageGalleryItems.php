<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Models\GalleryItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

class ManageGalleryItems extends ManageRecords
{
    protected static string $resource = GalleryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [GalleryItemResource::chooseMediaAction()];
    }

    public function createAlbumAction(): CreateAction
    {
        return GalleryItemResource::createAlbumAction()->model(GalleryItem::class)
            ->schema(fn (Schema $schema): Schema => GalleryItemResource::form($schema));
    }
}
