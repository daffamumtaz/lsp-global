<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Assessors\AssessorResource;
use App\Filament\Resources\CertificationSchemes\CertificationSchemeResource;
use App\Filament\Resources\Tuks\TukResource;
use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\Tuk;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CertificationStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static int $gridW = 12;

    public static int $gridMinW = 6;

    public static int $gridH = 3;

    public static int $gridMinH = 3;

    public static bool $gridSizeToContent = false;

    public function getTitle(): string
    {
        return 'Ringkasan sertifikasi';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Jumlah Skema', CertificationScheme::count())->url(CertificationSchemeResource::getUrl()),
            Stat::make('Jumlah TUK', Tuk::count())->url(TukResource::getUrl()),
            Stat::make('Jumlah Asesor', Assessor::count())->url(AssessorResource::getUrl()),
        ];
    }
}
