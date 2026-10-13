<?php

namespace App\Filament\Resources\Processes\RelationManagers;

class ActorsRelationManager extends ProcessRelationManager
{
    protected static string $relationship = 'actors';

    protected static string $kind = 'actors';

    protected static ?string $title = 'Pelaksana';
}
