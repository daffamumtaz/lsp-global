<?php

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitePage>
 */
class SitePageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => 'landing',
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(),
            'body' => fake()->paragraph(),
        ];
    }
}
