<?php

namespace App\Filament\Resources\Processes\RelationManagers;

use App\Enums\ProcessConnectionType;
use App\Enums\ProcessStepType;
use App\Filament\AdminPreview;
use App\Filament\Concerns\HasAdminPreview;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

abstract class ProcessRelationManager extends RelationManager
{
    use HasAdminPreview;

    protected function previewKind(): string
    {
        return 'process';
    }

    public function previewData(?array $draft = null, ?int $id = null): array
    {
        return AdminPreview::process($this->getOwnerRecord());
    }

    public function updatePreview(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        try {
            $this->getMountedActionSchema()->validate();
            $draft = (array) $this->getMountedActionSchema()->getRawState();
            $id = $this->getMountedAction()?->getRecord()?->getKey();
            AdminPreview::process($this->getOwnerRecord(), [], static::$kind, $id, $draft);
            $this->previewError = '';
            $this->dispatch('process-preview-draft', processId: $this->getOwnerRecord()->id, componentId: $this->getId(), kind: static::$kind, id: $id, draft: $draft);
        } catch (ValidationException $exception) {
            $this->previewError = implode(' ', array_merge(...array_values($exception->errors())));
        }
    }

    public function restorePreview(): void
    {
        $this->previewError = '';
        $this->dispatch('process-preview-draft', processId: $this->getOwnerRecord()->id, componentId: $this->getId());
    }

    protected static string $kind;

    public function isReadOnly(): bool
    {
        return false;
    }

    private function stages(): array
    {
        return $this->getOwnerRecord()->stages()->pluck('title', 'id')->all();
    }

    private function actors(): array
    {
        return $this->getOwnerRecord()->actors()->pluck('label', 'id')->all();
    }

    private function steps(): array
    {
        return $this->getOwnerRecord()->steps()->with(['stage', 'actor'])->get()
            ->mapWithKeys(fn (ProcessStep $step): array => [
                $step->id => $step->stage->title.' · '.($step->actor?->label ?? 'Tanpa pelaksana').' · '.$step->title,
            ])->all();
    }

    public function form(Schema $schema): Schema
    {
        if (static::$kind === 'steps') {
            $fields = [
                Select::make('stage_id')->label('Tahap')->options(fn (): array => $this->stages())->required()->searchable(),
                Select::make('actor_id')->label('Pelaksana')->options(fn (): array => $this->actors())->searchable()->placeholder('Tanpa pelaksana'),
                TextInput::make('title')->label('Judul Aktivitas')->required()->maxLength(255),
                Select::make('type')->label('Jenis Aktivitas')->options(ProcessStepType::options())->default('normal')->required(),
                Textarea::make('description')->label('Deskripsi')->maxLength(10000),
                TextInput::make('document_reference')->label('Dokumen Terkait')->maxLength(255),
                TextInput::make('information_url')->label('Link Informasi')->url()->rules(['url:https'])->maxLength(2048),
            ];
        } elseif (static::$kind === 'connections') {
            $fields = [
                Select::make('source_step_id')->label('Dari')->options(fn (): array => $this->steps())->required()->searchable(),
                Select::make('target_step_id')->label('Aktivitas Berikutnya')->options(fn (): array => $this->steps())->required()->searchable()->different('source_step_id'),
                TextInput::make('label')->label('Label Percabangan')->maxLength(120),
                Select::make('type')->label('Tipe Hubungan')->options(ProcessConnectionType::options())->default('normal')->required(),
            ];
        } else {
            $fields = static::$kind === 'actors' ? [
                TextInput::make('name')->label('Nama Singkat')->required()->maxLength(80),
                TextInput::make('label')->label('Nama Pelaksana')->required()->maxLength(120),
            ] : [
                TextInput::make('title')->label('Judul Tahap')->required()->maxLength(255),
            ];
            $fields[] = static::$kind === 'stages'
                ? MarkdownEditor::make('description')->label('Detail Tahap')->maxLength(10000)->fileAttachments(false)
                    ->toolbarButtons([['bold', 'italic'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->helperText('Tulis seluruh narasi tahap di sini, termasuk persiapan dan hasil asesmen. Gunakan paragraf atau poin-poin.')
                    ->columnSpanFull()
                : Textarea::make('description')->label('Deskripsi')->maxLength(10000);
        }
        $fields[] = TextInput::make('order')->label('Urutan')->numeric()->integer()->minValue(1)->maxValue(1000000)->default(1)->required();
        if (static::$kind !== 'connections') {
            $fields[] = Toggle::make('is_active')->label('Aktif')->default(true)->required();
        }

        return $schema->components($fields);
    }

    private function scopeData(array $data): array
    {
        // ID dari form tidak dipercaya; relasi harus tetap berada dalam alur yang sedang diedit.
        if (static::$kind === 'steps') {
            if (! $this->getOwnerRecord()->stages()->whereKey($data['stage_id'])->exists()) {
                throw ValidationException::withMessages(['stage_id' => 'Pilih tahap dari alur ini.']);
            }
        } else {
            $data['process_id'] = $this->getOwnerRecord()->id;
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        $titleColumn = match (static::$kind) {
            'actors' => 'label',
            'connections' => 'sourceStep.title',
            default => 'title',
        };
        $columns = [TextColumn::make($titleColumn)->label(static::$kind === 'connections' ? 'Dari' : 'Judul')->wrap()];
        if (static::$kind === 'steps') {
            $columns[] = TextColumn::make('stage.title')->label('Tahap');
            $columns[] = TextColumn::make('actor.label')->label('Pelaksana')->placeholder('Tanpa pelaksana');
            $columns[] = TextColumn::make('type')->label('Jenis')->formatStateUsing(fn (string $state): string => ProcessStepType::options()[$state]);
        }
        if (static::$kind === 'connections') {
            $columns[] = TextColumn::make('targetStep.title')->label('Aktivitas Berikutnya')->wrap();
            $columns[] = TextColumn::make('label')->label('Label');
            $columns[] = TextColumn::make('type')->label('Tipe')->formatStateUsing(fn (string $state): string => ProcessConnectionType::options()[$state]);
        }
        $columns[] = TextColumn::make('order')->label('Urutan');
        if (static::$kind !== 'connections') {
            $columns[] = IconColumn::make('is_active')->label('Aktif')->boolean();
        }
        $table->modelLabel(static::$title)->pluralModelLabel(static::$title)
            ->paginated(false)->defaultSort('order')->columns($columns)
            ->headerActions([
                CreateAction::make()->label('Tambah '.static::$title)->modalContentFooter(fn () => $this->previewView())->mutateFormDataUsing(fn (array $data): array => $this->scopeData($data)),
            ])->recordAction('edit')->recordActions([
                EditAction::make()->label('Edit')->modalContentFooter(fn () => $this->previewView())->mutateFormDataUsing(fn (array $data): array => $this->scopeData($data)),
                DeleteAction::make()->label('Hapus')->modalDescription(function (Model $record): string {
                    if (static::$kind === 'stages') {
                        return 'Menghapus tahap ini juga menghapus '.$record->steps()->count().' aktivitas dan hubungan terkait.';
                    }
                    if (static::$kind === 'actors') {
                        return 'Pelaksana akan dikosongkan pada '.$record->steps()->count().' aktivitas terkait.';
                    }

                    return 'Hapus data ini beserta hubungan aktivitas yang terkait?';
                }),
            ])->reorderable('order', fn (): bool => static::$kind !== 'steps' || (
                ($this->getTableFilterState('stage')['value'] ?? null) !== null &&
                ($this->getTableFilterState('actor')['value'] ?? null) !== null
            ));
        if (static::$kind === 'steps') {
            $table->modifyQueryUsing(fn ($query) => $query->with(['stage', 'actor']))
                ->defaultGroup(Group::make('stage_id')->label('Tahap / Pelaksana')
                    ->getKeyFromRecordUsing(fn (ProcessStep $step): string => $step->stage_id.'-'.($step->actor_id ?? 0))
                    ->getTitleFromRecordUsing(fn (ProcessStep $step): string => $step->stage->title.' · '.($step->actor?->label ?? 'Tanpa pelaksana')))
                ->filters([
                    SelectFilter::make('stage')->label('Tahap untuk urutkan')->options(fn (): array => $this->stages())
                        ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($query, $value) => $query->where('stage_id', $value))),
                    SelectFilter::make('actor')->label('Pelaksana untuk urutkan')->options(fn (): array => [0 => 'Tanpa pelaksana'] + $this->actors())
                        ->query(fn ($query, array $data) => isset($data['value']) ? $query->where('actor_id', $data['value'] ? (int) $data['value'] : null) : $query),
                ]);
        }

        return $table;
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $models = ['stages' => ProcessStage::class, 'actors' => ProcessActor::class, 'steps' => ProcessStep::class, 'connections' => ProcessConnection::class];
        $scope = ['process_id' => $this->getOwnerRecord()->id];
        if (static::$kind === 'steps') {
            $stage = $this->getTableFilterState('stage')['value'] ?? null;
            $actor = $this->getTableFilterState('actor')['value'] ?? null;
            if (! $stage || $actor === null || ! $this->getOwnerRecord()->stages()->whereKey($stage)->exists()) {
                throw ValidationException::withMessages(['order' => 'Pilih satu tahap dan pelaksana sebelum mengurutkan aktivitas.']);
            }
            $scope = ['stage_id' => (int) $stage, 'actor_id' => $actor ? (int) $actor : null];
        }
        $models[static::$kind]::reorderGroup($scope, $order);
        $this->resetTable();
        $this->restorePreview();
    }
}
