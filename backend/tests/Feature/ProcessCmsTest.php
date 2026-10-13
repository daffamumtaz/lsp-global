<?php

namespace Tests\Feature;

use App\Filament\Resources\Processes\Pages\EditProcess;
use App\Filament\Resources\Processes\RelationManagers\ActorsRelationManager;
use App\Filament\Resources\Processes\RelationManagers\ConnectionsRelationManager;
use App\Filament\Resources\Processes\RelationManagers\StagesRelationManager;
use App\Filament\Resources\Processes\RelationManagers\StepsRelationManager;
use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcessCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function editor(string $class, Process $process): mixed
    {
        return Livewire::test($class, ['ownerRecord' => $process, 'pageClass' => EditProcess::class]);
    }

    public function test_preview_action_generates_short_lived_signed_link_for_inactive_process(): void
    {
        config(['services.frontend.url' => 'http://localhost:3000']);
        $process = Process::factory()->create(['is_active' => false]);
        $editor = Livewire::test(EditProcess::class, ['record' => $process->id]);
        $url = $editor->instance()->getAction('preview')->getUrl();
        $this->assertStringStartsWith('http://localhost:3000/informasi/alur-asesmen/'.$process->slug.'/preview?', $url);
        $query = parse_url($url, PHP_URL_QUERY);
        $this->getJson('/api/processes/'.$process->slug.'/preview?'.$query)->assertOk();
        $this->travel(11)->minutes();
        $this->getJson('/api/processes/'.$process->slug.'/preview?'.$query)->assertForbidden();
    }

    public function test_guests_cannot_open_process_editor(): void
    {
        auth()->logout();
        $this->get('/admin/processes')->assertRedirect('/admin/login');
    }

    public function test_existing_process_can_be_edited_and_disabled_with_stable_slug(): void
    {
        $process = Process::factory()->create(['slug' => 'alur-uji']);
        Livewire::test(EditProcess::class, ['record' => $process->id])->fillForm(['title' => 'Alur Diubah', 'slug' => 'alamat-lain', 'is_active' => false])->call('save')->assertHasNoFormErrors();
        $this->assertFalse($process->refresh()->is_active);
        $this->assertSame('Alur Diubah', $process->title);
        $this->assertSame('alur-uji', $process->slug);
    }

    public function test_stage_and_actor_crud_and_scoped_reordering(): void
    {
        $process = Process::factory()->create();
        $modal = $this->editor(StagesRelationManager::class, $process)->mountAction(TestAction::make('create')->table());
        $this->assertSame('Create Tahap', $modal->instance()->getMountedAction()->getModalHeading());
        $this->editor(StagesRelationManager::class, $process)->callAction(TestAction::make('create')->table(), ['title' => 'Tahap Baru', 'description' => null, 'order' => 1, 'is_active' => true])->assertHasNoActionErrors();
        $stage = $process->stages()->firstOrFail();
        $this->editor(ActorsRelationManager::class, $process)->callAction(TestAction::make('create')->table(), ['name' => 'admin', 'label' => 'Admin', 'order' => 1, 'is_active' => true])->assertHasNoActionErrors();
        $actor = $process->actors()->firstOrFail();
        $this->editor(ActorsRelationManager::class, $process)->callAction(TestAction::make('edit')->table($actor), ['name' => 'admin', 'label' => 'Admin Baru', 'order' => 1, 'is_active' => false])->assertHasNoActionErrors();
        $this->assertSame('Admin Baru', $actor->refresh()->label);
        $this->assertFalse($actor->is_active);
        $second = ProcessStage::factory()->create(['process_id' => $process->id, 'order' => 2]);
        $this->editor(StagesRelationManager::class, $process)->call('reorderTable', [$second->id, $stage->id]);
        $this->assertSame(1, $second->refresh()->order);
        $this->editor(StagesRelationManager::class, $process)->callAction(TestAction::make('delete')->table($stage))->assertHasNoActionErrors();
        $this->assertModelMissing($stage);
    }

    public function test_stage_narrative_saved_in_filament_is_published_as_markdown(): void
    {
        $stage = ProcessStage::factory()->create();
        $narrative = "Ikuti petunjuk asesor.\n\n- **Kompeten:** penerbitan sertifikat.\n- **Belum kompeten:** tindak lanjut sesuai ketentuan LSP.";
        $this->editor(StagesRelationManager::class, $stage->process)
            ->callAction(TestAction::make('edit')->table($stage), ['title' => 'Narasi CMS', 'description' => $narrative, 'order' => 1, 'is_active' => true])
            ->assertHasNoActionErrors();
        $this->assertSame($narrative, $stage->refresh()->description);
        $response = $this->getJson('/api/processes/'.$stage->process->slug)->assertOk()->assertJsonPath('data.stages.0.title', 'Narasi CMS');
        $this->assertStringContainsString('<strong>Kompeten:</strong>', $response->json('data.stages.0.descriptionHtml'));
    }

    public function test_step_can_be_created_moved_edited_and_deleted(): void
    {
        $process = Process::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $process->id]);
        $actor = ProcessActor::factory()->create(['process_id' => $process->id]);
        $form = ['stage_id' => $stage->id, 'actor_id' => $actor->id, 'title' => 'Aktivitas Baru', 'type' => 'normal', 'order' => 1, 'is_active' => true, 'description' => 'Detail CMS', 'document_reference' => 'FR.APL.02', 'information_url' => null];
        $this->editor(StepsRelationManager::class, $process)->callAction(TestAction::make('create')->table(), $form)->assertHasNoActionErrors();
        $step = $process->steps()->firstOrFail();
        $other = ProcessStage::factory()->create(['process_id' => $process->id]);
        $this->editor(StepsRelationManager::class, $process)->callAction(TestAction::make('edit')->table($step), [...$form, 'stage_id' => $other->id, 'actor_id' => null, 'title' => 'Aktivitas Diubah', 'is_active' => false])->assertHasNoActionErrors();
        $this->assertSame($other->id, $step->refresh()->stage_id);
        $this->assertNull($step->actor_id);
        $this->assertFalse($step->is_active);
        $this->editor(StepsRelationManager::class, $process)->callAction(TestAction::make('delete')->table($step))->assertHasNoActionErrors();
        $this->assertModelMissing($step);
    }

    public function test_step_reorder_is_limited_to_selected_stage_and_actor(): void
    {
        $process = Process::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $process->id]);
        $actor = ProcessActor::factory()->create(['process_id' => $process->id]);
        $first = ProcessStep::factory()->create(['stage_id' => $stage->id, 'actor_id' => null, 'order' => 1]);
        $second = ProcessStep::factory()->create(['stage_id' => $stage->id, 'actor_id' => null, 'order' => 2]);
        $outside = ProcessStep::factory()->create(['stage_id' => $stage->id, 'actor_id' => $actor->id, 'order' => 9]);
        $this->editor(StepsRelationManager::class, $process)->call('reorderTable', [$second->id, $first->id])->assertHasErrors(['order']);
        $this->editor(StepsRelationManager::class, $process)->filterTable('stage', $stage->id)->filterTable('actor', '0')->call('reorderTable', [$second->id, $first->id]);
        $this->assertSame(1, $second->refresh()->order);
        $this->assertSame(2, $first->refresh()->order);
        $this->editor(StepsRelationManager::class, $process)->filterTable('stage', $stage->id)->filterTable('actor', '0')->call('reorderTable', [$first->id, $outside->id])->assertHasErrors(['order']);
        $this->assertSame(9, $outside->refresh()->order);
        $this->assertSame(1, $second->refresh()->order);
    }

    public function test_connections_can_be_created_changed_and_deleted(): void
    {
        $a = ProcessStep::factory()->create();
        $process = $a->stage->process;
        $b = ProcessStep::factory()->create(['stage_id' => $a->stage_id]);
        $form = ['source_step_id' => $a->id, 'target_step_id' => $b->id, 'label' => 'KOMPETEN', 'type' => 'success', 'order' => 1];
        $this->editor(ConnectionsRelationManager::class, $process)->callAction(TestAction::make('create')->table(), $form)->assertHasNoActionErrors();
        $edge = $process->connections()->firstOrFail();
        $this->editor(ConnectionsRelationManager::class, $process)->callAction(TestAction::make('edit')->table($edge), [...$form, 'label' => 'Ulang', 'type' => 'revision'])->assertHasNoActionErrors();
        $this->assertSame('Ulang', $edge->refresh()->label);
        $this->editor(ConnectionsRelationManager::class, $process)->callAction(TestAction::make('delete')->table($edge))->assertHasNoActionErrors();
        $this->assertModelMissing($edge);
    }

    public function test_activity_options_reject_foreign_stage(): void
    {
        $process = Process::factory()->create();
        $foreign = ProcessStage::factory()->create();
        $this->editor(StepsRelationManager::class, $process)->callAction(TestAction::make('create')->table(), ['stage_id' => $foreign->id, 'actor_id' => null, 'title' => 'Asing', 'type' => 'normal', 'order' => 1, 'is_active' => true])->assertHasActionErrors(['stage_id']);
        $this->assertDatabaseMissing('process_steps', ['title' => 'Asing']);
    }
}
