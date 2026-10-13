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

    protected $fillable = ['parent_id', 'title', 'person_name', 'credentials', 'members', 'layout_row', 'layout_column', 'order', 'is_active'];

    protected function casts(): array
    {
        return ['parent_id' => 'integer', 'order' => 'integer', 'is_active' => 'boolean', 'members' => 'array', 'layout_row' => 'integer', 'layout_column' => 'integer'];
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
            $members = $position->members;
            if ($members === null) {
                $members = [['person_name' => $position->person_name, 'credentials' => $position->credentials]];
            } elseif (! $position->isDirty('members') && ($position->isDirty('person_name') || $position->isDirty('credentials'))) {
                $members[0] = ['person_name' => $position->person_name, 'credentials' => $position->credentials];
            }
            Validator::make([...$position->getAttributes(), 'members' => $members], [
                'title' => ['required', 'string', 'max:120'],
                'members' => ['required', 'array', 'list', 'min:1', 'max:100'],
                'members.*' => ['required', 'array:person_name,credentials'],
                'members.*.person_name' => ['required', 'string', 'max:160'],
                'members.*.credentials' => ['nullable', 'string', 'max:160'],
                'layout_row' => ['nullable', 'required_with:layout_column', 'integer', 'min:1', 'max:1000'],
                'layout_column' => ['nullable', 'required_with:layout_row', 'integer', 'min:1', 'max:9'],
                'credentials' => ['nullable', 'string', 'max:160'],
                'parent_id' => ['nullable', 'integer'],
                'order' => ['required', 'integer', 'min:1', 'max:1000000'],
                'is_active' => ['required', 'boolean'],
            ])->validate();
            $position->members = array_map(fn (array $member): array => ['person_name' => $member['person_name'], 'credentials' => $member['credentials'] ?? null], $members);
            $position->person_name = $position->members[0]['person_name'];
            $position->credentials = $position->members[0]['credentials'];
            $nodes = self::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $parents = $nodes->pluck('parent_id', 'id')->all();
            $seen = [$position->id];
            $parent = $position->parent_id;
            while ($parent !== null) {
                if (! array_key_exists($parent, $parents) || in_array($parent, $seen, true)) {
                    throw ValidationException::withMessages(['parent_id' => 'Jabatan atasan tidak valid atau membentuk hubungan melingkar.']);
                }
                $seen[] = $parent;
                $parent = $parents[$parent];
            }
            $nodes[$position->id ?? 0] = $position;
            $rows = [];
            $slots = [];
            $resolveRow = function (self $node) use (&$resolveRow, &$rows, $nodes): int {
                $key = $node->id ?? 0;
                if (isset($rows[$key])) {
                    return $rows[$key];
                }
                $minimum = $node->parent_id === null ? 1 : $resolveRow($nodes[$node->parent_id]) + 1;
                if ($node->layout_row !== null && $node->layout_row < $minimum) {
                    throw ValidationException::withMessages(['layout_row' => 'Baris jabatan harus berada di bawah atasannya dan di atas bawahannya.']);
                }

                return $rows[$key] = $node->layout_row ?? $minimum;
            };
            foreach ($nodes as $node) {
                $resolveRow($node);
                if ($node->layout_row !== null) {
                    $slot = $node->layout_row.':'.$node->layout_column;
                    if (isset($slots[$slot])) {
                        throw ValidationException::withMessages(['layout_column' => 'Posisi ini sudah digunakan jabatan lain.']);
                    }
                    $slots[$slot] = true;
                }
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
                // Induk nonaktif menyembunyikan seluruh cabangnya, termasuk bawahan yang masih aktif.
                if ($activeOnly && ! $node->is_active) {
                    continue;
                }
                $rows[] = [...$node->only(['id', 'parent_id', 'title', 'person_name', 'credentials', 'members', 'layout_row', 'layout_column', 'order', 'is_active']), 'depth' => $depth];
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

    /** @param array<int, int|string> $ids */
    public function mergePositions(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $nodes = self::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $nodes[$this->id];
            $ids = array_map('intval', $ids);
            if (! $ids || count(array_unique($ids)) !== count($ids) || in_array($target->id, $ids, true) || count($nodes->only($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['position_ids' => 'Pilih jabatan yang akan digabungkan.']);
            }
            $selected = $nodes->only([$target->id, ...$ids]);
            foreach ($selected as $node) {
                if ($node->title !== $target->title || $node->parent_id !== $target->parent_id || $node->is_active !== $target->is_active || $node->children()->exists()) {
                    throw ValidationException::withMessages(['position_ids' => 'Hanya jabatan tanpa bawahan dengan judul, atasan, dan status aktif yang sama dapat digabungkan.']);
                }
            }
            $old = $selected->map->only(['id', 'parent_id', 'title', 'members', 'layout_row', 'layout_column'])->values()->all();
            $members = $target->members;
            foreach ($ids as $id) {
                $members = [...$members, ...$nodes[$id]->members];
            }
            $target->update(['members' => $members]);
            foreach ($ids as $id) {
                $nodes[$id]->delete();
            }
            activity()->performedOn($target)->causedBy(auth()->user())->event('merged')
                ->withProperties(['merged_positions' => $old])->log('Menggabungkan jabatan '.$target->title);
            $this->refresh();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
