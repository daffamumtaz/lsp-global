<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class History extends Page
{
    protected string $view = 'filament.pages.development';

    protected static ?string $title = 'Histori Perubahan';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;
}
