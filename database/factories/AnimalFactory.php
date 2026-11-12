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
            'breed' => fake()->word(),
            'size' => fake()->randomElement(['Small', 'Medium', 'Large']),
            'special_needs' => fake()->optional()->sentence(),
            'aggression' => fake()->optional()->randomElement(['Nenhuma', 'Baixa', 'Média', 'Alta']),
            'medication' => fake()->optional()->sentence(),
            'sex' => fake()->optional()->randomElement(['Macho', 'Fêmea']),
            'photo' => null,
            'weight' => fake()->optional()->randomFloat(2, 0.5, 50.0),
        ];
    }
}
