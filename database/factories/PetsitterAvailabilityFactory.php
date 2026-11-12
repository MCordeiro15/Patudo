<?php


/**
 * Factory para gerar dados fictícios de disponibilidade de petsitter nos testes.
 * Factory to generate fake petsitter availability data in tests.
 */
namespace Database\Factories;

use App\Models\Petsitter;
use App\Models\PetsitterAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetsitterAvailability>
 * @extends Factory<PetsitterAvailability>
 */
class PetsitterAvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petsitter_id' => Petsitter::factory(),
            'date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
