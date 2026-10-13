<?php

namespace App\Filament\Resources\CertificationSchemes\Pages;

use App\Filament\Resources\CertificationSchemes\CertificationSchemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificationSchemes extends ManageRecords
{
    protected static string $resource = CertificationSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah')->modalWidth('5xl')];
    }
}
