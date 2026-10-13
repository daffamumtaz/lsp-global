<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Assessors\AssessorResource;
use App\Models\Assessor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssessorStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Jumlah Asesor', Assessor::count())->url(AssessorResource::getUrl()),
            Stat::make('Asesor kritis', Assessor::query()->critical()->count())->description('Hampir habis (90 hari), masa tenggang, atau kedaluwarsa. Klik untuk melihat daftar.')->color('warning')->url(AssessorResource::getUrl(parameters: ['tableFilters' => ['critical' => ['isActive' => true]]])),
        ];
    }
}
