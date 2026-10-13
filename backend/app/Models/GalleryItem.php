<?php

namespace App\Models;

use App\Models\Concerns\ValidatesContent;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

class GalleryItem extends Model implements HasMedia
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, ValidatesContent;

    protected function validationRules(): array
    {
        return ['media_type' => ['nullable', Rule::in(['photo', 'video'])], 'title' => ['required', 'string', 'max:255'], 'year' => ['required', 'integer', 'between:1900,2100'], 'location' => ['nullable', 'string', 'max:255'], 'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:2100-12-31'], 'description' => ['nullable', 'string', 'max:10000']];
    }

    protected $fillable = ['title', 'year', 'description', 'location', 'date', 'media_type'];

    protected static function booted(): void
    {
        static::updating(function (self $item): void {
            if ($item->getOriginal('media_type') !== null && $item->isDirty('media_type')) {
                throw ValidationException::withMessages(['media_type' => 'Jenis album tidak dapat diubah. Buat album baru untuk jenis media lain.']);
            }
        });
    }

    public static function acceptedMimeTypes(?string $type): array
    {
        return match ($type) {
            'photo' => ['image/jpeg', 'image/png', 'image/webp'],
            'video' => ['video/mp4', 'video/webm'],
            default => [],
        };
    }

    public function setDateAttribute(?string $value): void
    {
        $this->attributes['date'] = $value;
        if ($value) {
            $this->attributes['year'] = substr($value, 0, 4);
        }
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')->useDisk('public')
            ->acceptsMimeTypes(self::acceptedMimeTypes($this->media_type))
            ->acceptsFile(fn (File $file): bool => in_array($file->mimeType, self::acceptedMimeTypes($this->media_type), true) && $file->size <= 104857600);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
