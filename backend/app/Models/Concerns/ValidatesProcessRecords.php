<?php

namespace App\Models\Concerns;

use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait ValidatesProcessRecords
{
    use LogsActivity;

    protected array $cascadeSnapshot = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        if ($event === 'deleted' && $this->cascadeSnapshot !== []) {
            $activity->properties = $activity->properties->put('cascade', $this->cascadeSnapshot);
        }
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options): bool {
            $this->lockProcess();

            return parent::save($options);
        });
    }

    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->lockProcess();

            return parent::delete();
        });
    }

    /** Lock induk menyelaraskan perubahan tahap, pelaksana, aktivitas, dan koneksi dalam satu alur. */
    private function lockProcess(): void
    {
        $id = $this instanceof Process ? $this->id : ($this->process_id ?? $this->stage?->process_id);
        if ($id) {
            Process::query()->whereKey($id)->lockForUpdate()->first();
        }
    }

    protected static function bootValidatesProcessRecords(): void
    {
        // Cascade database tidak memicu event model anak; simpan rinciannya untuk riwayat penghapusan.
        static::deleting(function (self $record): void {
            if ($record instanceof ProcessActor) {
                $record->cascadeSnapshot = ['unassigned_step_ids' => ProcessStep::query()->where('actor_id', $record->id)->pluck('id')->all()];

                return;
            }
            $steps = match (true) {
                $record instanceof Process => $record->steps()->get(),
                $record instanceof ProcessStage => $record->steps()->get(),
                $record instanceof ProcessStep => collect([$record]),
                default => collect(),
            };
            $ids = $steps->pluck('id')->all();
            $connections = ProcessConnection::query()->where(fn ($query) => $query->whereIn('source_step_id', $ids)->orWhereIn('target_step_id', $ids))->pluck('id')->all();
            $record->cascadeSnapshot = ['step_ids' => $ids, 'steps' => $steps->map(fn ($step): array => $step->only(['id', 'title', 'actor_id']))->all(), 'connection_ids' => $connections];
        });
        static::saving(function (self $record): void {
            Validator::make($record->getAttributes(), $record->validationRules())->validate();
            $record->validateRelationships();
        });
    }

    protected function validateRelationships(): void
    {
        if ($this->exists && $this->isDirty('process_id')) {
            throw ValidationException::withMessages(['process_id' => 'Data tidak dapat dipindahkan ke alur lain.']);
        }
    }

    protected function casts(): array
    {
        return ['order' => 'integer', 'is_active' => 'boolean', 'process_id' => 'integer', 'stage_id' => 'integer', 'actor_id' => 'integer', 'source_step_id' => 'integer', 'target_step_id' => 'integer'];
    }

    protected function commonRules(): array
    {
        return ['order' => ['required', 'integer', 'min:1', 'max:1000000'], 'description' => ['nullable', 'string', 'max:10000'], 'is_active' => ['required', 'boolean']];
    }

    /** Semua ID dalam kelompok wajib dikirim agar urutan tidak bertabrakan dengan data yang terlewat. */
    public static function reorderGroup(array $scope, array $ids): void
    {
        DB::transaction(function () use ($scope, $ids): void {
            $parents = Process::query();
            if (is_a(static::class, Process::class, true)) {
                $parents->where($scope);
            } elseif (is_a(static::class, ProcessStep::class, true)) {
                $stages = ProcessStage::query();
                if (isset($scope['stage_id'])) {
                    $stages->whereKey($scope['stage_id']);
                } else {
                    $stages->whereIn('id', static::query()->where($scope)->select('stage_id'));
                }
                $parents->whereIn('id', $stages->select('process_id'));
            } elseif (isset($scope['process_id'])) {
                $parents->whereKey($scope['process_id']);
            } else {
                $parents->whereIn('id', static::query()->where($scope)->select('process_id'));
            }
            $parents->orderBy('id')->lockForUpdate()->get();

            $records = static::query()->where($scope)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('intval', $ids);
            $expected = $records->keys()->sort()->values()->all();
            $actual = collect($ids)->sort()->values()->all();
            if ($actual !== $expected) {
                throw ValidationException::withMessages(['order' => 'Urutkan seluruh data dalam kelompok yang sama.']);
            }
            foreach ($ids as $index => $id) {
                $records[$id]->update(['order' => $index + 1]);
            }
        });
    }
}
