<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Profile extends Page
{
    protected string $view = 'filament.pages.development';

    protected static ?string $title = 'Profil LSP';

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 2;
}
