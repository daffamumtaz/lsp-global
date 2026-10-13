<?php

namespace App\Filament\Resources\Processes\Pages;

use App\Filament\AdminPreview;
use App\Filament\Concerns\HasAdminPreview;
use App\Filament\Resources\Processes\ProcessResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class EditProcess extends EditRecord
{
    use HasAdminPreview;

    protected function previewKind(): string
    {
        return 'process';
    }

    public function getFooter(): ?View
    {
        return $this->previewView();
    }

    protected function afterSave(): void
    {
        $this->restorePreview();
    }

    #[On('process-preview-draft')]
    public function previewRelation(int $processId, string $componentId, ?string $kind = null, ?int $id = null, ?array $draft = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        if ($processId !== $this->getRecord()->id) {
            return;
        }
        try {
            if ($draft !== null) {
                $this->form->validate();
            }
            $data = AdminPreview::process($this->getRecord(), $draft !== null ? (array) $this->form->getRawState() : [], $kind, $id, $draft);
            $this->previewError = '';
            $this->previewRevision++;
            $payload = $this->previewPayload($data, $draft !== null);
            $this->dispatch('admin-preview', componentId: $componentId, payload: $payload);
            $this->dispatch('admin-preview', componentId: $this->getId(), payload: $payload);
        } catch (ValidationException $exception) {
            $this->previewError = implode(' ', array_merge(...array_values($exception->errors())));
        }
    }

    protected static string $resource = ProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Preview Alur')->url(function (): string {
                $signed = URL::temporarySignedRoute('api.processes.preview', now()->addMinutes(10), ['slug' => $this->record->slug], absolute: false);

                return rtrim(config('services.frontend.url'), '/').'/informasi/alur-asesmen/'.rawurlencode($this->record->slug).'/preview?'.parse_url($signed, PHP_URL_QUERY);
            })->openUrlInNewTab(),
        ];
    }
}
