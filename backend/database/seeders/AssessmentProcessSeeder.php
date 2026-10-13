<?php

namespace Database\Seeders;

use App\Models\Process;
use App\Models\ProcessConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentProcessSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(resource_path('data/assessment-process.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data): void {
            if (Process::query()->where('slug', $data['slug'])->exists()) {
                return;
            }
            $process = Process::create(['title' => $data['title'], 'slug' => $data['slug'], 'description' => $data['description'], 'order' => 1, 'is_active' => true]);
            $actors = [];
            $steps = [];
            foreach ($data['actors'] as $i => $actor) {
                $actors[$actor['name']] = $process->actors()->create([...$actor, 'order' => $i + 1, 'is_active' => true])->id;
            }
            foreach ($data['stages'] as $i => $row) {
                $stage = $process->stages()->create(['title' => $row['title'], 'description' => $row['description'] ?? null, 'order' => $i + 1, 'is_active' => true]);
                foreach ($row['steps'] as $j => $step) {
                    $steps[$step['key']] = $stage->steps()->create(['actor_id' => $step['actor'] ? $actors[$step['actor']] : null, 'title' => $step['title'], 'description' => $step['description'], 'document_reference' => $step['document_reference'], 'type' => $step['type'], 'order' => $j + 1, 'is_active' => true])->id;
                }
            }
            foreach ($data['connections'] as $i => $edge) {
                ProcessConnection::create(['process_id' => $process->id, 'source_step_id' => $steps[$edge['source']], 'target_step_id' => $steps[$edge['target']], 'label' => $edge['label'] ?? '', 'type' => $edge['type'], 'order' => $i + 1]);
            }
        });
    }
}
