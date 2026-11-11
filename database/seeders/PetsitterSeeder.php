<?php

namespace Database\Seeders;

use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Database\Seeder;

class PetsitterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users for petsitters if they don't exist
        $users = [
            ['name' => 'Sofia', 'email' => 'sofia@patudo.test', 'city' => 'Lisboa', 'price' => 12],
            ['name' => 'Tiago', 'email' => 'tiago@patudo.test', 'city' => 'Porto', 'price' => 10],
            ['name' => 'João', 'email' => 'joao@patudo.test', 'city' => 'Braga', 'price' => 11],
            ['name' => 'Inês', 'email' => 'ines@patudo.test', 'city' => 'Coimbra', 'price' => 9],
            ['name' => 'Maria', 'email' => 'maria@patudo.test', 'city' => 'Aveiro', 'price' => 13],
            ['name' => 'Rui', 'email' => 'rui@patudo.test', 'city' => 'Faro', 'price' => 10],
        ];

        foreach ($users as $userData) {
            // Create user if not exists
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Create petsitter if not exists
            Petsitter::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'experience' => 'Experienced petsitter',
                    'service_location' => $userData['city'],
                    'age' => 25,
                    'price_per_day' => $userData['price'],
                    'accepted_sizes' => ['small', 'medium', 'large'],
                    'accepted_animal_types' => ['dog', 'cat'],
                    'sex' => in_array($userData['name'], ['Sofia', 'Inês', 'Maria']) ? 'female' : 'male',
                    'identification_documents' => 'document_' . $user->id . '.pdf',
                    'approval_status' => 'approved',
                ]
            );
        }
    }
}
