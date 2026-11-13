<?php


/**
 * Comando que exporta automaticamente os users existentes no banco de dados para os seeders.
 * Detecta users novos que não estão nos seeders e os adiciona ao DatabaseSeeder.
 */
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportUsersToSeeders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:export-to-seeders';

    /**
     * The console command description.
     */
    protected $description = 'Exportar users novos para o DatabaseSeeder automaticamente';

    /**
     * Emails que já estão nos seeders (não precisam de ser exportados)
     */
    protected $seededEmails = [
        'admin@admin.com',
        'test@example.com',
        'miguel@gmail.com',
        'sofia@patudo.test',
        'tiago@patudo.test',
        'joao@patudo.test',
        'ines@patudo.test',
        'maria@patudo.test',
        'rui@patudo.test',
        'client1@patudo.test',
        'client2@patudo.test',
        'client3@patudo.test',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('A detetar users novos...');

        // Pega todos os users do banco de dados
        $users = User::all();
        $newUsers = [];

        foreach ($users as $user) {
            if (!in_array($user->email, $this->seededEmails)) {
                $newUsers[] = $user;
            }
        }

        if (empty($newUsers)) {
            $this->info('Nenhum user novo detetado. Todos os users já estão nos seeders.');
            return 0;
        }

        $this->info("Encontrados " . count($newUsers) . " user(s) novo(s):");
        foreach ($newUsers as $user) {
            $this->line("  - {$user->name} ({$user->email})");
        }

        // Atualiza a lista de emails seedados
        foreach ($newUsers as $user) {
            $this->seededEmails[] = $user->email;
        }

        // Atualiza o DatabaseSeeder
        $seederPath = database_path('seeders/DatabaseSeeder.php');
        $seederContent = File::get($seederPath);

        // Gera o código PHP para os novos users
        $newUserCode = "\n        // Users exportados automaticamente\n";
        foreach ($newUsers as $user) {
            $newUserCode .= "        User::firstOrCreate(\n";
            $newUserCode .= "            ['email' => '{$user->email}'],\n";
            $newUserCode .= "            [\n";
            $newUserCode .= "                'name' => '{$user->name}',\n";
            $newUserCode .= "                'password' => bcrypt('password'),\n";
            $newUserCode .= "                'email_verified_at' => now(),\n";
            $newUserCode .= "            ]\n";
            $newUserCode .= "        );\n";
        }

        // Insere antes do comentário dos posts
        $searchString = "        // 5. Create posts for petsitters";
        $seederContent = str_replace($searchString, $newUserCode . "\n        // 5. Create posts for petsitters", $seederContent);

        // Atualiza a lista de emails seedados
        $emailList = "    protected \$seededEmails = [\n";
        foreach ($this->seededEmails as $email) {
            $emailList .= "        '{$email}',\n";
        }
        $emailList .= "    ];";

        $oldEmailListPattern = '#    protected \$seededEmails = \[\n(?:\s+.+\n)+    \];#';
        $seederContent = preg_replace($oldEmailListPattern, $emailList, $seederContent);

        File::put($seederPath, $seederContent);

        // Atualiza o README
        $readmePath = database_path('README.md');
        $readmeContent = File::get($readmePath);

        // Adiciona os novos users à tabela de credenciais
        $newReadmeLines = "";
        foreach ($newUsers as $user) {
            $newReadmeLines .= "| {$user->name} | `{$user->email}` | `password` |\n";
        }

        // Insere antes da linha dos clientes
        $readmeContent = str_replace(
            "| Ana (Cliente) | `client1@patudo.test` | `password` |",
            $newReadmeLines . "| Ana (Cliente) | `client1@patudo.test` | `password` |",
            $readmeContent
        );

        // Atualiza o número de users
        $totalUsers = count($this->seededEmails);
        $readmeContent = preg_replace(
            '/- \*\*\d+ Users\*\*: Admin, Test User, Miguel, 6 Petsitters/',
            "- **{$totalUsers} Users**: Admin, Test User, Miguel, 6 Petsitters",
            $readmeContent
        );

        File::put($readmePath, $readmeContent);

        $this->info('DatabaseSeeder atualizado com ' . count($newUsers) . ' user(s) novo(s)!');
        $this->info('README atualizado com as novas credenciais!');
        $this->line('');
        $this->info('Agora faz commit e push:');
        $this->line('  git add -A');
        $this->line('  git commit -m "Adicionar novos users aos seeders"');
        $this->line('  git push origin main');

        return 0;
    }
}
