<?php

namespace App\Console\Commands;

use App\Models\CertificationScheme;
use App\Models\SchemeCategory;
use App\Models\SitePage;
use App\Observers\ContentDirectoryObserver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Signature('content:reconcile-schemes {--manifest= : Full catalogue manifest} {--apply : Apply reviewed catalogue} {--rollback : Restore this batch}')]
#[Description('Reconcile a complete scheme catalogue with explicit removals and recoverable snapshots')]
class ReconcileCertificationSchemes extends Command
{
    private array $manifest;

    private string $hash;

    private string $journal;

    public function handle(): int
    {
        try {
            if ($this->option('apply') && $this->option('rollback')) {
                throw new RuntimeException('Apply and rollback cannot be combined.');
            }
            $contents = file_get_contents($this->option('manifest') ?: resource_path('data/lspglobal/complete-schemes-20261029.json'));
            $this->manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            Validator::make($this->manifest, [
                'batch' => ['required', 'regex:/^[a-z0-9-]+$/'],
                'baseline.schemes' => ['required', 'array'], 'baseline.pages' => ['present', 'array'], 'baseline.categories' => ['present', 'array'],
                'items' => ['required', 'array', 'min:1'], 'items.*.source_index' => ['required', 'integer', 'distinct'],
                'items.*.target_id' => ['present', 'nullable', 'integer', 'distinct'],
                'items.*.attributes' => ['required', 'array:name,type,category,price,units,basic_requirements,thumbnail,level,methods,registration_url,administrative_requirements,is_active'],
                'items.*.attributes.name' => ['required', 'string', 'max:255', 'distinct'],
                'items.*.photo_file' => ['required', 'regex:/^[0-9]+\.webp$/'],
                'items.*.photo_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
                'remove_ids' => ['present', 'array'], 'remove_ids.*' => ['integer', 'distinct'],
                'category_changes' => ['sometimes', 'array'],
                'category_changes.*.id' => ['required', 'integer', 'distinct'],
                'category_changes.*.from' => ['present', 'nullable', 'string'],
                'category_changes.*.to' => ['required', 'string', 'max:255'],
                'remove_category_names' => ['sometimes', 'array'],
                'remove_category_names.*' => ['required', 'string', 'distinct'],
            ])->validate();
            $this->hash = hash('sha256', $contents);
            $this->journal = 'scheme-reconciliations/'.$this->manifest['batch'];

            return Cache::lock('scheme-reconciliation', 300)->block(5, fn (): int => $this->option('rollback') ? $this->rollback() : $this->reconcile());
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function snapshot(bool $lock = false): array
    {
        $snapshot = [];
        foreach (['schemes' => CertificationScheme::class, 'pages' => SitePage::class, 'categories' => SchemeCategory::class] as $key => $class) {
            $query = $class::orderBy('id');
            if ($lock) {
                $query->lockForUpdate();
            }
            $snapshot[$key] = $query->get()->toArray();
        }

        return $snapshot;
    }

    private function reconcile(): int
    {
        $after = $this->readJournal('after.json');
        if ($after !== null) {
            $this->assertSnapshot($after['snapshot']);
            $this->info('Already applied; no changes.');

            return self::SUCCESS;
        }
        $this->assertSnapshot($this->manifest['baseline']);
        $targets = array_values(array_filter(array_column($this->manifest['items'], 'target_id'), fn (?int $id): bool => $id !== null));
        $removed = $this->manifest['remove_ids'];
        $unmatched = array_values(array_diff(array_column($this->manifest['baseline']['schemes'], 'id'), $targets));
        sort($unmatched);
        $sortedRemoved = $removed;
        sort($sortedRemoved);
        if ($unmatched !== $sortedRemoved || array_intersect($targets, $removed)) {
            throw new RuntimeException('Every existing ID must be retained or explicitly removed, exactly once.');
        }
        $ownedImages = [];
        $totals = ['update' => 0, 'create' => 0, 'unchanged' => 0];
        foreach ($this->manifest['items'] as $item) {
            $attributes = $item['attributes'];
            if (! array_key_exists('price', $attributes) || $attributes['price'] !== null) {
                throw new RuntimeException('All scheme prices must be null.');
            }
            $file = resource_path('data/lspglobal/stock/'.$item['photo_file']);
            $path = 'schemes/stock/'.$item['photo_file'];
            if ($attributes['thumbnail'] !== $path || ! is_file($file) || hash_file('sha256', $file) !== $item['photo_sha256']) {
                throw new RuntimeException('Missing or invalid image: '.$item['photo_file']);
            }
            if (Storage::disk('public')->exists($path)) {
                if (hash('sha256', Storage::disk('public')->get($path)) !== $item['photo_sha256']) {
                    throw new RuntimeException('Existing destination image differs: '.$path);
                }
            } else {
                $ownedImages[] = $path;
            }
            if ($item['target_id'] !== null) {
                $scheme = CertificationScheme::findOrFail($item['target_id']);
                if (($attributes['category'] ?? null) !== $scheme->category) {
                    $change = collect($this->manifest['category_changes'] ?? [])->firstWhere('id', $scheme->id);
                    if ($change === null || $change['from'] !== $scheme->category || $change['to'] !== $attributes['category']) {
                        throw new RuntimeException('Category change needs an explicit reviewed mapping: '.$scheme->id);
                    }
                }
                $scheme->fill($attributes);
                $action = $scheme->isDirty() ? 'update' : 'unchanged';
            } else {
                if (CertificationScheme::where('name', $attributes['name'])->orWhere('slug', Str::slug($attributes['name']))->exists()) {
                    throw new RuntimeException('New scheme name or slug already exists.');
                }
                $action = 'create';
            }
            $totals[$action]++;
            $this->line($action.' | '.($item['target_id'] ?? 'new').' | '.$attributes['name']);
        }
        foreach ($removed as $id) {
            $this->line('delete | '.$id.' | '.CertificationScheme::findOrFail($id)->name);
        }
        $this->info(sprintf('Update: %d; Create: %d; Delete: %d; Unchanged: %d', $totals['update'], $totals['create'], count($removed), $totals['unchanged']));
        if (! $this->option('apply')) {
            return self::SUCCESS;
        }
        if ($this->readJournal('before.json') !== null) {
            throw new RuntimeException('Pending journal exists; use rollback to recover before retrying.');
        }
        $this->writeJournal('before.json', ['snapshot' => $this->manifest['baseline'], 'owned_images' => array_values(array_unique($ownedImages))]);
        foreach ($this->manifest['items'] as $item) {
            $path = $item['attributes']['thumbnail'];
            if (! Storage::disk('public')->exists($path) && ! Storage::disk('public')->put($path, file_get_contents(resource_path('data/lspglobal/stock/'.$item['photo_file'])))) {
                throw new RuntimeException('Image copy failed.');
            }
        }
        try {
            DB::transaction(function () use ($removed): void {
                $this->assertSnapshot($this->manifest['baseline'], true);
                foreach ($this->manifest['items'] as $item) {
                    $scheme = $item['target_id'] !== null ? CertificationScheme::findOrFail($item['target_id']) : new CertificationScheme;
                    $scheme->fill($item['attributes']);
                    if (! $scheme->exists || $scheme->isDirty()) {
                        $scheme->save();
                    }
                }
                foreach (SitePage::all() as $page) {
                    $content = $page->content ?? [];
                    if (isset($content['highlighted_scheme_ids'])) {
                        $ids = array_values(array_filter($content['highlighted_scheme_ids'], fn (mixed $id): bool => ! in_array((int) $id, $removed, true)));
                        if ($ids !== $content['highlighted_scheme_ids']) {
                            $content['highlighted_scheme_ids'] = $ids;
                            $page->update(['content' => $content]);
                        }
                    }
                }
                foreach ($removed as $id) {
                    CertificationScheme::findOrFail($id)->delete();
                }
                foreach ($this->manifest['remove_category_names'] ?? [] as $name) {
                    if (CertificationScheme::where('category', $name)->exists()) {
                        throw new RuntimeException('Category is still in use: '.$name);
                    }
                    SchemeCategory::where('name', $name)->firstOrFail()->delete();
                }
                $this->writeJournal('after.json', ['snapshot' => $this->snapshot()]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($this->journal.'/after.json');
            throw $exception;
        }
        $this->info('Applied. Backup journal: '.Storage::disk('local')->path($this->journal.'/before.json'));

        return self::SUCCESS;
    }

    private function rollback(): int
    {
        $before = $this->readJournal('before.json') ?? throw new RuntimeException('No batch backup.');
        $after = $this->readJournal('after.json');
        DB::transaction(function () use ($before, $after): void {
            $this->assertSnapshot($after['snapshot'] ?? $before['snapshot'], true);
            if ($after === null) {
                return;
            }
            $baseline = $before['snapshot'];
            CertificationScheme::whereNotIn('id', array_column($baseline['schemes'], 'id'))->get()->each->delete();
            foreach (['categories' => 'scheme_categories', 'schemes' => 'certification_schemes', 'pages' => 'site_pages'] as $key => $table) {
                foreach ($baseline[$key] as $row) {
                    foreach ($row as $field => $value) {
                        if (is_array($value)) {
                            $row[$field] = json_encode($value, JSON_THROW_ON_ERROR);
                        } elseif (in_array($field, ['created_at', 'updated_at'], true) && $value !== null) {
                            $row[$field] = Carbon::parse($value)->format('Y-m-d H:i:s');
                        }
                    }
                    DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                }
            }
            SchemeCategory::whereNotIn('id', array_column($baseline['categories'], 'id'))->get()->each->delete();
        });
        foreach ($before['owned_images'] as $path) {
            if (! CertificationScheme::where('thumbnail', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        }
        Cache::forever(ContentDirectoryObserver::CACHE_VERSION_KEY, (string) Str::uuid());
        $this->writeJournal('rolled-back.json', ['before' => $before, 'after' => $after]);
        Storage::disk('local')->delete([$this->journal.'/before.json', $this->journal.'/after.json']);
        $this->info('Rollback complete. Archived journal retained.');

        return self::SUCCESS;
    }

    private function assertSnapshot(array $expected, bool $lock = false): void
    {
        if ($this->snapshot($lock) !== $expected) {
            throw new RuntimeException('Database differs from reviewed snapshot; refusing changes.');
        }
    }

    private function readJournal(string $name): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($this->journal.'/'.$name)) {
            return null;
        }
        $data = json_decode($disk->get($this->journal.'/'.$name), true, 512, JSON_THROW_ON_ERROR);
        if ($data['manifest_hash'] !== $this->hash) {
            throw new RuntimeException('Manifest differs from batch journal.');
        }

        return $data;
    }

    private function writeJournal(string $name, array $data): void
    {
        if (! Storage::disk('local')->put($this->journal.'/'.$name, json_encode(['manifest_hash' => $this->hash] + $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Journal write failed.');
        }
    }
}
