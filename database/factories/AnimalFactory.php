<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
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
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(['Dog', 'Cat', 'Bird', 'Rabbit']),
            'size' => fake()->randomElement(['Small', 'Medium', 'Large']),
            'special_needs' => fake()->optional()->sentence(),
        ];
    }
}
