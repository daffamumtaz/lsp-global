<?php

namespace Tests\Feature;

use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_deletion_records_actor_and_cascade_snapshot_once(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $edge = ProcessConnection::factory()->create();
        $process = $edge->process;
        $process->delete();
        $logs = Activity::query()->where('subject_type', $process::class)->where('subject_id', $process->id)->where('event', 'deleted')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($admin->id, $logs[0]->causer_id);
        $this->assertSame([$edge->id], $logs[0]->properties['cascade']['connection_ids']);
    }

    public function test_actor_deletion_records_steps_that_become_unassigned(): void
    {
        $edge = ProcessConnection::factory()->create();
        $actor = ProcessActor::factory()->create(['process_id' => $edge->process_id]);
        $edge->sourceStep->update(['actor_id' => $actor->id]);
        $actor->delete();
        $log = Activity::query()->where('subject_type', $actor::class)->where('subject_id', $actor->id)->where('event', 'deleted')->firstOrFail();
        $this->assertSame([$edge->source_step_id], $log->properties['cascade']['unassigned_step_ids']);
    }

    public function test_rolled_back_process_change_leaves_no_success_log(): void
    {
        $process = Process::factory()->create();
        $count = Activity::query()->count();
        DB::beginTransaction();
        $process->update(['title' => 'Rolled back']);
        DB::rollBack();
        $this->assertSame($count, Activity::query()->count());
        $this->assertNotSame('Rolled back', $process->refresh()->title);
    }

    public function test_history_is_available_to_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/history')
            ->assertOk()->assertDontSee('Halaman dalam Pengembangan');
    }
}
