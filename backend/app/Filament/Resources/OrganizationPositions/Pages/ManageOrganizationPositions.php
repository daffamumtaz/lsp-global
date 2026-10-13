<?php

namespace App\Filament\Resources\OrganizationPositions\Pages;

use App\Filament\Concerns\HasAdminPreview;
use App\Filament\Resources\OrganizationPositions\OrganizationPositionResource;
use App\Models\OrganizationPosition;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Validation\ValidationException;

class ManageOrganizationPositions extends ManageRecords
{
    protected static string $resource = OrganizationPositionResource::class;

    protected string $view = 'filament.resources.organization-positions.manage';

    use HasAdminPreview;

    protected function previewKind(): string
    {
        return 'organization';
    }

    protected ?string $subheading = 'Atasan menentukan hubungan organisasi; baris dan posisi menentukan letak kotak. Kosongkan keduanya untuk susunan otomatis. Perubahan langsung diterbitkan.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Jabatan')->modalContentFooter(fn () => $this->previewView())];
    }

    public function callMountedAction(array $arguments = []): mixed
    {
        try {
            return parent::callMountedAction($arguments);
        } catch (ValidationException $exception) {
            $path = $this->getMountedActionSchema()?->getStatePath();
            if (! $path) {
                throw $exception;
            }
            $messages = [];
            foreach ($exception->errors() as $field => $errors) {
                if (! str_starts_with($field, $path.'.')) {
                    $field = $path.'.'.($this->getMountedAction()?->getName() === 'merge' ? 'position_ids' : $field);
                }
                $messages[$field] = [...($messages[$field] ?? []), ...$errors];
            }

            throw ValidationException::withMessages($messages);
        }
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        OrganizationPosition::reorderSiblings($order);
        $this->resetTable();
        $this->restorePreview();
    }
}
