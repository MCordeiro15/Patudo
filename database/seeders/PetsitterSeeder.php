<?php

namespace Database\Seeders;

use App\Models\Animal;
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
            [
                'name' => 'Sofia Mendes',
                'email' => 'sofia@patudo.test',
                'city' => 'Lisboa',
                'service_location' => 'Lisboa, Portugal',
                'price' => 12,
                'sex' => 'female',
                'age' => 28,
                'experience' => 'Cuidadora de animais há 5 anos. Especializada em cães de grande porte e gatos. Disponibilidade total e muito amor para dar!',
                'biography' => 'Apaixonada por animais desde criança, tenho experiência com todas as raças de cães e gatos. Ofereço cuidados personalizados e muitos mimos.',
                'short_bio' => 'Cuidadora experiente em Lisboa',
                'average_rating' => 4.8,
            ],
            [
                'name' => 'Tiago Ferreira',
                'email' => 'tiago@patudo.test',
                'city' => 'Porto',
                'service_location' => 'Porto, Portugal',
                'price' => 10,
                'sex' => 'male',
                'age' => 24,
                'experience' => 'Estudante de veterinária com 3 anos de experiência em cuidados com animais. Adoro passeios longos com cães.',
                'biography' => 'Estudante de veterinária no Porto. Nos tempos livres, cuido de animais e faço passeios com cães pela cidade e pela praia.',
                'short_bio' => 'Estudante de veterinária no Porto',
                'average_rating' => 4.5,
            ],
            [
                'name' => 'João Santos',
                'email' => 'joao@patudo.test',
                'city' => 'Braga',
                'service_location' => 'Braga, Portugal',
                'price' => 11,
                'sex' => 'male',
                'age' => 30,
                'experience' => 'Técnico veterinário com 7 anos de experiência. Cuidado especializado para animais com necessidades especiais.',
                'biography' => 'Técnico veterinário qualificado. Ofereço cuidados de saúde básicos, administração de medicação e acompanhamento de animais idosos.',
                'short_bio' => 'Técnico veterinário em Braga',
                'average_rating' => 4.9,
            ],
            [
                'name' => 'Inês Rodrigues',
                'email' => 'ines@patudo.test',
                'city' => 'Coimbra',
                'service_location' => 'Coimbra, Portugal',
                'price' => 9,
                'sex' => 'female',
                'age' => 22,
                'experience' => 'Jovem entusiasta de animais com experiência em pet sitting e dog walking. Adoro todos os tipos de animais!',
                'biography' => 'Apaixonada por animais e natureza. Faço passeios diários e ofereço um ambiente acolhedor para os seus companheiros de quatro patas.',
                'short_bio' => 'Entusiasta de animais em Coimbra',
                'average_rating' => 4.3,
            ],
            [
                'name' => 'Maria Oliveira',
                'email' => 'maria@patudo.test',
                'city' => 'Aveiro',
                'service_location' => 'Aveiro, Portugal',
                'price' => 13,
                'sex' => 'female',
                'age' => 35,
                'experience' => 'Comportamentalista animal com 10 anos de experiência. Especialista em animais com ansiedade de separação.',
                'biography' => 'Profissional certificada em comportamento animal. Ofereço cuidados especializados para animais com necessidades comportamentais.',
                'short_bio' => 'Comportamentalista animal em Aveiro',
                'average_rating' => 5.0,
            ],
            [
                'name' => 'Rui Costa',
                'email' => 'rui@patudo.test',
                'city' => 'Faro',
                'service_location' => 'Faro, Portugal',
                'price' => 10,
                'sex' => 'male',
                'age' => 26,
                'experience' => 'Treinador canino com 4 anos de experiência. Combino cuidados com sessões de treino para enriquecer a vida do seu pet.',
                'biography' => 'Treinador canino profissional. Ofereço passeios ativos, sessões de treino e muito carinho para os seus animais.',
                'short_bio' => 'Treinador canino em Faro',
                'average_rating' => 4.6,
            ],
        ];

        foreach ($users as $userData) {
            // Create or update user and set is_petsitter
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Ensure is_petsitter is set
            if (!$user->is_petsitter) {
                $user->update(['is_petsitter' => true]);
            }

            // Create or update petsitter
            $petsitter = Petsitter::where('user_id', $user->id)->first();
            if ($petsitter) {
                $petsitter->update([
                    'biography' => $userData['biography'],
                    'experience' => $userData['experience'],
                    'service_location' => $userData['service_location'],
                    'age' => $userData['age'],
                    'price_per_day' => $userData['price'],
                    'accepted_sizes' => ['small', 'medium', 'large'],
                    'accepted_animal_types' => ['dog', 'cat'],
                    'sex' => $userData['sex'],
                    'identification_documents' => 'document_' . $user->id . '.pdf',
                    'approval_status' => 'approved',
                    'short_bio' => $userData['short_bio'],
                    'city' => $userData['city'],
                    'average_rating' => $userData['average_rating'],
                    'availability_days' => json_encode([
                        'monday' => true,
                        'tuesday' => true,
                        'wednesday' => true,
                        'thursday' => true,
                        'friday' => true,
                        'saturday' => true,
                        'sunday' => false,
                    ]),
                ]);
            } else {
                Petsitter::create([
                    'user_id' => $user->id,
                    'biography' => $userData['biography'],
                    'experience' => $userData['experience'],
                    'service_location' => $userData['service_location'],
                    'age' => $userData['age'],
                    'price_per_day' => $userData['price'],
                    'accepted_sizes' => ['small', 'medium', 'large'],
                    'accepted_animal_types' => ['dog', 'cat'],
                    'sex' => $userData['sex'],
                    'identification_documents' => 'document_' . $user->id . '.pdf',
                    'approval_status' => 'approved',
                    'short_bio' => $userData['short_bio'],
                    'city' => $userData['city'],
                    'average_rating' => $userData['average_rating'],
                    'availability_days' => json_encode([
                        'monday' => true,
                        'tuesday' => true,
                        'wednesday' => true,
                        'thursday' => true,
                        'friday' => true,
                        'saturday' => true,
                        'sunday' => false,
                    ]),
                ]);
            }

            // Create a test animal for each petsitter if not exists
            Animal::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => 'Pet de ' . $userData['name'],
                ],
                [
                    'species' => 'Dog',
                    'breed' => 'Labrador',
                    'size' => 'Medium',
                    'sex' => 'macho',
                    'weight' => 25.5,
                ]
            );
        }
    }
}
