<?php

namespace App\Filament;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use Awcodes\QuickCreate\QuickCreatePlugin;

class GalleryQuickCreatePlugin extends QuickCreatePlugin
{
    public function getResources(): array
    {
        return array_map(static function (array $resource): array {
            if ($resource['resource_name'] === GalleryItemResource::class) {
                $resource['action'] = null;
                $resource['url'] = GalleryItemResource::getUrl(parameters: ['action' => 'create']);
            }

            return $resource;
        }, parent::getResources());
    }
}
