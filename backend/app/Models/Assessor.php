<?php

namespace App\Models;

use App\Models\Concerns\ValidatesContent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Assessor extends Model
{
    use HasFactory, LogsActivity, ValidatesContent;

    protected $attributes = ['is_active' => true];

    public const EXPIRY_STATUSES = ['Berlaku' => 'success', 'Hampir habis' => 'warning', 'Masa tenggang' => 'warning', 'Kedaluwarsa' => 'danger', 'Belum diketahui' => 'gray'];

    protected static function booted(): void
    {
        static::saving(function (self $assessor): void {
            if (! $assessor->exists && ! array_key_exists('expires_on', $assessor->getAttributes())) {
                $assessor->expires_on = CarbonImmutable::today('Asia/Jakarta')->addYearsNoOverflow(3);
            }
        });
    }

    public function graceEndsOn(): ?CarbonImmutable
    {
        return $this->expires_on !== null ? CarbonImmutable::parse($this->expires_on->toDateString(), 'Asia/Jakarta')->addMonthsNoOverflow(3) : null;
    }

    public function expiryStatus(): string
    {
        if ($this->expires_on === null) {
            return 'Belum diketahui';
        }
        $today = CarbonImmutable::today('Asia/Jakarta');
        $expiry = CarbonImmutable::parse($this->expires_on->toDateString(), 'Asia/Jakarta');
        if ($expiry->greaterThan($today->addDays(90))) {
            return 'Berlaku';
        }
        if ($expiry->greaterThanOrEqualTo($today)) {
            return 'Hampir habis';
        }

        return $this->graceEndsOn()->greaterThanOrEqualTo($today) ? 'Masa tenggang' : 'Kedaluwarsa';
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereDate('expires_on', '<=', CarbonImmutable::today('Asia/Jakarta')->addDays(90));
    }

    public function scopeWithExpiryStatus(Builder $query, string $status): Builder
    {
        $today = CarbonImmutable::today('Asia/Jakarta');
        $graceStart = $today->subMonthsNoOverflow(3);
        // Hari yang tidak ada pada bulan asal memakai awal bulan berikutnya.
        if ($graceStart->addMonthsNoOverflow(3)->lessThan($today)) {
            $graceStart = $graceStart->addMonthNoOverflow()->startOfMonth();
        }

        return match ($status) {
            'Belum diketahui' => $query->whereNull('expires_on'),
            'Berlaku' => $query->whereDate('expires_on', '>', $today->addDays(90)),
            'Hampir habis' => $query->whereDate('expires_on', '>=', $today)->whereDate('expires_on', '<=', $today->addDays(90)),
            'Masa tenggang' => $query->whereDate('expires_on', '<', $today)->whereDate('expires_on', '>=', $graceStart),
            'Kedaluwarsa' => $query->whereDate('expires_on', '<', $graceStart),
            default => $query,
        };
    }

    protected function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'certificate' => ['nullable', 'string', 'max:255', Rule::unique('assessors', 'certificate')->ignore($this->id)],
            'field' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'expires_on' => ['nullable', 'date'],
            'import_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected $fillable = ['name', 'certificate', 'field', 'is_active', 'expires_on', 'import_notes'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'expires_on' => 'immutable_date'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
