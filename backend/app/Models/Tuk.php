<?php

namespace App\Models;

use App\Models\Concerns\ValidatesContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Tuk extends Model
{
    use HasFactory, LogsActivity, ValidatesContent;

    protected $attributes = ['is_active' => true];

    protected function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('tuks', 'code')->ignore($this->id)],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'maps_url' => ['nullable', 'url:https', 'max:255', 'regex:~^https://(?:www\.)?(?:google\.com/maps(?:[/?]|$)|maps\.google\.com/|maps\.app\.goo\.gl/|goo\.gl/maps/)~'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'kind' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected $fillable = ['name', 'kind', 'website_url', 'logo_path', 'code', 'city', 'province', 'address', 'maps_url', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
