<?php

namespace App\Models;

use App\Models\Concerns\ValidatesProcessRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\Rule;

class Process extends Model
{
    use HasFactory, ValidatesProcessRecords;

    protected $fillable = ['title', 'slug', 'description', 'order', 'is_active'];

    protected $attributes = ['order' => 1, 'is_active' => true];

    public function stages(): HasMany
    {
        return $this->hasMany(ProcessStage::class)->orderBy('order')->orderBy('id');
    }

    public function actors(): HasMany
    {
        return $this->hasMany(ProcessActor::class)->orderBy('order')->orderBy('id');
    }

    public function connections(): HasMany
    {
        return $this->hasMany(ProcessConnection::class)->orderBy('order')->orderBy('id');
    }

    public function steps(): HasManyThrough
    {
        return $this->hasManyThrough(ProcessStep::class, ProcessStage::class, 'process_id', 'stage_id');
    }

    protected function validationRules(): array
    {
        return [...$this->commonRules(), 'title' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('processes', 'slug')->ignore($this->id)]];
    }
}
