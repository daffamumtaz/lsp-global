<?php

namespace App\Models;

use App\Models\Concerns\ValidatesProcessRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class ProcessActor extends Model
{
    use HasFactory, ValidatesProcessRecords;

    protected $fillable = ['process_id', 'name', 'label', 'description', 'order', 'is_active'];

    protected $attributes = ['order' => 1, 'is_active' => true];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'actor_id');
    }

    protected function validationRules(): array
    {
        return [...$this->commonRules(), 'process_id' => ['required', 'integer', 'exists:processes,id'], 'name' => ['required', 'string', 'max:80', Rule::unique('process_actors', 'name')->where('process_id', $this->process_id)->ignore($this->id)], 'label' => ['required', 'string', 'max:120']];
    }
}
