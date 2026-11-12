<?php


/**
 * Factory para gerar dados fictícios de petsitters nos testes. Cria perfis com biografia, experiência e preço aleatórios.
 * Factory to generate fake petsitter data in tests. Creates profiles with random biography, experience, and price.
 */
namespace Database\Factories;

use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Petsitter>
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
            'profile_photo' => null,
            'short_bio' => fake()->sentence(6),
            'age' => fake()->numberBetween(18, 90),
            'experience' => fake()->sentence(),
            'service_location' => fake()->city(),
            'city' => fake()->city(),
            'price_per_day' => fake()->randomFloat(2, 10, 100),
            'average_rating' => null,
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
