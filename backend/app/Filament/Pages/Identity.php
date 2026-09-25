<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Identity extends Page
{
    protected string $view = 'filament.pages.development';

    protected static ?string $title = 'Identitas';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;
}
