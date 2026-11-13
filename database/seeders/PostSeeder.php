<?php


/**
 * Seeder que preenche a base de dados com posts de exemplo para desenvolvimento e testes.
 * Seeder that fills the database with example posts for development and testing.
 */
namespace Database\Seeders;

use App\Models\Post;
use App\Models\Petsitter;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
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

        $postTemplates = [
            [
                'title' => 'Conheçam o Rex!',
                'description' => 'Hoje passei o dia com o Rex, um Golden Retriever adorável. Fizemos um passeio pela praia e ele se divertiu muito!',
            ],
            [
                'title' => 'Dia de mimos com a Luna',
                'description' => 'A Luna é uma gatinha muito carinhosa. Passamos o dia brincando e ela adorou as novas brincadeiras que ensinei.',
            ],
            [
                'title' => 'Passeio matinal com o Thor',
                'description' => 'O Thor é um Labrador cheio de energia! Fizemos um passeio matinal pelo parque e ele fez novos amigos.',
            ],
            [
                'title' => 'Cuidando da Mimi',
                'description' => 'A Mimi é uma gatinha especial que precisa de cuidados atenciosos. Estou a adorando cuidar dela!',
            ],
            [
                'title' => 'Aventuras com o Bob',
                'description' => 'O Bob é um cão curioso e aventureiro. Exploramos uma trilha nova hoje e ele descobriu muitos cheiros interessantes.',
            ],
            [
                'title' => 'Tempo de brincadeira com a Pipa',
                'description' => 'A Pipa é uma papagaia inteligente e divertida. Passamos o dia treinando novos truques e ela aprendeu rápido!',
            ],
            [
                'title' => 'Dia especial com o Max',
                'description' => 'O Max é um Bulldog francês adorável. Fizemos um piquenique no parque e ele comeu muito bem!',
            ],
            [
                'title' => 'Cuidando do Simba',
                'description' => 'O Simba é um gato persa que adora ser mimado. Passamos o dia inteiro brincando e fazendo spa.',
            ],
        ];

        foreach ($petsitters as $petsitterIndex => $petsitter) {
            // Each petsitter gets 1-3 posts
            $postCount = rand(1, 3);
            $indices = array_rand($postTemplates, min($postCount, count($postTemplates)));

            if (!is_array($indices)) {
                $indices = [$indices];
            }

            foreach ($indices as $index) {
                Post::create([
                    'petsitter_id' => $petsitter->user_id,
                    'title' => $postTemplates[$index]['title'],
                    'description' => $postTemplates[$index]['description'],
                    'photos' => null,
                ]);
            }
        }
    }
}
