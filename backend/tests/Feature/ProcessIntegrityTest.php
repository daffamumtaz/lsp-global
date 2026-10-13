<?php

namespace Tests\Feature;

use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProcessIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_graph_supports_branching_and_revision_cycles(): void
    {
        $process = Process::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $process->id]);
        $actor = ProcessActor::factory()->create(['process_id' => $process->id]);
        $decision = ProcessStep::factory()->create(['stage_id' => $stage->id, 'actor_id' => $actor->id, 'type' => 'decision']);
        $pass = ProcessStep::factory()->create(['stage_id' => $stage->id, 'type' => 'outcome']);
        $revision = ProcessStep::factory()->create(['stage_id' => $stage->id]);
        foreach ([$pass, $revision] as $step) {
            ProcessConnection::factory()->create(['process_id' => $process->id, 'source_step_id' => $decision->id, 'target_step_id' => $step->id]);
        }
        ProcessConnection::factory()->create(['process_id' => $process->id, 'source_step_id' => $revision->id, 'target_step_id' => $decision->id, 'type' => 'revision']);
        $this->assertCount(2, $decision->outgoingConnections);
        $this->assertCount(3, $process->connections);
        $this->assertSame($actor->id, $decision->actor->id);
        $this->assertCount(3, $process->steps);
    }

    public function test_cross_process_actor_is_rejected(): void
    {
        $stage = ProcessStage::factory()->create();
        $actor = ProcessActor::factory()->create();
        $this->expectException(ValidationException::class);
        ProcessStep::factory()->create(['stage_id' => $stage->id, 'actor_id' => $actor->id]);
    }

    public function test_cross_process_connection_is_rejected(): void
    {
        $source = ProcessStep::factory()->create();
        $target = ProcessStep::factory()->create();
        $this->expectException(ValidationException::class);
        ProcessConnection::factory()->create(['process_id' => $source->stage->process_id, 'source_step_id' => $source->id, 'target_step_id' => $target->id]);
    }

    public function test_activities_move_only_inside_the_same_process(): void
    {
        $step = ProcessStep::factory()->create();
        $stage = ProcessStage::factory()->create(['process_id' => $step->stage->process_id]);
        $step->update(['stage_id' => $stage->id]);
        $this->assertSame($stage->id, $step->refresh()->stage_id);
        $this->expectException(ValidationException::class);
        $step->update(['stage_id' => ProcessStage::factory()->create()->id]);
    }

    public function test_duplicate_connections_and_self_connections_are_rejected(): void
    {
        $edge = ProcessConnection::factory()->create();
        try {
            ProcessConnection::factory()->create($edge->only(['process_id', 'source_step_id', 'target_step_id', 'label', 'type']));
            $this->fail('Duplicate edge saved');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('target_step_id', $error->errors());
        }
        ProcessConnection::factory()->create([...$edge->only(['process_id', 'source_step_id', 'target_step_id']), 'label' => 'Cabang lain']);
        $this->expectException(ValidationException::class);
        $edge->update(['target_step_id' => $edge->source_step_id]);
    }

    public function test_delete_cascades_edges_and_actor_becomes_unassigned(): void
    {
        $edge = ProcessConnection::factory()->create();
        $actor = ProcessActor::factory()->create(['process_id' => $edge->process_id]);
        $edge->sourceStep->update(['actor_id' => $actor->id]);
        $actor->delete();
        $this->assertNull($edge->sourceStep->refresh()->actor_id);
        $edge->targetStep->delete();
        $this->assertModelMissing($edge);
        $edge->process->delete();
        $this->assertDatabaseCount('process_steps', 0);
    }

    public function test_stage_and_actor_process_ownership_is_immutable(): void
    {
        $stage = ProcessStage::factory()->create();
        $this->expectException(ValidationException::class);
        $stage->update(['process_id' => Process::factory()->create()->id]);
    }

    public function test_reordering_rejects_partial_and_foreign_ids_without_changes(): void
    {
        $stage = ProcessStage::factory()->create();
        $a = ProcessStep::factory()->create(['stage_id' => $stage->id, 'order' => 1]);
        $b = ProcessStep::factory()->create(['stage_id' => $stage->id, 'order' => 2]);
        foreach ([[$b->id], [$b->id, ProcessStep::factory()->create()->id], [$b->id, $b->id]] as $ids) {
            try {
                ProcessStep::reorderGroup(['stage_id' => $stage->id, 'actor_id' => null], $ids);
                $this->fail('Invalid reorder saved');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('order', $error->errors());
            }
        }
        $this->assertSame(1, $a->refresh()->order);
        ProcessStep::reorderGroup(['stage_id' => $stage->id, 'actor_id' => null], [$b->id, $a->id]);
        $this->assertSame(1, $b->refresh()->order);
        $this->assertSame(2, $a->refresh()->order);
    }

    public function test_database_rejects_cross_process_actor(): void
    {
        $step = ProcessStep::factory()->create();
        $actor = ProcessActor::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('process_steps')->where('id', $step->id)->update(['actor_id' => $actor->id]);
    }

    public function test_database_rejects_moving_a_connected_step_to_another_process(): void
    {
        $edge = ProcessConnection::factory()->create();
        $stage = ProcessStage::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('process_steps')->where('id', $edge->source_step_id)->update(['stage_id' => $stage->id]);
    }

    public function test_database_rejects_reparenting_a_stage(): void
    {
        $stage = ProcessStage::factory()->create();
        $other = Process::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('process_stages')->where('id', $stage->id)->update(['process_id' => $other->id]);
    }
}
