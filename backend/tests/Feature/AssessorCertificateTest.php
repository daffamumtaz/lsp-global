<?php

namespace Tests\Feature;

use App\Filament\Resources\Assessors\Pages\ManageAssessors;
use App\Models\Assessor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessorCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_create_defaults_use_jakarta_year_for_both_create_actions(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-12-31 18:00:00', 'UTC'));

        foreach (['create', TestAction::make('create')->table()] as $action) {
            Livewire::test(ManageAssessors::class)->mountAction($action)
                ->assertActionDataSet(['certificate_number' => null, 'certificate_year' => '2027', 'expires_on' => '2030-01-01'])
                ->assertSchemaComponentVisible('certificate_number', 'mountedActionSchema0')
                ->assertSchemaComponentVisible('certificate_year', 'mountedActionSchema0');
        }
    }

    public function test_create_preserves_leading_zeroes_and_uses_editable_year(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05', 'Asia/Jakarta'));

        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Asesor Sertifikat', 'certificate_number' => '002531', 'certificate_year' => '2024',
        ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('assessors', ['name' => 'Asesor Sertifikat', 'certificate' => 'MET.000.002531 2024']);
        $this->assertSame('2029-10-05', Assessor::query()->where('name', 'Asesor Sertifikat')->firstOrFail()->expires_on->toDateString());
    }

    public function test_short_number_uses_default_year_and_takes_precedence_over_legacy_input(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05', 'Asia/Jakarta'));

        Livewire::test(ManageAssessors::class)->callAction(TestAction::make('create')->table(), [
            'name' => 'Nomor Pendek', 'certificate_number' => '07', 'certificate' => 'MET-OLD',
        ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('assessors', ['name' => 'Nomor Pendek', 'certificate' => 'MET.000.07 2026']);
    }

    public function test_blank_middle_saves_null_instead_of_an_incomplete_template(): void
    {
        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Tanpa Sertifikat', 'certificate_number' => '', 'certificate_year' => '2026',
        ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('assessors', ['name' => 'Tanpa Sertifikat', 'certificate' => null]);
    }

    public function test_invalid_number_year_and_total_length_cannot_be_saved(): void
    {
        foreach ([
            ['certificate_number' => '12A', 'certificate_year' => '2026', 'error' => 'certificate_number'],
            ['certificate_number' => '12.3', 'certificate_year' => '2026', 'error' => 'certificate_number'],
            ['certificate_number' => '123', 'certificate_year' => '', 'error' => 'certificate_year'],
            ['certificate_number' => '123', 'certificate_year' => '026', 'error' => 'certificate_year'],
            ['certificate_number' => '123', 'certificate_year' => '20267', 'error' => 'certificate_year'],
            ['certificate_number' => '123', 'certificate_year' => '20AB', 'error' => 'certificate_year'],
            ['certificate_number' => str_repeat('1', 243), 'certificate_year' => '2026', 'error' => 'certificate_number'],
        ] as $case) {
            Livewire::test(ManageAssessors::class)->callAction('create', [
                'name' => 'Invalid', 'certificate_number' => $case['certificate_number'], 'certificate_year' => $case['certificate_year'],
            ])->assertHasActionErrors([$case['error']]);
        }

        $this->assertDatabaseCount('assessors', 0);
    }

    public function test_full_certificate_at_the_existing_length_limit_is_allowed(): void
    {
        $number = str_repeat('0', 242);

        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Batas Panjang', 'certificate_number' => $number, 'certificate_year' => '2026',
        ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('assessors', ['name' => 'Batas Panjang', 'certificate' => 'MET.000.'.$number.' 2026']);
    }

    public function test_full_certificate_length_error_is_available_on_the_visible_number(): void
    {
        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Terlalu Panjang', 'certificate_number' => str_repeat('0', 243), 'certificate_year' => '2026',
        ])->assertHasActionErrors(['certificate' => 'max', 'certificate_number' => 'max'])
            ->assertSchemaComponentVisible('certificate_number', 'mountedActionSchema0');

        $this->assertDatabaseCount('assessors', 0);
    }

    public function test_duplicate_full_certificate_is_rejected_but_different_year_is_allowed(): void
    {
        Assessor::factory()->create(['certificate' => 'MET.000.002531 2026']);

        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Duplikat', 'certificate_number' => '002531', 'certificate_year' => '2026',
        ])->assertHasActionErrors(['certificate' => 'unique', 'certificate_number'])
            ->assertSchemaComponentVisible('certificate_number', 'mountedActionSchema0');
        $this->assertDatabaseCount('assessors', 1);

        Livewire::test(ManageAssessors::class)->callAction('create', [
            'name' => 'Tahun Berbeda', 'certificate_number' => '002531', 'certificate_year' => '2025',
        ])->assertHasNoActionErrors();
        $this->assertDatabaseHas('assessors', ['name' => 'Tahun Berbeda', 'certificate' => 'MET.000.002531 2025']);
    }

    public function test_matching_certificate_edit_hydrates_parts_preserves_it_and_can_change_or_clear_it(): void
    {
        $assessor = Assessor::factory()->create(['certificate' => 'MET.000.002531 2024']);
        $action = TestAction::make('edit')->table($assessor);

        Livewire::test(ManageAssessors::class)->mountAction($action)
            ->assertActionDataSet(['certificate_number' => '002531', 'certificate_year' => '2024'])
            ->set('mountedActions.0.data.field', 'TIK')->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('MET.000.002531 2024', $assessor->refresh()->certificate);

        Livewire::test(ManageAssessors::class)->callAction($action, ['certificate_number' => '00001', 'certificate_year' => '2023'])->assertHasNoActionErrors();
        $this->assertSame('MET.000.00001 2023', $assessor->refresh()->certificate);

        Livewire::test(ManageAssessors::class)->callAction($action, ['certificate_number' => ''])->assertHasNoActionErrors();
        $this->assertNull($assessor->refresh()->certificate);
    }

    public function test_legacy_certificate_remains_editable_and_unchanged_on_unrelated_edit(): void
    {
        $assessor = Assessor::factory()->create(['certificate' => 'MET-001']);
        $action = TestAction::make('edit')->table($assessor);

        Livewire::test(ManageAssessors::class)->mountAction($action)
            ->assertActionDataSet(['certificate' => 'MET-001'])
            ->assertSchemaComponentVisible('certificate', 'mountedActionSchema0')
            ->assertSchemaComponentHidden('certificate_number', 'mountedActionSchema0')
            ->assertSchemaComponentHidden('certificate_year', 'mountedActionSchema0')
            ->set('mountedActions.0.data.field', 'TIK')->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('MET-001', $assessor->refresh()->certificate);

        Livewire::test(ManageAssessors::class)->callAction($action, ['certificate' => 'MET-002'])->assertHasNoActionErrors();
        $this->assertSame('MET-002', $assessor->refresh()->certificate);
    }

    public function test_duplicate_legacy_certificate_error_stays_on_the_visible_full_text_field(): void
    {
        Assessor::factory()->create(['certificate' => 'MET-001']);
        $assessor = Assessor::factory()->create(['certificate' => 'MET-002']);

        Livewire::test(ManageAssessors::class)->callAction(TestAction::make('edit')->table($assessor), [
            'certificate' => 'MET-001',
        ])->assertHasActionErrors(['certificate' => 'unique'])->assertHasNoActionErrors(['certificate_number'])
            ->assertSchemaComponentVisible('certificate', 'mountedActionSchema0');

        $this->assertSame('MET-002', $assessor->refresh()->certificate);
    }
}
