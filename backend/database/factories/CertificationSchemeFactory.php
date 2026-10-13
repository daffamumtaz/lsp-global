<?php

namespace Database\Factories;

use App\Models\CertificationScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificationScheme>
 */
class CertificationSchemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
            'category' => 'Teknologi Informasi',
            'level' => 2,
            'slug' => fake()->unique()->slug(),
            'price' => 1000000,
            'is_active' => true,
        ];
    }
}
