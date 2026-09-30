<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationPositionFactory extends Factory
{
    public function definition(): array
    {
        return ['parent_id' => null, 'title' => fake()->jobTitle(), 'person_name' => fake()->name(), 'credentials' => null, 'order' => 1, 'is_active' => true];
    }
}
