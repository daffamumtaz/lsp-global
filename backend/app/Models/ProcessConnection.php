<?php

namespace App\Models;

use App\Enums\ProcessConnectionType;
use App\Models\Concerns\ValidatesProcessRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProcessConnection extends Model
{
    use HasFactory, ValidatesProcessRecords;

    protected $fillable = ['process_id', 'source_step_id', 'target_step_id', 'label', 'type', 'order'];

    protected $attributes = ['order' => 1, 'label' => '', 'type' => 'normal'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function sourceStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'source_step_id');
    }

    public function targetStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'target_step_id');
    }

    protected function validationRules(): array
    {
        $this->label = trim($this->label ?? '');
        $this->type = $this->type ?: 'normal';

        return ['process_id' => ['required', 'integer', 'exists:processes,id'], 'source_step_id' => ['required', 'integer', 'exists:process_steps,id'], 'target_step_id' => ['required', 'integer', 'exists:process_steps,id', 'different:source_step_id'], 'label' => ['nullable', 'string', 'max:120'], 'type' => ['required', Rule::in(array_keys(ProcessConnectionType::options()))], 'order' => ['required', 'integer', 'min:1', 'max:1000000']];
    }

    protected function validateRelationships(): void
    {
        if ($this->exists && $this->isDirty('process_id')) {
            throw ValidationException::withMessages(['process_id' => 'Hubungan tidak dapat dipindahkan ke alur lain.']);
        }
        foreach (['source_step_id', 'target_step_id'] as $key) {
            if (ProcessStep::query()->findOrFail($this->$key)->stage->process_id !== $this->process_id) {
                throw ValidationException::withMessages([$key => 'Aktivitas harus berasal dari alur yang sama.']);
            }
        }
        $query = self::query()->where($this->only(['process_id', 'source_step_id', 'target_step_id', 'label', 'type']));
        if ($this->exists) {
            $query->whereKeyNot($this->id);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['target_step_id' => 'Hubungan dengan label dan tipe yang sama sudah tersedia.']);
        }
    }
}
