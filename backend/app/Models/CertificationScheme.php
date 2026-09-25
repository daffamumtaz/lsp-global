<?php

namespace App\Models;

use Database\Factories\CertificationSchemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CertificationScheme extends Model
{
    /** @use HasFactory<CertificationSchemeFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'category', 'level', 'price', 'is_active'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_active' => 'boolean'];
    }
}
