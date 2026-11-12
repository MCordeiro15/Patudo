<?php

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
        // Call PetsitterSeeder to create petsitters with test data
        $this->call(PetsitterSeeder::class);

        // Create or update admin user with petsitter profile
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
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
                'availability_days' => json_encode([
                    'monday' => true,
                    'tuesday' => true,
                    'wednesday' => true,
                    'thursday' => true,
                    'friday' => true,
                    'saturday' => true,
                    'sunday' => true,
                ]),
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
                'availability_days' => json_encode([
                    'monday' => true,
                    'tuesday' => true,
                    'wednesday' => true,
                    'thursday' => true,
                    'friday' => true,
                    'saturday' => true,
                    'sunday' => true,
                ]),
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

        // Create regular test user
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
