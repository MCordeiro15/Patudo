<?php


/**
 * Factory para gerar dados fictícios de avaliações nos testes. Cria reviews com nota e comentário aleatórios.
 * Factory to generate fake review data in tests. Creates reviews with random ratings and comments.
 */
namespace Database\Factories;

use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'petsitter_id' => Petsitter::factory(),
            'star_rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->paragraph(),
            'visible' => true,
        ];
    }

    /**
     * Indicate that the review has a high rating.
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'star_rating' => fake()->numberBetween(4, 5),
        ]);
    }

    /**
     * Indicate that the review is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'visible' => false,
        ]);
    }
}
