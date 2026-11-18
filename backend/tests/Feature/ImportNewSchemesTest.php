<?php

namespace Tests\Feature;

use App\Models\CertificationScheme;
use App\Models\SchemeCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class ImportNewSchemesTest extends TestCase
{
    use RefreshDatabase;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->manifestPath = tempnam(sys_get_temp_dir(), 'scheme-manifest-');
    }

    protected function tearDown(): void
    {
        unlink($this->manifestPath);
        parent::tearDown();
    }

    public function test_dry_run_leaves_database_and_storage_unchanged(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $before = $scheme->toArray();
        $this->manifest($scheme);

        $this->runImport()->assertSuccessful();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 1);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_apply_preserves_identity_other_fields_and_other_records_and_is_repeatable(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $other = CertificationScheme::factory()->create()->refresh();
        $before = $scheme->toArray();
        $otherBefore = $other->toArray();
        $this->manifest($scheme);

        $this->runImport(['--apply' => true])->assertSuccessful();

        $scheme->refresh();
        $this->assertSame('ASISTEN PENGEMBANG WEB (ASSISTANT WEB DEVELOPER)', $scheme->name);
        $this->assertSame($before['slug'], $scheme->slug);
        $this->assertSame($before['price'], $scheme->price);
        $this->assertSame($before['category'], $scheme->category);
        $this->assertSame('J.620100.010.02', $scheme->units[0]['code']);
        $this->assertSame($otherBefore, $other->refresh()->toArray());
        $this->assertDatabaseHas('certification_schemes', ['name' => 'PENGEMBANG FRONTEND (FRONTEND DEVELOPER)', 'price' => null, 'category' => null]);
        Storage::disk('public')->assertExists('schemes/stock/6803529.webp');
        Storage::disk('local')->assertExists('scheme-imports/test-batch/before.json');
        Storage::disk('local')->assertExists('scheme-imports/test-batch/after.json');
        $after = CertificationScheme::orderBy('id')->get()->toArray();

        $this->runImport(['--apply' => true])->assertSuccessful();
        $this->runImport()->assertSuccessful();

        $this->assertSame($after, CertificationScheme::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('certification_schemes', 3);
    }

    public function test_baseline_drift_rejects_entire_batch(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $this->manifest($scheme);
        $scheme->update(['price' => 1234]);
        $before = $scheme->refresh()->toArray();

        $this->runImport(['--apply' => true])->assertFailed();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 1);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_missing_asset_rejects_before_writing(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $this->manifest($scheme);
        $this->changeManifest('photo_file', 'missing.webp');
        $before = $scheme->toArray();

        $this->runImport(['--apply' => true])->assertFailed();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 1);
    }

    public function test_late_model_validation_failure_rolls_back_earlier_updates(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $this->manifest($scheme);
        $this->changeManifest('attributes.registration_url', 'http://insecure.example.com');
        $before = $scheme->toArray();

        $this->runImport(['--apply' => true])->assertFailed();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 1);

        $this->runImport(['--rollback' => true])->assertSuccessful();

        $this->assertSame([], Storage::disk('public')->allFiles());
        Storage::disk('local')->assertMissing('scheme-imports/test-batch/before.json');
        Storage::disk('local')->assertExists('scheme-imports/test-batch/rolled-back.json');
        $this->runImport()->assertSuccessful();
    }

    public function test_existing_slug_is_not_adopted_or_suffixed(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $this->manifest($scheme);
        CertificationScheme::factory()->create(['name' => 'PENGEMBANG FRONTEND (FRONTEND DEVELOPER)']);
        $before = $scheme->toArray();

        $this->runImport(['--apply' => true])->assertFailed();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 2);
    }

    public function test_partial_image_copy_failure_can_be_recovered_and_retried(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $before = $scheme->toArray();
        $this->manifest($scheme);
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $failing = \Mockery::mock($public);
        $failCopy = true;
        $failing->shouldReceive('put')->andReturnUsing(function (string $path, string $contents) use ($public, &$failCopy): bool {
            if (! $failCopy) {
                return $public->put($path, $contents);
            }
            $public->put($path, substr($contents, 0, 20));

            return false;
        });
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failing);

        $this->runImport(['--apply' => true])->assertFailed();
        $this->runImport(['--rollback' => true])->assertSuccessful();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertSame([], $public->allFiles());
        $failCopy = false;
        $this->runImport(['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('certification_schemes', 2);
    }

    public function test_rollback_restores_only_batch_records(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $other = CertificationScheme::factory()->create()->refresh();
        $before = $scheme->toArray();
        $otherBefore = $other->toArray();
        $this->manifest($scheme);
        $this->runImport(['--apply' => true])->assertSuccessful();

        $this->runImport(['--rollback' => true])->assertSuccessful();

        $this->assertSame($before, $scheme->refresh()->toArray());
        $this->assertSame($otherBefore, $other->refresh()->toArray());
        $this->assertDatabaseCount('certification_schemes', 2);
    }

    public function test_rollback_refuses_subsequent_edits(): void
    {
        $scheme = CertificationScheme::factory()->create()->refresh();
        $this->manifest($scheme);
        $this->runImport(['--apply' => true])->assertSuccessful();
        $scheme->refresh()->update(['price' => 1234]);

        $this->runImport(['--rollback' => true])->assertFailed();

        $this->assertDatabaseHas('certification_schemes', ['id' => $scheme->id, 'price' => 1234]);
        $this->assertDatabaseCount('certification_schemes', 2);
    }

    public function test_actual_manifest_imports_all_fifteen_schemes_with_exact_source_unit_counts(): void
    {
        $path = resource_path('data/lspglobal/new-schemes-20261028.json');
        $manifest = json_decode(file_get_contents($path), true);
        foreach ($manifest['entries'] as $entry) {
            if ($entry['target_id'] !== null) {
                $row = $entry['before'];
                SchemeCategory::firstOrCreate(['name' => $row['category']]);
                foreach ($row as $key => $value) {
                    if (is_array($value)) {
                        $row[$key] = json_encode($value);
                    }
                }
                DB::table('certification_schemes')->insert($row);
            }
        }

        $this->artisan('content:import-new-schemes', ['--manifest' => $path, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 15);
        $counts = [];
        foreach ($manifest['entries'] as $entry) {
            $scheme = CertificationScheme::where('name', $entry['attributes']['name'])->sole();
            $counts[] = count($scheme->units);
            $this->assertCount(4, $scheme->basic_requirements);
            $this->assertSame($entry['attributes']['units'], $scheme->units);
        }
        $this->assertSame([8, 5, 14, 10, 12, 6, 10, 6, 10, 13, 7, 6, 9, 7, 11], $counts);
        $this->artisan('content:import-new-schemes', ['--manifest' => $path])->assertSuccessful();
    }

    public function test_data_engineer_checkpoint_updates_eight_creates_four_and_preserves_previous_batch(): void
    {
        $path = resource_path('data/lspglobal/new-schemes-20261029.json');
        $this->assertFileExists($path);
        $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest['entries'] as $entry) {
            if ($entry['target_id'] !== null) {
                $row = $entry['before'];
                if (filled($row['category'])) {
                    SchemeCategory::firstOrCreate(['name' => $row['category']]);
                }
                foreach ($row as $key => $value) {
                    if (is_array($value)) {
                        $row[$key] = json_encode($value);
                    }
                }
                DB::table('certification_schemes')->insert($row);
            }
        }
        $previous = json_decode(file_get_contents(resource_path('data/lspglobal/new-schemes-20261028.json')), true);
        $previousIds = [40, 13, 9, 1, 41, 42, 43, 44, 45, 46, 3, 2, 47, 26, 24];
        foreach ($previous['entries'] as $index => $entry) {
            CertificationScheme::factory()->create(['id' => $previousIds[$index]] + $entry['attributes']);
        }
        foreach (range(1, 47) as $id) {
            if (! CertificationScheme::whereKey($id)->exists()) {
                CertificationScheme::factory()->create(['id' => $id, 'name' => $id === 14 ? 'CHIEF CYBER SECURITY OFFICER' : 'Existing scheme '.$id]);
            }
        }
        $before = CertificationScheme::orderBy('id')->get()->keyBy('id')->toArray();
        $options = ['--manifest' => $path];

        $this->artisan('content:import-new-schemes', $options)
            ->expectsOutput('Update: 8; Create: 4; Unchanged: 0')->assertSuccessful();
        $this->assertSame($before, CertificationScheme::orderBy('id')->get()->keyBy('id')->toArray());
        $this->artisan('content:import-new-schemes', $options + ['--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 51);
        $counts = [];
        foreach ($manifest['entries'] as $entry) {
            $scheme = CertificationScheme::where('name', $entry['attributes']['name'])->sole();
            $counts[] = count($scheme->units);
            $this->assertSame('Okupasi', $scheme->type);
            $this->assertSame($entry['attributes']['units'], $scheme->units);
            $this->assertSame($entry['attributes']['basic_requirements'], $scheme->basic_requirements);
            $this->assertCount(4, $scheme->basic_requirements);
            if ($entry['target_id'] !== null) {
                $id = $entry['target_id'];
                $this->assertSame($id, $scheme->id);
                foreach (array_diff_key($before[$id], $entry['attributes'], ['updated_at' => true]) as $key => $value) {
                    $this->assertSame($value, $scheme->toArray()[$key], 'Preserved field '.$key.' of ID '.$id);
                }
            } else {
                $this->assertNull($scheme->price);
                $this->assertNull($scheme->level);
            }
        }
        $this->assertSame([11, 8, 8, 9, 8, 7, 7, 4, 6, 4, 11, 5], $counts);
        $this->assertContains('J.62PDP00.011.1', array_column(CertificationScheme::findOrFail(25)->units, 'code'));
        $this->assertContains('J.62DMI00.007.1', array_column(CertificationScheme::findOrFail(22)->units, 'code'));
        $this->assertNotContains('J.62DMI00.007. 1', array_column(CertificationScheme::findOrFail(22)->units, 'code'));
        foreach (['J.63OPR00.010.2', 'J.63OPR00.014.2', 'J.63OPR00.016.2'] as $code) {
            $this->assertContains($code, array_column(CertificationScheme::findOrFail(36)->units, 'code'));
        }
        $targetIds = array_column(array_filter($manifest['entries'], fn (array $entry): bool => $entry['target_id'] !== null), 'target_id');
        $after = CertificationScheme::orderBy('id')->get()->keyBy('id')->toArray();
        foreach (array_diff(array_keys($before), $targetIds) as $id) {
            $this->assertSame($before[$id], $after[$id]);
        }

        $this->artisan('content:import-new-schemes', $options + ['--apply' => true])
            ->expectsOutput('Update: 0; Create: 0; Unchanged: 12')->assertSuccessful();
        $this->assertSame($after, CertificationScheme::orderBy('id')->get()->keyBy('id')->toArray());
        $this->artisan('content:import-new-schemes', $options + ['--rollback' => true])->assertSuccessful();

        $this->assertSame($before, CertificationScheme::orderBy('id')->get()->keyBy('id')->toArray());
        $this->assertDatabaseCount('certification_schemes', 47);
    }

    private function runImport(array $options = []): PendingCommand
    {
        return $this->artisan('content:import-new-schemes', ['--manifest' => $this->manifestPath] + $options);
    }

    private function changeManifest(string $key, mixed $value): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        data_set($manifest, 'entries.1.'.$key, $value);
        file_put_contents($this->manifestPath, json_encode($manifest));
    }

    private function manifest(CertificationScheme $scheme): void
    {
        $attributes = [
            'name' => 'ASISTEN PENGEMBANG WEB (ASSISTANT WEB DEVELOPER)', 'type' => 'Okupasi',
            'units' => [['code' => 'J.620100.010.02', 'title' => 'Menerapkan Perintah Eksekusi Bahasa Pemrograman']],
            'basic_requirements' => ['Lulus pelatihan berbasis kompetensi.'], 'thumbnail' => 'schemes/stock/6803529.webp',
        ];
        $entry = [
            'source_index' => 1, 'target_id' => $scheme->id, 'before' => $scheme->toArray(), 'attributes' => $attributes,
            'photo_file' => '6803529.webp', 'photo_sha256' => hash_file('sha256', resource_path('data/lspglobal/stock/6803529.webp')),
        ];
        $new = array_replace($entry, ['source_index' => 2, 'target_id' => null, 'before' => null]);
        $new['attributes'] = array_replace($attributes, [
            'name' => 'PENGEMBANG FRONTEND (FRONTEND DEVELOPER)', 'category' => null, 'price' => null, 'level' => null,
            'registration_url' => null, 'methods' => [], 'administrative_requirements' => [], 'is_active' => true,
        ]);
        file_put_contents($this->manifestPath, json_encode(['batch' => 'test-batch', 'baseline_ids' => [$scheme->id], 'entries' => [$entry, $new]]));
    }
}
