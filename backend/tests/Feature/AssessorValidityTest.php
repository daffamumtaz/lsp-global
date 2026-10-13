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

class AssessorValidityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_validity_can_be_edited_without_hiding_expired_assessors(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 00:30:00', 'Asia/Jakarta'));
        $assessor = Assessor::factory()->create();
        $this->assertSame('2029-10-02', $assessor->expires_on->toDateString());
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageAssessors::class)->callAction(TestAction::make('edit')->table($assessor), [
            'name' => $assessor->name, 'is_active' => true, 'expires_on' => '2025-01-01',
        ])->assertHasNoActionErrors();
        $this->assertSame('Kedaluwarsa', $assessor->refresh()->expiryStatus());
        $this->getJson('/api/content-directory')->assertJsonCount(1, 'data.assessors')
            ->assertJsonMissingPath('data.assessors.0.expires_on')->assertJsonMissingPath('data.assessors.0.expiry_status');
        Livewire::test(ManageAssessors::class)->assertSee('Asesor kritis');
    }

    public function test_migration_backfills_existing_records_without_changing_visibility(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 00:30:00', 'Asia/Jakarta'));
        $assessor = Assessor::factory()->create(['is_active' => false]);
        $migration = require database_path('migrations/2026_10_02_073806_add_expires_on_to_assessors_table.php');
        $migration->down();
        $migration->up();
        $this->assertSame('2029-10-02', $assessor->refresh()->expires_on->toDateString());
        $this->assertFalse($assessor->is_active);
    }

    public function test_status_and_filters_match_on_date_boundaries_and_month_ends(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 00:30:00', 'Asia/Jakarta'));
        foreach ([
            '2027-01-02' => 'Berlaku', '2026-12-31' => 'Hampir habis',
            '2026-10-02' => 'Hampir habis', '2026-10-01' => 'Masa tenggang',
            '2026-07-02' => 'Masa tenggang', '2026-07-01' => 'Kedaluwarsa',
        ] as $date => $status) {
            $assessor = Assessor::factory()->create(['expires_on' => $date]);
            $this->assertSame($status, $assessor->expiryStatus(), $date);
            $this->assertTrue(Assessor::query()->withExpiryStatus($status)->whereKey($assessor)->exists(), $date);
        }
        $this->assertSame(5, Assessor::query()->critical()->count());
        $this->travelTo(CarbonImmutable::parse('2027-02-28 12:00:00', 'Asia/Jakarta'));
        $assessor = Assessor::factory()->create(['expires_on' => '2026-11-30']);
        $this->assertSame('2027-02-28', $assessor->graceEndsOn()->toDateString());
        $this->assertSame('Masa tenggang', $assessor->expiryStatus());
        $this->assertTrue(Assessor::query()->withExpiryStatus('Masa tenggang')->whereKey($assessor)->exists());
        $this->travel(1)->days();
        $this->assertSame('Kedaluwarsa', $assessor->expiryStatus());
        $this->travelTo(CarbonImmutable::parse('2027-05-31 12:00:00', 'Asia/Jakarta'));
        $assessor->update(['expires_on' => '2027-02-28']);
        $this->assertSame('Kedaluwarsa', $assessor->expiryStatus());
        $this->assertTrue(Assessor::query()->withExpiryStatus('Kedaluwarsa')->whereKey($assessor->id)->exists());
    }
}
