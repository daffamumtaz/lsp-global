<?php

namespace App\Filament\Resources\Processes\RelationManagers;

class StepsRelationManager extends ProcessRelationManager
{
    protected static string $relationship = 'steps';

    protected static string $kind = 'steps';

    protected static ?string $title = 'Aktivitas';
}
