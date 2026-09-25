<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Information extends Page
{
    protected string $view = 'filament.pages.development';

    protected static ?string $title = 'Informasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 3;
}
