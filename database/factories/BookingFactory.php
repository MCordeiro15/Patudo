<?php


/**
 * Factory para gerar dados fictícios de reservas nos testes. Cria bookings com datas, estado e preço aleatórios.
 * Factory to generate fake booking data in tests. Creates bookings with random dates, status, and price.
 */
namespace Database\Factories;

use App\Models\Animal;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $petsitter = Petsitter::factory()->create();
        $user = User::factory()->create(['is_petsitter' => false]);

        $startDate = fake()->dateTimeBetween('+1 week', '+4 weeks');
        $endDate = (clone $startDate)->modify('+' . fake()->numberBetween(1, 7) . ' days');

        return [
            'user_id' => $user->id,
            'petsitter_id' => $petsitter->user_id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => fake()->randomElement(['pending', 'approved', 'completed', 'cancelled']),
            'total_price' => fake()->randomFloat(2, 20, 200),
        ];
    }

    /**
     * Indicate that the booking is completed (for reviews).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Create the booking with associated animals.
     */
    public function withAnimals(int $count = 1): static
    {
        return $this->afterCreating(function (Booking $booking) use ($count) {
            $animals = Animal::factory()->count($count)->create([
                'user_id' => $booking->user_id,
            ]);
            foreach ($animals as $animal) {
                \DB::table('animal_bookings')->insert([
                    'booking_id' => $booking->id,
                    'animal_id' => $animal->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
