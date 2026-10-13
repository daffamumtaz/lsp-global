<?php

namespace App\Filament\Resources\OrganizationPositions;

use App\Filament\Resources\OrganizationPositions\Pages\ManageOrganizationPositions;
use App\Models\OrganizationPosition;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Konten Website';

    protected static ?string $navigationLabel = 'Struktur Organisasi';

    protected static ?string $modelLabel = 'Jabatan';

    protected static ?string $pluralModelLabel = 'Struktur Organisasi';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Jabatan')->required()->maxLength(120),
            Repeater::make('members')->label('Pejabat / Anggota')->schema([
                TextInput::make('person_name')->label('Nama')->required()->maxLength(160),
                TextInput::make('credentials')->label('Gelar')->maxLength(160),
            ])->columns(2)->minItems(1)->maxItems(100)->defaultItems(1)->required()->addActionLabel('Tambah nama')->reorderableWithButtons()->columnSpanFull(),
            TextInput::make('layout_row')->label('Baris bagan')->numeric()->integer()->minValue(1)->maxValue(1000)->requiredWith('layout_column')
                ->helperText('Baris 1 paling atas. Kosongkan baris dan posisi untuk susunan otomatis.'),
            Select::make('layout_column')->label('Posisi horizontal')->placeholder('Otomatis')->options([
                1 => '1 · Paling kiri', 2 => '2 · Kiri', 3 => '3 · Kiri dalam', 4 => '4 · Dekat tengah kiri',
                5 => '5 · Tengah', 6 => '6 · Dekat tengah kanan', 7 => '7 · Kanan dalam', 8 => '8 · Kanan', 9 => '9 · Paling kanan',
            ])->requiredWith('layout_row'),
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
                TextColumn::make('members')->label('Pejabat / Anggota')->state(fn (OrganizationPosition $record): array => array_map(
                    fn (array $member): string => $member['person_name'].(! empty($member['credentials']) ? ', '.$member['credentials'] : ''), $record->members,
                ))->listWithLineBreaks(),
                TextColumn::make('layout_row')->label('Letak bagan')->formatStateUsing(fn ($state, OrganizationPosition $record): string => 'Baris '.$state.' · Posisi '.$record->layout_column)->placeholder('Otomatis'),
                TextColumn::make('order')->label('Urutan'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])->recordAction('edit')->recordActions([
                EditAction::make()->label('Edit')->modalContentFooter(fn ($livewire) => $livewire->previewView()),
                Action::make('merge')->label('Gabungkan jabatan')->modalHeading('Gabungkan nama ke kotak ini')
                    ->modalDescription('Nama dipindahkan ke jabatan ini. Kotak jabatan yang dipilih akan dihapus.')->modalSubmitActionLabel('Gabungkan')
                    ->visible(fn (OrganizationPosition $record): bool => ! $record->children()->exists())
                    ->schema([
                        Select::make('position_ids')->label('Jabatan dengan judul dan atasan yang sama')->multiple()->required()->minItems(1)
                            ->options(fn (OrganizationPosition $record): array => OrganizationPosition::query()->where('title', $record->title)
                                ->where('parent_id', $record->parent_id)->where('is_active', $record->is_active)->whereKeyNot($record->id)->doesntHave('children')
                                ->orderBy('order')->get()->mapWithKeys(fn (OrganizationPosition $node): array => [$node->id => implode(', ', array_column($node->members, 'person_name'))])->all()),
                    ])->action(function (array $data, OrganizationPosition $record): void {
                        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
                        $record->mergePositions($data['position_ids']);
                        Notification::make()->success()->title('Nama digabungkan ke satu kotak jabatan.')->send();
                    }),
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
