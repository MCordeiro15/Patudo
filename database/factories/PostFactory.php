<?php


/**
 * Factory para gerar dados fictícios de posts nos testes. Cria posts com título, descrição e fotos aleatórias.
 * Factory to generate fake post data in tests. Creates posts with random title, description, and photos.
 */
namespace Database\Factories;

use App\Models\Petsitter;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
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
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'photos' => null,
        ];
    }

    /**
     * Indicate that the post has photos.
     */
    public function withPhotos(): static
    {
        $photoCount = fake()->numberBetween(1, 5);
        $photos = [];
        for ($i = 0; $i < $photoCount; $i++) {
            $photos[] = 'posts/post_' . fake()->uuid() . '.jpg';
        }

        return $this->state(fn (array $attributes) => [
            'photos' => $photos,
        ]);
    }
}
