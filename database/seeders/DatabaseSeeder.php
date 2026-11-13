<?php


/**
 * Seeder principal que orquestra a população da base de dados. Chama os outros seeders e cria dados iniciais para desenvolvimento/testes.
 * Main seeder that orchestrates database population. Calls other seeders and creates initial data for development/testing.
 */
namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create petsitters with test data
        $this->call(PetsitterSeeder::class);

        // 2. Create or update admin user (always ensure correct password)
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin1234'),
                'email_verified_at' => now(),
                'phone' => '910000000',
            ]
        );

        // Ensure admin flags are set
        if (!$admin->is_admin || !$admin->is_petsitter) {
            $admin->update([
                'is_admin' => true,
                'is_petsitter' => true,
            ]);
        }

        // Create or update petsitter profile for admin
        $adminPetsitter = Petsitter::where('user_id', $admin->id)->first();
        if ($adminPetsitter) {
            $adminPetsitter->update([
                'biography' => 'Administrador do Patudo com perfil de petsitter de teste.',
                'experience' => 'Experiência em cuidados com animais de estimação.',
                'service_location' => 'São Miguel, Açores',
                'age' => 25,
                'price_per_day' => 15,
                'accepted_sizes' => ['small', 'medium', 'large'],
                'accepted_animal_types' => ['dog', 'cat', 'bird', 'rabbit'],
                'sex' => 'male',
                'identification_documents' => 'document_admin.pdf',
                'approval_status' => 'approved',
                'short_bio' => 'Admin do Patudo',
                'city' => 'São Miguel',
                'average_rating' => 5.0,
                'availability_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            ]);
        } else {
            Petsitter::create([
                'user_id' => $admin->id,
                'biography' => 'Administrador do Patudo com perfil de petsitter de teste.',
                'experience' => 'Experiência em cuidados com animais de estimação.',
                'service_location' => 'São Miguel, Açores',
                'age' => 25,
                'price_per_day' => 15,
                'accepted_sizes' => ['small', 'medium', 'large'],
                'accepted_animal_types' => ['dog', 'cat', 'bird', 'rabbit'],
                'sex' => 'male',
                'identification_documents' => 'document_admin.pdf',
                'approval_status' => 'approved',
                'short_bio' => 'Admin do Patudo',
                'city' => 'São Miguel',
                'average_rating' => 5.0,
                'availability_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            ]);
        }

        // Create a test animal for admin
        Animal::firstOrCreate(
            [
                'user_id' => $admin->id,
                'name' => 'Pet do Admin',
            ],
            [
                'species' => 'Dog',
                'breed' => 'Golden Retriever',
                'size' => 'Large',
                'sex' => 'macho',
                'weight' => 30.0,
            ]
        );

        // 3. Create regular test user only if it doesn't exist
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'phone' => '920000000',
            ]
        );

        // 4. Create Miguel's user only if it doesn't exist
        User::firstOrCreate(
            ['email' => 'miguel@gmail.com'],
            [
                'name' => 'Miguel',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'phone' => '930000000',
            ]
        );

        // Se existir o ExportedDataSeeder (gerado automaticamente), usa-o
        // Caso contrário, usa os seeders normais
        if (file_exists(database_path('seeders/ExportedDataSeeder.php'))) {
            $this->call(ExportedDataSeeder::class);
        } else {
            // 5. Create posts for petsitters
            $this->call(PostSeeder::class);

            // 6. Create bookings and reviews
            $this->call(BookingSeeder::class);
        }
    }
}
