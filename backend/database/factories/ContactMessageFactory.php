<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(3),
            'category' => 'Informasi Sertifikasi',
            'message' => fake()->paragraph(),
            'is_read' => false,
        ];
    }
}
