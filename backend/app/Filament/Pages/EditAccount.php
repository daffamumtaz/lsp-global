<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Panel;

class EditAccount extends EditProfile
{
    public static function getSlug(?Panel $panel = null): string
    {
        return 'account';
    }
}
