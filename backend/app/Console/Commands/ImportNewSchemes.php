<?php

namespace App\Console\Commands;

use App\Models\CertificationScheme;
use App\Models\SitePage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Signature('content:import-new-schemes {--manifest= : Path manifest JSON} {--apply : Terapkan perubahan} {--rollback : Pulihkan batch yang belum diedit lagi}')]
#[Description('Preview dan impor skema Oktober 2026 dengan baseline, backup, dan rollback')]
class ImportNewSchemes extends Command
{
    private array $manifest;

    private string $journal;

    private string $manifestHash;

    public function handle(): int
    {
        try {
            if ($this->option('apply') && $this->option('rollback')) {
                throw new RuntimeException('Pilih apply atau rollback.');
            }
            $path = $this->option('manifest') ?: resource_path('data/lspglobal/new-schemes-20261028.json');
            $contents = file_get_contents($path);
            $this->manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            Validator::make($this->manifest, [
                'batch' => ['required', 'regex:/^[a-z0-9-]+$/'],
                'entries' => ['required', 'array', 'min:1'],
                'entries.*.source_index' => ['required', 'integer', 'distinct'],
                'entries.*.target_id' => ['present', 'nullable', 'integer', 'distinct:ignore_case'],
                'entries.*.attributes' => ['required', 'array:name,type,units,basic_requirements,thumbnail,category,level,price,registration_url,methods,administrative_requirements,is_active'],
                'entries.*.attributes.name' => ['required', 'string', 'max:255', 'distinct'],
                'entries.*.photo_file' => ['required', 'regex:/^[0-9]+\.webp$/'],
                'entries.*.photo_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            ])->validate();
            $this->manifestHash = hash('sha256', $contents);
            $this->journal = 'scheme-imports/'.$this->manifest['batch'];

            return Cache::lock('scheme-import:'.$this->manifest['batch'], 300)->block(5, function (): int {
                if ($this->option('rollback')) {
                    return $this->rollback();
                }

                return $this->import();
            });
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function import(): int
    {
        foreach ($this->manifest['entries'] as $entry) {
            $source = resource_path('data/lspglobal/stock/'.$entry['photo_file']);
            if (! is_file($source) || hash_file('sha256', $source) !== $entry['photo_sha256']) {
                throw new RuntimeException('Gambar tidak tersedia atau checksum berbeda: '.$entry['photo_file']);
            }
            if ($entry['attributes']['thumbnail'] !== 'schemes/stock/'.$entry['photo_file']) {
                throw new RuntimeException('Path gambar tidak cocok dengan sumber.');
            }
            $disk = Storage::disk('public');
            if ($disk->exists($entry['attributes']['thumbnail']) && hash('sha256', $disk->get($entry['attributes']['thumbnail'])) !== $entry['photo_sha256']) {
                throw new RuntimeException('Gambar tujuan sudah ada dengan isi berbeda.');
            }
        }
        $receipt = $this->readJournal('after.json');
        $plan = $this->plan($receipt);
        $totals = array_count_values(array_column($plan, 'action'));
        foreach ($plan as $item) {
            $this->line($item['action'].' | '.($item['record']->id ?? 'new').' | '.$item['entry']['attributes']['name']);
        }
        $this->info(sprintf('Update: %d; Create: %d; Unchanged: %d', $totals['update'] ?? 0, $totals['create'] ?? 0, $totals['unchanged'] ?? 0));
        if (! $this->option('apply') || ! (($totals['update'] ?? 0) + ($totals['create'] ?? 0))) {
            return self::SUCCESS;
        }
        if ($receipt !== null || $this->readJournal('before.json') !== null) {
            throw new RuntimeException('Journal batch sudah ada; periksa backup sebelum mencoba apply ulang.');
        }
        $copied = [];
        foreach ($this->manifest['entries'] as $entry) {
            $path = $entry['attributes']['thumbnail'];
            if (! Storage::disk('public')->exists($path)) {
                $copied[] = $path;
            }
        }
        $backup = [
            'manifest_hash' => $this->manifestHash,
            'schemes' => CertificationScheme::orderBy('id')->get()->map(fn (CertificationScheme $scheme): array => $scheme->getRawOriginal())->all(),
            'pages' => SitePage::orderBy('id')->get()->toArray(),
            'copied_images' => array_values(array_unique($copied)),
        ];
        $this->writeJournal('before.json', $backup);
        foreach ($this->manifest['entries'] as $entry) {
            if (! Storage::disk('public')->exists($entry['attributes']['thumbnail'])) {
                if (! Storage::disk('public')->put($entry['attributes']['thumbnail'], file_get_contents(resource_path('data/lspglobal/stock/'.$entry['photo_file'])))) {
                    throw new RuntimeException('Gagal menyalin gambar.');
                }
            }
        }
        try {
            DB::transaction(function (): void {
                $plan = $this->plan(null, true);
                $records = [];
                foreach ($plan as $item) {
                    $record = $item['record'];
                    $record->fill($item['entry']['attributes']);
                    if ($record->isDirty() || ! $record->exists) {
                        $record->save();
                    }
                    $records[(string) $item['entry']['source_index']] = $record->refresh()->toArray();
                }
                $this->writeJournal('after.json', ['manifest_hash' => $this->manifestHash, 'records' => $records]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($this->journal.'/after.json');
            throw $exception;
        }
        $this->info('Apply selesai. Backup: '.Storage::disk('local')->path($this->journal.'/before.json'));

        return self::SUCCESS;
    }

    private function plan(?array $receipt, bool $lock = false): array
    {
        $plan = [];
        foreach ($this->manifest['entries'] as $entry) {
            $query = CertificationScheme::query();
            if ($lock) {
                $query->lockForUpdate();
            }
            $id = $entry['target_id'];
            if ($receipt !== null) {
                $id = $receipt['records'][(string) $entry['source_index']]['id'] ?? throw new RuntimeException('Receipt tidak lengkap.');
            }
            if ($id !== null) {
                $record = $query->find($id) ?? throw new RuntimeException('ID target hilang: '.$id);
                $current = $record->toArray();
                if ($receipt !== null && $current !== $receipt['records'][(string) $entry['source_index']]) {
                    throw new RuntimeException('Record berubah setelah impor: '.$id);
                }
                $record->fill($entry['attributes']);
                $dirty = $record->isDirty();
                if ($receipt === null && $current !== $entry['before']) {
                    throw new RuntimeException('Baseline berbeda: '.$id);
                }
                $plan[] = ['entry' => $entry, 'record' => $record, 'action' => $dirty ? 'update' : 'unchanged'];
            } else {
                $slug = Str::slug($entry['attributes']['name']);
                if ($query->where(function ($query) use ($slug, $entry): void {
                    $query->where('slug', $slug)->orWhere('name', $entry['attributes']['name']);
                })->exists()) {
                    throw new RuntimeException('Nama/slug skema baru sudah ada: '.$slug);
                }
                $plan[] = ['entry' => $entry, 'record' => new CertificationScheme, 'action' => 'create'];
            }
        }

        return $plan;
    }

    private function rollback(): int
    {
        $before = $this->readJournal('before.json') ?? throw new RuntimeException('Backup tidak ada.');
        $after = $this->readJournal('after.json');
        DB::transaction(function () use ($before, $after): void {
            if (SitePage::orderBy('id')->lockForUpdate()->get()->toArray() !== $before['pages']) {
                throw new RuntimeException('Halaman berubah; periksa referensi skema sebelum rollback.');
            }
            if ($after === null) {
                $current = CertificationScheme::orderBy('id')->lockForUpdate()->get()
                    ->map(fn (CertificationScheme $scheme): array => $scheme->getRawOriginal())->all();
                if ($current !== $before['schemes']) {
                    throw new RuntimeException('Batch belum memiliki receipt dan database berubah; periksa backup manual.');
                }

                return;
            }
            $this->plan($after, true);
            $originals = collect($before['schemes'])->keyBy('id');
            foreach ($after['records'] as $row) {
                $record = CertificationScheme::findOrFail($row['id']);
                if ($originals->has($record->id)) {
                    $record->timestamps = false;
                    $record->setRawAttributes($originals[$record->id]);
                    $record->save();
                } else {
                    $record->delete();
                }
            }
        });
        foreach ($before['copied_images'] as $path) {
            if (! CertificationScheme::where('thumbnail', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        }
        $this->writeJournal('rolled-back.json', ['manifest_hash' => $this->manifestHash, 'before' => $before, 'after' => $after]);
        Storage::disk('local')->delete($this->journal.'/before.json');
        Storage::disk('local')->delete($this->journal.'/after.json');
        $this->info('Rollback selesai; backup tetap disimpan.');

        return self::SUCCESS;
    }

    private function readJournal(string $file): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($this->journal.'/'.$file)) {
            return null;
        }
        $data = json_decode($disk->get($this->journal.'/'.$file), true, 512, JSON_THROW_ON_ERROR);
        if ($data['manifest_hash'] !== $this->manifestHash) {
            throw new RuntimeException('Manifest berbeda dari journal batch.');
        }

        return $data;
    }

    private function writeJournal(string $file, array $data): void
    {
        if (! Storage::disk('local')->put($this->journal.'/'.$file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Gagal menulis backup/journal.');
        }
    }
}
