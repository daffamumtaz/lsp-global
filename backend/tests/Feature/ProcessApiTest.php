<?php

namespace Tests\Feature;

use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Database\Seeders\AssessmentProcessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProcessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_and_active_process_listing_and_unknown_slug(): void
    {
        $this->getJson('/api/processes')->assertOk()->assertExactJson(['data' => []]);
        $hidden = Process::factory()->create(['is_active' => false]);
        $second = Process::factory()->create(['order' => 2]);
        $first = Process::factory()->create(['order' => 1]);
        $this->assertSame([$first->id, $second->id], array_column($this->getJson('/api/processes')->assertOk()->json('data'), 'id'));
        $this->getJson('/api/processes/'.$hidden->slug)->assertNotFound();
        $this->getJson('/api/processes/missing')->assertNotFound();
    }

    public function test_database_graph_is_semantic_sorted_and_pruned(): void
    {
        $edge = ProcessConnection::factory()->create(['label' => 'KOMPETEN', 'type' => 'success']);
        $process = $edge->process;
        $actor = ProcessActor::factory()->create(['process_id' => $process->id]);
        $edge->sourceStep->update(['actor_id' => $actor->id, 'description' => 'Baru dari CMS']);
        $result = $this->getJson('/api/processes/'.$process->slug)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame('Baru dari CMS', $result['stages'][0]['steps'][0]['description']);
        $this->assertSame('KOMPETEN', $result['connections'][0]['label']);
        $this->assertSame('success', $result['connections'][0]['type']);
        $this->assertSame($actor->id, $result['stages'][0]['steps'][0]['actorId']);
        $actor->update(['is_active' => false]);
        $result = $this->getJson('/api/processes/'.$process->slug)->assertOk()->json('data');
        $this->assertCount(1, $result['stages'][0]['steps']);
        $this->assertCount(0, $result['connections']);
        $edge->targetStep->stage->update(['is_active' => false]);
        $this->getJson('/api/processes/'.$process->slug)->assertOk()->assertJsonCount(0, 'data.stages');
    }

    public function test_preview_requires_unexpired_signature_and_includes_inactive(): void
    {
        $process = Process::factory()->create(['is_active' => false]);
        $stage = ProcessStage::factory()->create(['process_id' => $process->id, 'is_active' => false]);
        ProcessStep::factory()->create(['stage_id' => $stage->id, 'is_active' => false]);
        $this->getJson('/api/processes/'.$process->slug.'/preview')->assertForbidden();
        $url = URL::temporarySignedRoute('api.processes.preview', now()->addMinutes(10), ['slug' => $process->slug], absolute: false);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data.stages.0.steps');
        $this->getJson($url.'x')->assertForbidden();
        $this->travel(11)->minutes();
        $this->getJson($url)->assertForbidden();
    }

    public function test_seed_is_create_once_and_does_not_restore_admin_edits(): void
    {
        $this->seed(AssessmentProcessSeeder::class);
        $process = Process::query()->where('slug', 'asesmen-sertifikasi-jarak-jauh')->firstOrFail();
        $this->assertCount(6, $process->stages);
        $this->assertCount(0, $process->actors);
        $this->assertSame(0, $process->steps()->count());
        $stage = $process->stages()->firstOrFail();
        $stage->update(['description' => 'Narasi edit admin']);
        $process->update(['title' => 'Edit Admin']);
        $count = $process->steps()->count();
        $this->seed(AssessmentProcessSeeder::class);
        $this->assertSame($count, $process->steps()->count());
        $this->assertSame('Edit Admin', $process->refresh()->title);
        $this->assertSame('Narasi edit admin', $stage->refresh()->description);
    }

    public function test_stage_markdown_is_rendered_without_unsafe_html_or_links(): void
    {
        $stage = ProcessStage::factory()->create(['description' => "Persiapan **asesmen**.\n\n- Data diri\n- Dokumen\n\n<script>alert(1)</script>\n\n[tautan](javascript:alert%281%29)"]);
        $html = $this->getJson('/api/processes/'.$stage->process->slug)->assertOk()->json('data.stages.0.descriptionHtml');
        $this->assertStringContainsString('<strong>asesmen</strong>', $html);
        $this->assertStringContainsString('<li>Data diri</li>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_stepper_upgrade_reuses_existing_stages_and_leaves_other_processes_unchanged(): void
    {
        $process = Process::factory()->create(['slug' => 'asesmen-sertifikasi-jarak-jauh']);
        $stages = collect(range(1, 6))->map(fn ($order) => ProcessStage::factory()->create(['process_id' => $process->id, 'order' => $order]));
        $step = ProcessStep::factory()->create(['stage_id' => $stages->first()->id]);
        $other = Process::factory()->create();
        $migration = require database_path('migrations/2026_10_09_094001_replace_sjj_diagram_with_editable_stepper.php');
        $migration->up();
        $this->assertSame($stages->pluck('id')->all(), $process->stages()->pluck('id')->all());
        $this->assertSame('Pendaftaran Sertifikasi', $stages->first()->refresh()->title);
        $this->assertStringContainsString('kompeten', $stages->get(4)->refresh()->description);
        $this->assertSame($other->title, $other->refresh()->title);
        $this->assertModelExists($step);
    }

    public function test_api_does_not_issue_queries_per_step(): void
    {
        $stage = ProcessStage::factory()->create();
        ProcessStep::factory()->count(3)->create(['stage_id' => $stage->id]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson('/api/processes/'.$stage->process->slug)->assertOk();
        $small = count(DB::getQueryLog());
        ProcessStep::factory()->count(30)->create(['stage_id' => $stage->id]);
        DB::flushQueryLog();
        $this->getJson('/api/processes/'.$stage->process->slug)->assertOk();
        $large = count(DB::getQueryLog());
        $this->assertLessThanOrEqual($small + 1, $large);
        DB::disableQueryLog();
    }
}
