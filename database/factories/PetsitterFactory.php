<?php

namespace Database\Factories;

use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Petsitter>
 */
class PetsitterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'biography' => fake()->paragraph(),
            'experience' => fake()->sentence(),
            'service_location' => fake()->city(),
            'price_per_day' => fake()->randomFloat(2, 10, 100),
            'accepted_sizes' => ['small', 'medium'],
            'accepted_animal_types' => ['dog', 'cat'],
            'approval_status' => 'pending',
            'identification_documents' => 'doc_'.fake()->uuid().'.pdf',
        ];
    }

    /**
     * Indicate that the petsitter is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
        ]);
    }

    /**
     * Indicate that the petsitter is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
        ]);
    }
}
