<?php

namespace App\Filament\Resources\Processes\RelationManagers;

class ConnectionsRelationManager extends ProcessRelationManager
{
    protected static string $relationship = 'connections';

    protected static string $kind = 'connections';

    protected static ?string $title = 'Hubungan Aktivitas';
}
