<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CertificationSchemes\CertificationSchemeResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Filament\Resources\SitePages\SitePageResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected function getViewData(): array
    {
        return [
            'statistics' => [
                ['label' => 'Total Skema', 'value' => '—'],
                ['label' => 'Total Asesor', 'value' => '—'],
                ['label' => 'Total Tempat Uji Kompetensi (TUK)', 'value' => '—'],
            ],
            'quickActions' => [
                ['label' => 'Edit Halaman', 'url' => SitePageResource::getUrl()],
                ['label' => 'Tambah Galeri', 'url' => GalleryItemResource::getUrl()],
                ['label' => 'Tambah Skema', 'url' => CertificationSchemeResource::getUrl()],
                ['label' => 'Lihat Pesan', 'url' => ContactMessageResource::getUrl()],
            ],
            'messages' => [],
            'changes' => [],
        ];
    }
}
