<?php

namespace Database\Factories;

use App\Models\Process;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessConnectionFactory extends Factory
{
    protected $model = ProcessConnection::class;

    public function definition(): array
    {
        return ['process_id' => Process::factory(),
            'source_step_id' => fn (array $a): int => ProcessStep::factory()->create(['stage_id' => ProcessStage::factory()->create(['process_id' => $a['process_id']])->id])->id,
            'target_step_id' => fn (array $a): int => ProcessStep::factory()->create(['stage_id' => ProcessStep::findOrFail($a['source_step_id'])->stage_id])->id,
            'label' => '', 'type' => 'normal', 'order' => 1];
    }
}
