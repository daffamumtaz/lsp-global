<?php

namespace App\Models;

use App\Models\Concerns\ValidatesContent;
use Database\Factories\SchemeCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SchemeCategory extends Model
{
    /** @use HasFactory<SchemeCategoryFactory> */
    use HasFactory, LogsActivity, ValidatesContent;

    protected $fillable = ['name'];

    protected function validationRules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique('scheme_categories', 'name')->ignore($this->id)]];
    }

    public function schemes(): HasMany
    {
        return $this->hasMany(CertificationScheme::class, 'category', 'name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
