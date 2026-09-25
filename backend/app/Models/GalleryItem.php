<?php

namespace App\Models;

use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GalleryItem extends Model implements HasMedia
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = ['title', 'year', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
