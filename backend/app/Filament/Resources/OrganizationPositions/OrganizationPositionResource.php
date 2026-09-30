<?php

namespace App\Filament\Resources\OrganizationPositions;

use App\Filament\Resources\OrganizationPositions\Pages\ManageOrganizationPositions;
use App\Models\OrganizationPosition;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPositionResource extends Resource
{
    protected static ?string $model = OrganizationPosition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Edit Halaman';

    protected static ?string $navigationLabel = 'Struktur Organisasi';

    protected static ?string $modelLabel = 'Jabatan';

    protected static ?string $pluralModelLabel = 'Struktur Organisasi';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Jabatan')->required()->maxLength(120),
            TextInput::make('person_name')->label('Nama Pejabat')->required()->maxLength(160),
            TextInput::make('credentials')->label('Gelar / Credentials')->maxLength(160),
            Select::make('parent_id')->label('Jabatan Atasan')->placeholder('Tanpa atasan (puncak organisasi)')->searchable()
                ->options(function (?OrganizationPosition $record): array {
                    $nodes = OrganizationPosition::hierarchyRows();
                    $excluded = $record ? [$record->id] : [];
                    foreach ($nodes as $node) {
                        if (in_array($node['parent_id'], $excluded, true)) {
                            $excluded[] = $node['id'];
                        }
                    }

                    return collect($nodes)->reject(fn (array $node): bool => in_array($node['id'], $excluded, true))
                        ->mapWithKeys(fn (array $node): array => [$node['id'] => str_repeat('— ', $node['depth']).$node['title'].' · '.$node['person_name']])->all();
                }),
            TextInput::make('order')->label('Urutan di bawah atasan yang sama')->numeric()->integer()->minValue(1)->maxValue(1000000)->default(1)->required()
                ->helperText('Urutan juga dapat diubah dengan tombol Urutkan pada daftar.'),
            Toggle::make('is_active')->label('Aktif')->default(true)->required()->helperText('Menonaktifkan atasan juga menyembunyikan seluruh cabangnya di situs.'),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $rows = OrganizationPosition::hierarchyRows();
        $query = parent::getEloquentQuery()->with('parent');
        if (! $rows) {
            return $query->select('*')->selectRaw('0 as hierarchy_depth');
        }
        $sort = $depth = 'case id ';
        foreach ($rows as $index => $node) {
            $sort .= 'when '.(int) $node['id'].' then '.$index.' ';
            $depth .= 'when '.(int) $node['id'].' then '.(int) $node['depth'].' ';
        }

        return $query->select('*')->selectRaw($depth.'else 0 end as hierarchy_depth')->orderByRaw($sort.'end');
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false)->reorderable('order')->reorderRecordsTriggerAction(fn ($action) => $action->label('Urutkan jabatan'))
            ->columns([
                TextColumn::make('title')->label('Jabatan')->formatStateUsing(fn (string $state, OrganizationPosition $record): string => str_repeat('— ', (int) $record->hierarchy_depth).$state)
                    ->description(fn (OrganizationPosition $record): string => 'Atasan: '.($record->parent?->title ?? 'Tidak ada')),
                TextColumn::make('person_name')->label('Nama Pejabat')->description(fn (OrganizationPosition $record): ?string => $record->credentials),
                TextColumn::make('order')->label('Urutan'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])->recordActions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()->label('Hapus')->before(function (OrganizationPosition $record, DeleteAction $action): void {
                    if ($record->children()->exists()) {
                        Notification::make()->warning()->title('Pindahkan atau hapus bawahan terlebih dahulu.')->send();
                        $action->halt();
                    }
                }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrganizationPositions::route('/')];
    }
}
