<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected static bool $isLazy = false;

    public static int $gridW = 12;

    public static int $gridMinW = 6;

    public static int $gridH = 4;

    public static int $gridMinH = 4;

    public static bool $gridSizeToContent = false;

    public function getTitle(): string
    {
        return 'Aksi cepat';
    }

    protected string $view = 'filament.widgets.quick-actions';
}
