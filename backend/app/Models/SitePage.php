<?php

namespace App\Models;

use Database\Factories\SitePageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SitePage extends Model
{
    /** @use HasFactory<SitePageFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['kind', 'title', 'summary', 'body'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
