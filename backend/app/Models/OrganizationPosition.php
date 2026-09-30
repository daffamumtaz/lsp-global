<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class OrganizationPosition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['parent_id', 'title', 'person_name', 'credentials', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['parent_id' => 'integer', 'order' => 'integer', 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order')->orderBy('id');
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    public function delete(): ?bool
    {
        return DB::transaction(fn (): ?bool => parent::delete());
    }

    protected static function booted(): void
    {
        static::saving(function (self $position): void {
            Validator::make($position->getAttributes(), [
                'title' => ['required', 'string', 'max:120'],
                'person_name' => ['required', 'string', 'max:160'],
                'credentials' => ['nullable', 'string', 'max:160'],
                'parent_id' => ['nullable', 'integer'],
                'order' => ['required', 'integer', 'min:1', 'max:1000000'],
                'is_active' => ['required', 'boolean'],
            ])->validate();
            $parents = self::query()->orderBy('id')->lockForUpdate()->pluck('parent_id', 'id')->all();
            $seen = [$position->id];
            $parent = $position->parent_id;
            while ($parent !== null) {
                if (! array_key_exists($parent, $parents) || in_array($parent, $seen, true)) {
                    throw ValidationException::withMessages(['parent_id' => 'Jabatan atasan tidak valid atau membentuk hubungan melingkar.']);
                }
                $seen[] = $parent;
                $parent = $parents[$parent];
            }
        });
        static::deleting(function (self $position): void {
            self::query()->orderBy('id')->lockForUpdate()->get();
            if ($position->children()->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'Pindahkan atau hapus bawahan terlebih dahulu sebelum menghapus jabatan ini.']);
            }
        });
    }

    /** @return array<int, array<string, mixed>> */
    public static function hierarchyRows(bool $activeOnly = false): array
    {
        $groups = self::query()->orderBy('order')->orderBy('id')->get()->groupBy(fn (self $node): string => (string) ($node->parent_id ?? 'root'));
        $rows = [];
        $visit = function (string $parent, int $depth) use (&$visit, &$rows, $groups, $activeOnly): void {
            foreach ($groups->get($parent, []) as $node) {
                if ($activeOnly && ! $node->is_active) {
                    continue;
                }
                $rows[] = [...$node->only(['id', 'parent_id', 'title', 'person_name', 'credentials', 'order', 'is_active']), 'depth' => $depth];
                $visit((string) $node->id, $depth + 1);
            }
        };
        $visit('root', 0);

        return $rows;
    }

    /** @param array<int, int|string> $ids */
    public static function reorderSiblings(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $nodes = self::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('intval', $ids);
            if (count(array_unique($ids)) !== count($ids) || count($nodes->only($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['order' => 'Urutan jabatan tidak valid.']);
            }
            $groups = collect($ids)->groupBy(fn (int $id): string => (string) ($nodes[$id]->parent_id ?? 'root'));
            foreach ($groups as $parent => $siblings) {
                $expected = $nodes->filter(fn (self $node): bool => (string) ($node->parent_id ?? 'root') === (string) $parent)->count();
                if ($siblings->count() !== $expected) {
                    throw ValidationException::withMessages(['order' => 'Urutkan seluruh jabatan di bawah atasan yang sama.']);
                }
                foreach ($siblings->values() as $index => $id) {
                    $nodes[$id]->update(['order' => $index + 1]);
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
