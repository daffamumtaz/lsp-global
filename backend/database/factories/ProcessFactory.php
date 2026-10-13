<?php

namespace Database\Factories;

use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'slug' => fake()->unique()->slug(), 'order' => 1, 'is_active' => true];
    }
}
