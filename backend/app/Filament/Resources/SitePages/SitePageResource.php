<?php

namespace App\Filament\Resources\SitePages;

use App\Filament\Resources\SitePages\Pages\ListSitePageActivities;
use App\Filament\Resources\SitePages\Pages\ManageSitePages;
use App\Models\SitePage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class SitePageResource extends Resource
{
    protected static ?string $model = SitePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Landing Page';

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof SitePage && $record->kind === 'landing';
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSitePages::route('/'),
            'activities' => ListSitePageActivities::route('/{record}/activities'),
        ];
    }
}
