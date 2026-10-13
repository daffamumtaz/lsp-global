<?php

namespace Tests\Feature;

use App\Filament\AdminPreview;
use App\Filament\Resources\OrganizationPositions\Pages\ManageOrganizationPositions;
use App\Filament\Resources\Processes\Pages\EditProcess;
use App\Filament\Resources\Processes\RelationManagers\StepsRelationManager;
use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Models\OrganizationPosition;
use App\Models\Process;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use App\Models\Tuk;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_tuk_preview_uses_draft_coordinates_without_saving_and_cancel_restores_data(): void
    {
        $tuk = Tuk::factory()->create(['name' => 'TUK Tersimpan', 'latitude' => 0, 'longitude' => 0]);
        $editor = Livewire::test(ManageTuks::class)->mountAction(TestAction::make('edit')->table($tuk))
            ->set('mountedActions.0.data.name', 'TUK Draft')->set('mountedActions.0.data.latitude', '-7.2')->set('mountedActions.0.data.longitude', '112.7')
            ->call('updatePreview')->assertDispatched('admin-preview', fn ($event, $params): bool => $params['payload']['draft'] && collect($params['payload']['data'])->contains(fn ($row): bool => $row['name'] === 'TUK Draft' && $row['latitude'] === -7.2));
        $this->assertSame('TUK Tersimpan', $tuk->refresh()->name);
        $this->assertSame(0.0, $tuk->latitude);
        $editor->call('unmountAction')->assertDispatched('admin-preview', fn ($event, $params): bool => ! $params['payload']['draft']);
    }

    public function test_management_page_preview_refreshes_saved_data_without_a_modal(): void
    {
        Livewire::test(ManageTuks::class)->call('updatePreview')->assertSet('previewError', '')->assertDispatched('admin-preview', fn ($event, $params): bool => ! $params['payload']['draft']);
        Livewire::test(ManageOrganizationPositions::class)->call('updatePreview')->assertSet('previewError', '')->assertDispatched('admin-preview');
    }

    public function test_draft_logo_removal_does_not_change_the_saved_logo(): void
    {
        $tuk = Tuk::factory()->create(['logo_path' => 'tuks/logo.png']);
        $payload = AdminPreview::tuks($tuk->id, ['logo_path' => [], 'latitude' => null, 'longitude' => null]);
        $this->assertSame('', collect($payload)->firstWhere('id', (string) $tuk->id)['logo_url']);
        $this->assertSame('tuks/logo.png', $tuk->refresh()->logo_path);
    }

    public function test_incomplete_coordinates_keep_last_preview_and_show_error(): void
    {
        $editor = Livewire::test(ManageTuks::class)->mountAction('create')->set('mountedActions.0.data.name', 'Baru')->set('mountedActions.0.data.latitude', -7)->set('mountedActions.0.data.longitude', null)->call('updatePreview');
        $this->assertNotEmpty($editor->instance()->previewError);
        $editor->assertNotDispatched('admin-preview');
        $this->assertDatabaseMissing('tuks', ['name' => 'Baru']);
    }

    public function test_organization_preview_contains_new_node_and_inactive_nodes_without_writes(): void
    {
        $count = OrganizationPosition::count();
        $parent = OrganizationPosition::factory()->create(['is_active' => false]);
        $payload = AdminPreview::organization(null, ['title' => 'Draft Jabatan', 'members' => [['person_name' => 'Nama Draft', 'credentials' => null]], 'parent_id' => $parent->id, 'order' => 1, 'is_active' => true]);
        $this->assertCount($count + 2, $payload);
        $this->assertFalse(collect($payload)->firstWhere('id', $parent->id)['is_active']);
        $this->assertSame('Nama Draft', collect($payload)->last()['person_name']);
        $this->assertGreaterThan($parent->id, collect($payload)->last()['id']);
        $this->assertDatabaseCount('organization_positions', $count + 1);
        Livewire::test(ManageOrganizationPositions::class)->assertSee('/admin-preview/organization');
    }

    public function test_process_relation_preview_combines_unsaved_header_and_restores_after_cancel(): void
    {
        $process = Process::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $process->id]);
        $step = ProcessStep::factory()->create(['stage_id' => $stage->id]);
        $relation = Livewire::test(StepsRelationManager::class, ['ownerRecord' => $process, 'pageClass' => EditProcess::class])
            ->mountAction(TestAction::make('edit')->table($step))->set('mountedActions.0.data.title', 'Aktivitas Draft')->call('updatePreview')->assertDispatched('process-preview-draft');
        $page = Livewire::test(EditProcess::class, ['record' => $process->id])
            ->fillForm(['title' => 'Header Draft'])->call('previewRelation', $process->id, 'child', 'steps', $step->id, ['title' => 'Aktivitas Draft', 'stage_id' => (string) $stage->id, 'actor_id' => null])
            ->assertDispatched('admin-preview', fn ($event, $params): bool => $params['payload']['draft'] && $params['payload']['data']['title'] === 'Header Draft' && $params['payload']['data']['stages'][0]['steps'][0]['title'] === 'Aktivitas Draft');
        $this->assertNotSame('Aktivitas Draft', $step->refresh()->title);
        $relation->call('unmountAction')->assertDispatched('process-preview-draft', fn ($event, $params): bool => ! isset($params['draft']));
        $page->call('previewRelation', $process->id, 'child')->assertDispatched('admin-preview', fn ($event, $params): bool => ! $params['payload']['draft'] && $params['payload']['data']['title'] === $process->title);
    }

    public function test_saving_tuk_refreshes_saved_preview(): void
    {
        $tuk = Tuk::factory()->create();
        Livewire::test(ManageTuks::class)->callAction(TestAction::make('edit')->table($tuk), ['name' => 'TUK Disimpan', 'latitude' => -7.2, 'longitude' => 112.7])->assertHasNoActionErrors()
            ->assertDispatched('admin-preview', fn ($event, $params): bool => ! $params['payload']['draft'] && collect($params['payload']['data'])->contains(fn ($row): bool => $row['name'] === 'TUK Disimpan' && $row['longitude'] === 112.7));
        $this->assertSame(-7.2, $tuk->refresh()->latitude);
    }

    public function test_process_draft_can_move_step_without_saving_or_losing_connections(): void
    {
        $process = Process::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $process->id]);
        $target = ProcessStage::factory()->create(['process_id' => $process->id]);
        $step = ProcessStep::factory()->create(['stage_id' => $stage->id]);
        $otherStep = ProcessStep::factory()->create(['stage_id' => $target->id]);
        $connection = ProcessConnection::factory()->create(['process_id' => $process->id, 'source_step_id' => $step->id, 'target_step_id' => $otherStep->id]);
        $payload = AdminPreview::process($process, ['title' => 'Judul Draft'], 'steps', $step->id, ['title' => 'Aktivitas Draft', 'stage_id' => $target->id, 'actor_id' => null]);
        $this->assertSame('Judul Draft', $payload['title']);
        $this->assertSame($connection->id, $payload['connections'][0]['id']);
        $this->assertEmpty(collect($payload['stages'])->firstWhere('id', $stage->id)['steps']);
        $this->assertSame('Aktivitas Draft', collect(collect($payload['stages'])->firstWhere('id', $target->id)['steps'])->firstWhere('id', $step->id)['title']);
        $this->assertSame($stage->id, $step->refresh()->stage_id);
        $this->assertNotSame('Judul Draft', $process->refresh()->title);
    }
}
