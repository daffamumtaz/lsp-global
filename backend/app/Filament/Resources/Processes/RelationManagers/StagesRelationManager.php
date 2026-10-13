<?php

namespace App\Filament\Resources\Processes\RelationManagers;

class StagesRelationManager extends ProcessRelationManager
{
    protected static string $relationship = 'stages';

    protected static string $kind = 'stages';

    protected static ?string $title = 'Tahap';
}
