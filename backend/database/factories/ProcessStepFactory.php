<?php

namespace Database\Factories;

use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessStepFactory extends Factory
{
    protected $model = ProcessStep::class;

    public function definition(): array
    {
        return ['stage_id' => ProcessStage::factory(), 'actor_id' => null, 'title' => fake()->sentence(3), 'type' => 'normal', 'order' => 1, 'is_active' => true];
    }
}
