<?php

namespace App\Models;

use App\Models\Concerns\ValidatesContent;
use Database\Factories\CertificationSchemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CertificationScheme extends Model
{
    /** @use HasFactory<CertificationSchemeFactory> */
    use HasFactory, LogsActivity, ValidatesContent;

    protected $attributes = ['is_active' => true, 'type' => 'Okupasi'];

    protected static function booted(): void
    {
        static::saving(function (self $scheme): void {
            if ($scheme->exists) {
                $scheme->slug = $scheme->getOriginal('slug');

                return;
            }
            $base = Str::slug($scheme->name ?? '');
            if ($base === '') {
                throw ValidationException::withMessages(['name' => 'Nama skema harus menghasilkan alamat URL yang valid. Gunakan huruf atau angka.']);
            }
            $base = rtrim(substr($base, 0, 240), '-');
            $scheme->slug = $base;
            $suffix = 2;
            while (self::query()->where('slug', $scheme->slug)->exists()) {
                $scheme->slug = $base.'-'.$suffix++;
            }
        });
    }

    public static function defaultBasicRequirements(): array
    {
        return [
            'Copy Kartu Pelajar atau Copy Buku Raport terakhir SMK jurusan Rekayasa Perangkat Lunak',
            'Copy sertifikat pelatihan berbasis kompetensi Pemrograman Komputer yang telah diikuti',
            'Surat Pengalaman Kerja pada jabatan Pemrogram Junior (Junior Coder) minimal 1 (satu) tahun secara berkelanjutan',
            'Copy Ijazah terakhir minimal D1 bidang Informatika yang dilegalisir oleh Perguruan Tinggi yang bersangkutan',
        ];
    }

    protected function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('certification_schemes', 'slug')->ignore($this->id)],
            'level' => ['nullable', 'integer', 'between:1,9'],
            'price' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'type' => ['required', 'string', 'max:255'],
            'registration_url' => ['nullable', 'url:https', 'max:255'],
            'methods' => ['nullable', 'array'],
            'methods.*' => ['string', Rule::in(['luring', 'jarak-jauh'])],
            'units' => ['nullable', 'array'],
            'units.*' => ['array:code,title,description'],
            'units.*.code' => ['required', 'string', 'max:255'],
            'units.*.description' => ['nullable', 'string', 'max:10000'],
            'units.*.title' => ['required', 'string', 'max:1000'],
            'basic_requirements' => ['nullable', 'array'],
            'basic_requirements.*' => ['required', 'string', 'max:2000'],
            'administrative_requirements' => ['nullable', 'array'],
            'administrative_requirements.*' => ['required', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected $fillable = ['name', 'category', 'level', 'price', 'is_active', 'slug', 'type', 'thumbnail', 'registration_url', 'methods', 'units', 'basic_requirements', 'administrative_requirements'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_active' => 'boolean', 'methods' => 'array', 'units' => 'array', 'basic_requirements' => 'array', 'administrative_requirements' => 'array'];
    }
}
