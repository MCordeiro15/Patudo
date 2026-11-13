<?php


/**
 * Seeder que preenche a base de dados com reservas e avaliações de exemplo para desenvolvimento e testes.
 * Seeder that fills the database with example bookings and reviews for development and testing.
 */
namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all approved petsitters
        $petsitters = Petsitter::where('approval_status', 'approved')->get();

        if ($petsitters->isEmpty()) {
            return;
        }

        // Create some regular users for bookings
        $bookingUsers = User::whereIn('email', [
            'client1@patudo.test',
            'client2@patudo.test',
            'client3@patudo.test',
        ])->get()->keyBy('email');

        // Create missing users
        if (!$bookingUsers->has('client1@patudo.test')) {
            $bookingUsers['client1@patudo.test'] = User::create([
                'name' => 'Ana Silva',
                'email' => 'client1@patudo.test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'phone' => '940000000',
            ]);
        }

        if (!$bookingUsers->has('client2@patudo.test')) {
            $bookingUsers['client2@patudo.test'] = User::create([
                'name' => 'Pedro Costa',
                'email' => 'client2@patudo.test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'phone' => '950000000',
            ]);
        }

        if (!$bookingUsers->has('client3@patudo.test')) {
            $bookingUsers['client3@patudo.test'] = User::create([
                'name' => 'Carla Mendes',
                'email' => 'client3@patudo.test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'phone' => '960000000',
            ]);
        }

        $users = $bookingUsers->values()->all();

        // Create animals for booking users
        foreach ($users as $index => $user) {
            $animalNames = ['Rex', 'Luna', 'Thor', 'Mimi', 'Bob'];
            $species = ['Dog', 'Cat', 'Dog', 'Cat', 'Bird'];
            $breeds = ['Golden Retriever', 'Siamês', 'Labrador', 'Persa', 'Papagaia'];
            $sizes = ['Large', 'Small', 'Medium', 'Small', 'Small'];
            $sexes = ['Macho', 'Fêmea', 'Macho', 'Fêmea', 'Macho'];

            Animal::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $animalNames[$index % count($animalNames)],
                ],
                [
                    'species' => $species[$index % count($species)],
                    'breed' => $breeds[$index % count($breeds)],
                    'size' => $sizes[$index % count($sizes)],
                    'sex' => $sexes[$index % count($sexes)],
                    'weight' => rand(2, 30) . '.00',
                ]
            );
        }

        // Create bookings with reviews
        $reviewComments = [
            'Excelente serviço! O meu animal foi muito bem cuidado. Recomendo!',
            'Muito atencioso e responsável. O meu cão adorou!',
            'Profissional e carinhoso. Voltarei a contratar!',
            'Ótima experiência! O petsitter foi pontual e muito cuidado.',
            'O meu gato foi muito bem cuidado. Muito obrigado!',
        ];

        foreach ($petsitters as $petsitterIndex => $petsitter) {
            $user = $users[$petsitterIndex % count($users)];
            $animal = Animal::where('user_id', $user->id)->first();

            if (!$animal) {
                continue;
            }

            // Create a completed booking
            $startDate = now()->subDays(rand(10, 30));
            $endDate = $startDate->copy()->addDays(rand(2, 5));

            $booking = Booking::create([
                'user_id' => $user->id,
                'petsitter_id' => $petsitter->user_id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => 'completed',
                'total_price' => $petsitter->price_per_day * $startDate->diffInDays($endDate),
            ]);

            // Link animal to booking
            \DB::table('animal_bookings')->insert([
                'booking_id' => $booking->id,
                'animal_id' => $animal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create review for the booking
            $rating = rand(4, 5);
            Review::create([
                'booking_id' => $booking->id,
                'petsitter_id' => $petsitter->user_id,
                'star_rating' => $rating,
                'comment' => $reviewComments[$petsitterIndex % count($reviewComments)],
                'visible' => true,
            ]);

            // Update petsitter's average rating
            $petsitter->refreshAverageRating();
        }
    }
}
