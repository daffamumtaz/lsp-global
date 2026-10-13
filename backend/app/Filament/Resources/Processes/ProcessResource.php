<?php

namespace App\Filament\Resources\Processes;

use App\Filament\Resources\Processes\Pages\CreateProcess;
use App\Filament\Resources\Processes\Pages\EditProcess;
use App\Filament\Resources\Processes\Pages\ListProcesses;
use App\Filament\Resources\Processes\Schemas\ProcessForm;
use App\Filament\Resources\Processes\Tables\ProcessesTable;
use App\Models\Process;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProcessResource extends Resource
{
    protected static ?string $model = Process::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Alur Asesmen';

    protected static ?string $modelLabel = 'Alur Asesmen';

    protected static ?string $pluralModelLabel = 'Alur Asesmen';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationUrl(): string
    {
        $process = Process::query()->where('slug', 'asesmen-sertifikasi-jarak-jauh')->first() ?? Process::query()->first();

        return $process ? static::getUrl('edit', ['record' => $process]) : static::getUrl();
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProcessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcessesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcesses::route('/'),
            'create' => CreateProcess::route('/create'),
            'edit' => EditProcess::route('/{record}/edit'),
        ];
    }
}
