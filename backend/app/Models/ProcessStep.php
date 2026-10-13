<?php

namespace App\Models;

use App\Enums\ProcessStepType;
use App\Models\Concerns\ValidatesProcessRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProcessStep extends Model
{
    use HasFactory, ValidatesProcessRecords;

    protected $fillable = ['stage_id', 'actor_id', 'title', 'description', 'type', 'order', 'is_active', 'document_reference', 'information_url'];

    protected $attributes = ['order' => 1, 'is_active' => true, 'type' => 'normal'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProcessStage::class, 'stage_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(ProcessActor::class, 'actor_id');
    }

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(ProcessConnection::class, 'target_step_id');
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(ProcessConnection::class, 'source_step_id');
    }

    protected function validationRules(): array
    {
        return [...$this->commonRules(), 'title' => ['required', 'string', 'max:255'], 'stage_id' => ['required', 'integer', 'exists:process_stages,id'], 'actor_id' => ['nullable', 'integer', 'exists:process_actors,id'], 'type' => ['required', Rule::in(array_keys(ProcessStepType::options()))], 'document_reference' => ['nullable', 'string', 'max:255'], 'information_url' => ['nullable', 'url:https', 'max:2048']];
    }

    protected function validateRelationships(): void
    {
        $processId = ProcessStage::query()->findOrFail($this->stage_id)->process_id;
        if ($this->actor_id && ProcessActor::query()->findOrFail($this->actor_id)->process_id !== $processId) {
            throw ValidationException::withMessages(['actor_id' => 'Pelaksana harus berasal dari alur yang sama.']);
        }
        if ($this->exists && $this->isDirty('stage_id') && ProcessStage::query()->findOrFail($this->getOriginal('stage_id'))->process_id !== $processId) {
            throw ValidationException::withMessages(['stage_id' => 'Tahap harus berasal dari alur yang sama.']);
        }
        if ($this->information_url && (parse_url($this->information_url, PHP_URL_USER) || parse_url($this->information_url, PHP_URL_PASS))) {
            throw ValidationException::withMessages(['information_url' => 'Link informasi tidak boleh memuat kredensial.']);
        }
    }
}
