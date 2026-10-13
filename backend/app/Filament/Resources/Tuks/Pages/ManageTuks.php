<?php

namespace App\Filament\Resources\Tuks\Pages;

use App\Filament\Concerns\HasAdminPreview;
use App\Filament\Resources\Tuks\TukResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\View\View;

class ManageTuks extends ManageRecords
{
    use HasAdminPreview;

    protected function previewKind(): string
    {
        return 'tuks';
    }

    public function getFooter(): ?View
    {
        return $this->previewView();
    }

    protected static string $resource = TukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah')->modalWidth('5xl')->modalContentFooter(fn () => $this->previewView()),
        ];
    }
}
