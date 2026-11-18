<?php

namespace Tests\Feature;

use App\Models\CertificationScheme;
use App\Models\SchemeCategory;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReconcileCertificationSchemesTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->path = tempnam(sys_get_temp_dir(), 'scheme-reconcile-');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
        parent::tearDown();
    }

    public function test_reconciliation_preserves_categories_clears_prices_removes_retired_highlights_and_supports_rollback(): void
    {
        $manifest = $this->fixture();
        $before = $this->snapshot();

        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 2);
        $id = $manifest['items'][0]['target_id'];
        $this->assertDatabaseHas('certification_schemes', ['id' => $id, 'price' => null, 'category' => 'Existing category']);
        $this->assertSame($before['schemes'][0]['slug'], CertificationScheme::findOrFail($id)->slug);
        $this->assertDatabaseMissing('certification_schemes', ['id' => $manifest['remove_ids'][0]]);
        $this->assertSame([(string) $id], SitePage::where('kind', 'landing')->firstOrFail()->content['highlighted_scheme_ids']);
        $after = $this->snapshot();
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertSuccessful();
        $this->assertSame($after, $this->snapshot());
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--rollback' => true])->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_drift_or_missing_asset_cannot_delete_existing_schemes(): void
    {
        $manifest = $this->fixture();
        $manifest['items'][1]['photo_file'] = '99999999.webp';
        file_put_contents($this->path, json_encode($manifest));
        $before = $this->snapshot();

        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertFailed();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_unreviewed_changes_stop_apply_and_rollback(): void
    {
        $manifest = $this->fixture();
        $id = $manifest['items'][0]['target_id'];
        CertificationScheme::findOrFail($id)->update(['price' => 42]);
        $changed = $this->snapshot();
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertFailed();
        $this->assertSame($changed, $this->snapshot());
        $manifest['baseline'] = $changed;
        file_put_contents($this->path, json_encode($manifest));
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertSuccessful();
        CertificationScheme::findOrFail($id)->update(['price' => 99]);
        $changed = $this->snapshot();

        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--rollback' => true])->assertFailed();

        $this->assertSame($changed, $this->snapshot());
    }

    public function test_actual_complete_catalogue_has_exactly_fifty_two_schemes_and_no_retired_ids(): void
    {
        $path = resource_path('data/lspglobal/complete-schemes-20261029.json');
        $this->assertFileExists($path);
        $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        DB::table('site_pages')->delete();
        foreach (['categories' => 'scheme_categories', 'schemes' => 'certification_schemes', 'pages' => 'site_pages'] as $key => $table) {
            foreach ($manifest['baseline'][$key] as $row) {
                foreach ($row as $field => $value) {
                    if (is_array($value)) {
                        $row[$field] = json_encode($value);
                    }
                }
                DB::table($table)->insert($row);
            }
        }
        $before = $this->snapshot();
        $this->artisan('content:reconcile-schemes', ['--manifest' => $path])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());

        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 52);
        $this->assertSame(0, CertificationScheme::whereNotNull('price')->count());
        $this->assertSame(0, CertificationScheme::whereIn('id', [7, 8, 12, 14, 15, 16, 31, 32, 34, 35, 39])->count());
        $this->assertSame(13, SchemeCategory::count());
        $unitCount = 0;
        foreach ($manifest['items'] as $item) {
            $row = CertificationScheme::where('name', $item['attributes']['name'])->sole()->toArray();
            $unitCount += count($row['units']);
            $this->assertSame('Okupasi', $row['type']);
            $this->assertCount(4, $row['basic_requirements']);
            foreach ($item['attributes'] as $field => $value) {
                $this->assertSame($value, $row[$field]);
            }
            if ($item['target_id'] !== null) {
                $original = collect($before['schemes'])->firstWhere('id', $item['target_id']);
                $this->assertSame($original['id'], $row['id']);
                $this->assertSame($original['slug'], $row['slug']);
                $this->assertSame($original['category'], $row['category']);
            } else {
                $this->assertNotEmpty($row['category']);
            }
        }
        $this->assertSame(452, $unitCount);
        $this->assertCount(9, CertificationScheme::where('name', 'INSINYUR SISTEM (SYSTEM ENGINEER)')->sole()->units);
        $this->assertCount(11, CertificationScheme::where('name', 'MANAJER PUSAT DATA (DATA CENTER MANAGER)')->sole()->units);
        $this->assertSame(['17', '24', '28'], SitePage::findOrFail(5)->content['highlighted_scheme_ids']);
        $after = $this->snapshot();
        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--apply' => true])->assertSuccessful();
        $this->assertSame($after, $this->snapshot());
        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--rollback' => true])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_common_administrative_requirements_apply_to_every_scheme_without_other_changes(): void
    {
        $path = resource_path('data/lspglobal/scheme-administration-20261029.json');
        $this->assertFileExists($path);
        $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        DB::table('site_pages')->delete();
        foreach (['categories' => 'scheme_categories', 'schemes' => 'certification_schemes', 'pages' => 'site_pages'] as $key => $table) {
            foreach ($manifest['baseline'][$key] as $row) {
                foreach ($row as $field => $value) {
                    if (is_array($value)) {
                        $row[$field] = json_encode($value);
                    }
                }
                DB::table($table)->insert($row);
            }
        }
        $before = $this->snapshot();
        $requirements = ['KTP (WNI)/ Paspor dan Kartu Izin Tinggal Terbatas (Kitas) (WNA)', 'Pas Photo 3x4 background merah'];

        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 52);
        foreach ($before['schemes'] as $old) {
            $current = CertificationScheme::findOrFail($old['id'])->toArray();
            $old['administrative_requirements'] = $requirements;
            $old['updated_at'] = $current['updated_at'];
            $this->assertSame($old, $current);
        }
        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--rollback' => true])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_category_corrections_require_explicit_mapping_and_restore_removed_category_on_rollback(): void
    {
        $manifest = $this->fixture();
        $before = $this->snapshot();
        foreach ($manifest['items'] as &$item) {
            $item['attributes']['category'] = 'Correct category';
        }
        unset($item);
        file_put_contents($this->path, json_encode($manifest));
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertFailed();
        $this->assertSame($before, $this->snapshot());
        $manifest['category_changes'] = [['id' => $manifest['items'][0]['target_id'], 'from' => 'Existing category', 'to' => 'Correct category']];
        $manifest['remove_category_names'] = ['Existing category'];
        file_put_contents($this->path, json_encode($manifest));

        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--apply' => true])->assertSuccessful();

        $this->assertSame(2, CertificationScheme::where('category', 'Correct category')->count());
        $this->assertDatabaseMissing('scheme_categories', ['name' => 'Existing category']);
        $this->artisan('content:reconcile-schemes', ['--manifest' => $this->path, '--rollback' => true])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_verified_categories_leave_no_empty_or_occupation_categories_and_preserve_all_other_fields(): void
    {
        $path = resource_path('data/lspglobal/scheme-categories-verified-20261029.json');
        $this->assertFileExists($path);
        $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        DB::table('site_pages')->delete();
        foreach (['categories' => 'scheme_categories', 'schemes' => 'certification_schemes', 'pages' => 'site_pages'] as $key => $table) {
            foreach ($manifest['baseline'][$key] as $row) {
                foreach ($row as $field => $value) {
                    if (is_array($value)) {
                        $row[$field] = json_encode($value);
                    }
                }
                DB::table($table)->insert($row);
            }
        }
        $before = $this->snapshot();

        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseCount('certification_schemes', 52);
        $this->assertDatabaseMissing('scheme_categories', ['name' => 'Okupasi']);
        $this->assertSame(0, CertificationScheme::whereNull('category')->orWhere('category', '')->orWhere('category', 'Okupasi')->count());
        $changes = collect($manifest['category_changes'])->keyBy('id');
        $this->assertCount(13, $changes);
        foreach ($before['schemes'] as $old) {
            $current = CertificationScheme::findOrFail($old['id'])->toArray();
            $old['updated_at'] = $current['updated_at'];
            if ($changes->has($old['id'])) {
                $old['category'] = $changes[$old['id']]['to'];
            }
            $this->assertSame($old, $current);
        }
        $this->assertSame('DATA MANAGEMENT SYSTEM', CertificationScheme::findOrFail(36)->category);
        $this->artisan('content:reconcile-schemes', ['--manifest' => $path, '--rollback' => true])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    private function snapshot(): array
    {
        return ['schemes' => CertificationScheme::orderBy('id')->get()->toArray(), 'pages' => SitePage::orderBy('id')->get()->toArray(), 'categories' => SchemeCategory::orderBy('id')->get()->toArray()];
    }

    private function fixture(): array
    {
        $keep = CertificationScheme::factory()->create(['category' => 'Existing category'])->refresh();
        $remove = CertificationScheme::factory()->create()->refresh();
        SitePage::where('kind', 'landing')->firstOrFail()->update(['content' => ['highlighted_scheme_ids' => [(string) $keep->id, (string) $remove->id]]]);
        $attributes = ['name' => 'Updated scheme', 'category' => $keep->category, 'type' => 'Okupasi', 'price' => null, 'units' => [['code' => 'J.620100.010.02', 'title' => 'Unit']], 'basic_requirements' => ['Requirement'], 'thumbnail' => 'schemes/stock/6803529.webp'];
        $item = ['source_index' => 1, 'target_id' => $keep->id, 'attributes' => $attributes, 'photo_file' => '6803529.webp', 'photo_sha256' => hash_file('sha256', resource_path('data/lspglobal/stock/6803529.webp'))];
        $new = $item;
        $new['source_index'] = 2;
        $new['target_id'] = null;
        $new['attributes'] = array_replace($attributes, ['name' => 'New scheme', 'category' => 'Existing category']);
        $manifest = ['batch' => 'test-reconcile', 'baseline' => $this->snapshot(), 'items' => [$item, $new], 'remove_ids' => [$remove->id]];
        file_put_contents($this->path, json_encode($manifest));

        return $manifest;
    }
}
