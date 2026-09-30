<?php

namespace App\Filament\Resources\SitePages\Pages;

use App\Filament\Resources\SitePages\SitePageResource;
use App\Models\SitePage;
use Filament\Resources\Pages\Page;
use Spatie\Activitylog\Models\Activity;

class ListSitePageActivities extends Page
{
    protected static string $resource = SitePageResource::class;

    protected static ?string $title = 'Riwayat Landing Page';

    protected string $view = 'filament.resources.site-pages.pages.list-site-page-activities';

    public int $sitePageId;

    public function mount(int|string $record): void
    {
        $this->sitePageId = SitePage::query()->where('kind', 'landing')->findOrFail($record)->getKey();
    }

    protected function getViewData(): array
    {
        return [
            'activities' => Activity::query()
                ->where('subject_type', SitePage::class)
                ->where('subject_id', $this->sitePageId)
                ->with('causer')
                ->latest()
                ->limit(50)
                ->get(),
        ];
    }
}
