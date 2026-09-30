<?php

namespace App\Filament\Resources\OrganizationPositions\Pages;

use App\Filament\Resources\OrganizationPositions\OrganizationPositionResource;
use App\Models\OrganizationPosition;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageOrganizationPositions extends ManageRecords
{
    protected static string $resource = OrganizationPositionResource::class;

    protected ?string $subheading = 'Pilih jabatan atasan untuk mengubah hierarki. Drag-and-drop hanya mengubah urutan di bawah atasan yang sama. Perubahan langsung diterbitkan.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Jabatan')];
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        OrganizationPosition::reorderSiblings($order);
        $this->resetTable();
    }
}
