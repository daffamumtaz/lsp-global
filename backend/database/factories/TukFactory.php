<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TukFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'is_active' => true];
    }
}
