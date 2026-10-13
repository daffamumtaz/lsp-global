<?php

namespace App\Filament\Resources\Assessors\Pages;

use App\Filament\Resources\Assessors\AssessorResource;
use App\Filament\Widgets\AssessorStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Validation\ValidationException;

class ManageAssessors extends ManageRecords
{
    protected static string $resource = AssessorResource::class;

    protected function getHeaderWidgets(): array
    {
        return [AssessorStats::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah')->modalWidth('5xl'),
        ];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $schema = $this->getMountedActionSchema();
        if ($number = $schema?->getComponent('certificate_number')) {
            foreach ($exception->validator->errors()->get($schema->getStatePath().'.certificate') as $message) {
                $exception->validator->errors()->add($number->getStatePath(), $message);
            }
        }

        parent::onValidationError($exception);
    }
}
