<?php

namespace Database\Factories;

use App\Models\Process;
use App\Models\ProcessActor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessActorFactory extends Factory
{
    protected $model = ProcessActor::class;

    public function definition(): array
    {
        return ['process_id' => Process::factory(), 'name' => fake()->unique()->slug(2), 'label' => fake()->word(), 'order' => 1, 'is_active' => true];
    }
}
