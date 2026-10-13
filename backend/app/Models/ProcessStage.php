<?php

namespace App\Models;

use App\Models\Concerns\ValidatesProcessRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStage extends Model
{
    use HasFactory, ValidatesProcessRecords;

    protected $fillable = ['process_id', 'title', 'description', 'order', 'is_active'];

    protected $attributes = ['order' => 1, 'is_active' => true];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'stage_id')->orderBy('order')->orderBy('id');
    }

    protected function validationRules(): array
    {
        return [...$this->commonRules(), 'process_id' => ['required', 'integer', 'exists:processes,id'], 'title' => ['required', 'string', 'max:255']];
    }
}
