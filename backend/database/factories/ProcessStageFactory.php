<?php

namespace Database\Factories;

use App\Models\Process;
use App\Models\ProcessStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessStageFactory extends Factory
{
    protected $model = ProcessStage::class;

    public function definition(): array
    {
        return ['process_id' => Process::factory(), 'title' => fake()->sentence(2), 'order' => 1, 'is_active' => true];
    }
}
