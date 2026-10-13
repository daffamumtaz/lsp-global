<?php

namespace App\Filament\Concerns;

use App\Filament\AdminPreview;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

trait HasAdminPreview
{
    public int $previewRevision = 0;

    public string $previewError = '';

    abstract protected function previewKind(): string;

    public function previewData(?array $draft = null, ?int $id = null): array
    {
        return match ($this->previewKind()) {
            'organization' => AdminPreview::organization($id, $draft),
            'tuks' => AdminPreview::tuks($id, $draft),
            'process' => AdminPreview::process($this->getRecord(), $draft ?? []),
        };
    }

    public function previewView(): View
    {
        $base = rtrim(config('services.frontend.url'), '/');

        return view('filament.admin-preview', [
            'payload' => $this->previewPayload($this->previewData()),
            'componentId' => $this->getId(),
            'url' => $base.'/admin-preview/'.$this->previewKind(),
            'origin' => parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST).(parse_url($base, PHP_URL_PORT) ? ':'.parse_url($base, PHP_URL_PORT) : ''),
        ]);
    }

    protected function previewPayload(array $data, bool $draft = false): array
    {
        return ['type' => 'admin-preview:update', 'kind' => $this->previewKind(), 'revision' => $this->previewRevision, 'draft' => $draft, 'data' => $data];
    }

    public function updatePreview(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        try {
            $schema = $this->getMountedActionSchema();
            if ($schema === null && $this->previewKind() !== 'process') {
                $this->restorePreview();

                return;
            }
            ($schema ?? $this->form)->validate();
            $draft = $schema ? (array) $schema->getRawState() : (array) $this->form->getRawState();
            $id = $this->getMountedAction()?->getRecord()?->getKey();
            $data = $this->previewData($draft, $id);
            $this->previewError = '';
            $this->previewRevision++;
            $this->dispatch('admin-preview', componentId: $this->getId(), payload: $this->previewPayload($data, true));
        } catch (ValidationException $exception) {
            $this->previewError = implode(' ', array_merge(...array_values($exception->errors())));
        }
    }

    public function restorePreview(): void
    {
        $this->previewError = '';
        $this->previewRevision++;
        $this->dispatch('admin-preview', componentId: $this->getId(), payload: $this->previewPayload($this->previewData()));
    }

    public function unmountAction(bool|string|null $cancelParentActions = null): void
    {
        parent::unmountAction($cancelParentActions);
        $this->restorePreview();
    }

    protected function afterActionCalled(Action $action): void
    {
        $this->restorePreview();
    }
}
