<?php

namespace App\Filament\Resources\CertificationSchemes;

use App\Models\SchemeCategory;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CategoryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function mount(): void
    {
        Gate::authorize('manage-admin');
    }

    public function table(Table $table): Table
    {
        $fields = fn (): array => [TextInput::make('name')->label('Nama Kategori')->required()->maxLength(255)
            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
            ->dehydrateStateUsing(fn (string $state): string => trim($state))
            ->unique(table: SchemeCategory::class, column: 'name', ignoreRecord: true)];

        return $table
            ->query(SchemeCategory::query()->withCount('schemes'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Kategori')->searchable()->wrap(),
                TextColumn::make('schemes_count')->label('Jumlah Skema'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah Kategori')->schema($fields)->authorize('manage-admin')])
            ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action->iconButton())
            ->recordActions([
                EditAction::make()->label('Edit')->iconButton()->schema($fields)->authorize('manage-admin')
                    ->modalDescription('Perubahan nama berlaku untuk semua skema yang memakai kategori ini.'),
                DeleteAction::make()->label('Hapus')->iconButton()->authorize('manage-admin')
                    ->modalDescription(fn (SchemeCategory $record): string => "Kategori akan dihapus dan kategori pada {$record->schemes_count} skema akan dikosongkan. Skema tetap tersimpan."),
            ])
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Tambahkan kategori untuk digunakan pada skema sertifikasi.');
    }

    public function render(): View
    {
        Gate::authorize('manage-admin');

        return view('filament.resources.certification-schemes.category-table');
    }
}
