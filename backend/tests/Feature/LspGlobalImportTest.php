<?php

namespace Tests\Feature;

use App\Filament\Resources\Assessors\Pages\ManageAssessors;
use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\Tuk;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LspGlobalImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_ids_slugs_and_missing_data_and_can_be_repeated(): void
    {
        Storage::fake('public');
        $existing = CertificationScheme::factory()->create(['name' => 'Junior Coder', 'thumbnail' => null]);
        $slug = $existing->slug;
        $this->artisan('content:import-lspglobal')->assertSuccessful();
        $this->assertDatabaseCount('tuks', 45);
        $this->assertDatabaseCount('certification_schemes', 39);
        $this->assertDatabaseCount('assessors', 82);
        $this->assertSame($slug, $existing->refresh()->slug);
        $this->assertCount(8, $existing->units);
        $this->assertNotEmpty($existing->units[0]['description']);
        $this->assertSame(359, CertificationScheme::all()->sum(fn ($scheme) => count($scheme->units)));
        $chiefUnits = CertificationScheme::where('name', 'CHIEF DATA SCIENTIST')->firstOrFail()->units;
        $this->assertCount(7, $chiefUnits);
        $this->assertCount(7, array_unique(array_column($chiefUnits, 'code')));
        $this->assertContains('Membuat Rencana Proyek Data Science', array_column($chiefUnits, 'title'));
        $this->assertSame('Sewaktu', Tuk::where('code', 'TUK-TIKGLOBAL-002')->firstOrFail()->kind);
        $this->assertNull(Tuk::where('code', 'TUK-029/2024')->firstOrFail()->address);
        $enrichment = json_decode(file_get_contents(resource_path('data/lspglobal/tuk-enrichment.json')), true, 512, JSON_THROW_ON_ERROR)['entries'];
        foreach (array_keys(array_filter($enrichment, fn ($entry) => isset($entry['logo_file']))) as $code) {
            $tuk = Tuk::where('code', $code)->firstOrFail();
            $this->assertNotNull($tuk->logo_path);
            Storage::disk('public')->assertExists($tuk->logo_path);
        }
        $this->assertSame(39, Tuk::where('is_active', true)->whereNotNull('logo_path')->count());
        $this->assertSame(39, Tuk::where('is_active', true)->whereNotNull('logo_path')->whereNotNull('website_url')->count());
        $this->assertSame('https://desty.page/medstudiojakarta', Tuk::where('code', 'TUK-TIKGLOBAL-005')->firstOrFail()->website_url);
        $this->assertSame(39, CertificationScheme::distinct()->count('thumbnail'));
        foreach (CertificationScheme::all() as $scheme) {
            $this->assertStringStartsWith('schemes/stock/', $scheme->thumbnail);
            $dimensions = getimagesize(Storage::disk('public')->path($scheme->thumbnail));
            $this->assertGreaterThanOrEqual(1920, $dimensions[0]);
            $this->assertGreaterThanOrEqual(1080, $dimensions[1]);
        }
        $bnsp = json_decode(file_get_contents(resource_path('data/lspglobal/bnsp-tuks.json')), true, 512, JSON_THROW_ON_ERROR)['entries'];
        $this->assertCount(44, $bnsp);
        $this->assertEqualsCanonicalizing(array_column($bnsp, 'code'), Tuk::where('is_active', true)->pluck('code')->all());
        $this->assertFalse(Tuk::where('code', 'TUK-001/2022')->firstOrFail()->is_active);
        $this->assertSame(38, Tuk::whereNotNull('latitude')->count());
        $this->assertSame(38, Tuk::whereNotNull('longitude')->count());
        $this->assertSame(38, Tuk::whereNotNull('maps_url')->count());
        $this->assertNull(Tuk::where('code', 'TUK-029/2024')->firstOrFail()->maps_url);
        foreach (Tuk::all() as $tuk) {
            $entry = $enrichment[$tuk->code];
            $this->assertSame($entry['is_active'], $tuk->is_active);
            if (($entry['logo_status'] ?? null) === 'unverified') {
                $this->assertNull($tuk->logo_path);
            }
            $this->assertSame($entry['latitude'], $tuk->latitude);
            $this->assertSame($entry['longitude'], $tuk->longitude);
            $this->assertSame($entry['maps_url'], $tuk->maps_url);
            if ($tuk->latitude === null) {
                $this->assertNull($tuk->longitude);
                $this->assertNull($tuk->maps_url);

                continue;
            }
            $this->assertGreaterThanOrEqual(-90, $tuk->latitude);
            $this->assertLessThanOrEqual(90, $tuk->latitude);
            $this->assertGreaterThanOrEqual(-180, $tuk->longitude);
            $this->assertLessThanOrEqual(180, $tuk->longitude);
            parse_str(parse_url($tuk->maps_url, PHP_URL_QUERY), $query);
            $this->assertLessThanOrEqual(255, strlen($tuk->maps_url));
            $this->assertSame('1', $query['api']);
            $this->assertSame($tuk->latitude.','.$tuk->longitude, $query['query']);
            if ($entry['location_match'] !== 'published_plus_code') {
                $this->assertNotEmpty($query['query_place_id']);
            }
        }
        $this->assertSame(0, Assessor::whereNotNull('expires_on')->count());
        $this->assertSame(0, Assessor::whereNull('certificate')->count());
        $this->assertSame(82, Assessor::distinct()->count('certificate'));
        Storage::disk('public')->assertExists($existing->thumbnail);
        $this->artisan('content:import-lspglobal')->assertSuccessful();
        $this->assertDatabaseCount('tuks', 45);
        $this->assertDatabaseCount('certification_schemes', 39);
        $this->assertDatabaseCount('assessors', 82);
        $this->getJson('/api/content-directory')->assertOk()->assertJsonCount(44, 'data.tuks')->assertJsonCount(39, 'data.schemes')->assertJsonCount(82, 'data.assessors');
    }

    public function test_verified_met_correction_resolves_the_source_conflict_and_unknown_expiry_remains_editable(): void
    {
        Storage::fake('public');
        $this->artisan('content:import-lspglobal')->assertSuccessful();
        $assessor = Assessor::where('name', 'Bowo Witanto')->firstOrFail();
        $this->assertSame('MET.000.000128 2023', $assessor->certificate);
        $this->assertNull($assessor->import_notes);
        $anang = Assessor::where('name', 'Prof. Dr. Anang Kurnia S.Si. M.Si')->firstOrFail();
        $this->assertSame('MET.000.008457 2023', $anang->certificate);
        $this->assertStringContainsString('BNSP', $anang->import_notes);
        $this->assertSame('Belum diketahui', $assessor->expiryStatus());
        $this->assertNull($assessor->graceEndsOn());
        $this->assertFalse(Assessor::query()->critical()->whereKey($assessor)->exists());
        $this->assertTrue(Assessor::query()->withExpiryStatus('Belum diketahui')->whereKey($assessor)->exists());
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageAssessors::class)->assertSee('Belum diketahui')
            ->callAction(TestAction::make('edit')->table($assessor), ['name' => $assessor->name, 'expires_on' => null])->assertHasNoActionErrors();
        $this->assertNull($assessor->refresh()->expires_on);
    }
}
