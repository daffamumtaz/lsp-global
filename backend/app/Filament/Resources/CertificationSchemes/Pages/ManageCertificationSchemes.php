<?php

namespace App\Filament\Resources\CertificationSchemes\Pages;

use App\Filament\Resources\CertificationSchemes\CategoryTable;
use App\Filament\Resources\CertificationSchemes\CertificationSchemeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Livewire;

class ManageCertificationSchemes extends ManageRecords
{
    protected static string $resource = CertificationSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageCategories')->label('Kelola Kategori')->color('gray')->authorize('manage-admin')
                ->modalHeading('Kategori Skema Sertifikasi')->modalWidth('4xl')
                ->schema([Livewire::make(CategoryTable::class)->key('scheme-categories')])
                ->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
            CreateAction::make()->label('Tambah')->modalWidth('5xl'),
        ];
    }
}
