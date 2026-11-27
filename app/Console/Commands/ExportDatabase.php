<?php


/**
 * Comando que exporta TODOS os dados do banco de dados para um seeder.
 * Este comando é rodado automaticamente antes de cada push via git hook.
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportDatabase extends Command
{
    protected $signature = 'db:export';

    protected $description = 'Exportar todos os dados do banco de dados para um seeder';

    public function handle()
    {
        $this->info('Exportando dados do banco de dados...');

        app(\App\Services\ImportExportedPetsitters::class)->synchronizeCurrentDatabase();

        $seederPath = database_path('seeders/ExportedDataSeeder.php');

        $usersArray = $this->exportTable('users');
        foreach ($usersArray as &$userData) {
            unset($userData['remember_token'], $userData['two_factor_secret'], $userData['two_factor_recovery_codes']);
        }
        unset($userData);

        $petsittersArray = $this->exportTable('petsitters', 'profile_photo');
        $animalsArray = $this->exportTable('animals', 'photo');
        $postsArray = $this->exportTable('posts', 'photos');
        $servicesArray = $this->exportTable('petsitter_services');
        $bookingsArray = $this->exportTable('bookings');
        $notificationsArray = $this->exportTable('notifications');
        $reviewsArray = $this->exportTable('reviews');
        $animalBookingsArray = $this->exportTable('animal_bookings');

        // Exportar restantes tabelas de dados da aplicação sem incluir credenciais ou dados temporários
        $additionalTables = [
            'petsitter_availabilities',
            'favourites',
            'post_favourites',
            'support_tickets',
            'user_notes',
        ];
        $additionalTablesArray = [];
        foreach ($additionalTables as $table) {
            $additionalTablesArray[$table] = Schema::hasTable($table)
                ? DB::table($table)->get()->map(fn($row) => (array) $row)->all()
                : [];
        }

        // Generate the seeder file
        $content = "<?php\n\n\n";
        $content .= "namespace Database\Seeders;\n\n";
        $content .= "use App\Models\User;\n";
        $content .= "use App\Models\Petsitter;\n";
        $content .= "use App\Models\Animal;\n";
        $content .= "use App\Models\Post;\n";
        $content .= "use App\Models\Booking;\n";
        $content .= "use App\Models\Review;\n";
        $content .= "use App\Models\AnimalBooking;\n";
        $content .= "use App\Models\PetsitterService;\n";
        $content .= "use App\Models\Notification;\n";
        $content .= "use Illuminate\Database\Seeder;\n";
        $content .= "use Illuminate\Support\Facades\Hash;\n\n";
        $content .= "/**\n";
        $content .= " * SEEDER GERADO AUTOMATICAMENTE - NÃO EDITE MANUALMENTE\n";
        $content .= " * Este arquivo é gerado pelo comando php artisan db:export\n";
        $content .= " */\n";
        $content .= "class ExportedDataSeeder extends Seeder\n";
        $content .= "{\n";
        $content .= "    public function run(): void\n";
        $content .= "    {\n";
        $content .= "        // Desabilitar eventos durante o seed\n";
        $content .= "        \Illuminate\Support\Facades\Event::fake();\n\n";

        $content .= "        // Limpar primeiro as relações dependentes para evitar erros de chaves estrangeiras\n";
        $content .= "        \$additionalTables = " . var_export($additionalTablesArray, true) . ";\n";
        $content .= "        foreach (['user_notes', 'support_tickets', 'post_favourites', 'favourites', 'petsitter_availabilities'] as \$table) {\n";
        $content .= "            if (\\Illuminate\\Support\\Facades\\Schema::hasTable(\$table)) { \\Illuminate\\Support\\Facades\\DB::table(\$table)->delete(); }\n";
        $content .= "        }\n\n";
        $content .= "        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('notifications')) { Notification::query()->delete(); }\n";
        $content .= "        AnimalBooking::query()->delete();\n";
        $content .= "        Review::query()->delete();\n";
        $content .= "        Booking::query()->delete();\n";
        $content .= "        PetsitterService::query()->delete();\n";
        $content .= "        Animal::query()->delete();\n";
        $content .= "        Post::query()->delete();\n\n";

        // Users
        $content .= "        // Export Users (" . count($usersArray) . " users)\n";
        $content .= "        \$users = " . var_export($usersArray, true) . ";\n";
        $content .= "        \$userColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('users'));\n";
        $content .= "        foreach (\$users as \$userData) {\n";
        $content .= "            \$userCreateData = array_intersect_key(\$userData, \$userColumns);\n";
        $content .= "            \$user = User::firstOrNew(['email' => \$userData['email']]);\n";
        $content .= "            \$user->forceFill(\$userCreateData)->save();\n";
        $content .= "        }\n\n";

        // Petsitters
        $content .= "        // Export Petsitters (" . count($petsittersArray) . " petsitters)\n";
        $content .= "        \$petsitters = " . var_export($petsittersArray, true) . ";\n";
        $content .= "        \$petsitterColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('petsitters'));\n";
        $content .= "        foreach (\$petsitters as \$petsitterData) {\n";
        $content .= "            \$petsitterCreateData = array_intersect_key(\$petsitterData, \$petsitterColumns);\n";
        $content .= "            \$petsitter = Petsitter::firstOrNew(['user_id' => \$petsitterData['user_id']]);\n";
        $content .= "            \$petsitter->forceFill(\$petsitterCreateData)->save();\n";
        $content .= "        }\n\n";

        // Animals (delete all first, then recreate)
        $content .= "        // Export Animals (" . count($animalsArray) . " animals)\n";
        $content .= "        \$animals = " . var_export($animalsArray, true) . ";\n";
        $content .= "        \$animalColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('animals'));\n";
        $content .= "        foreach (\$animals as \$aData) {\n";
        $content .= "            \$animalCreateData = array_intersect_key(\$aData, \$animalColumns);\n";
        $content .= "            Animal::forceCreate(\$animalCreateData);\n";
        $content .= "        }\n\n";

        // Posts (delete all first, then recreate)
        $content .= "        // Export Posts (" . count($postsArray) . " posts)\n";
        $content .= "        \$posts = " . var_export($postsArray, true) . ";\n";
        $content .= "        \$postColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('posts'));\n";
        $content .= "        foreach (\$posts as \$postData) {\n";
        $content .= "            \$postCreateData = array_intersect_key(\$postData, \$postColumns);\n";
        $content .= "            Post::forceCreate(\$postCreateData);\n";
        $content .= "        }\n\n";

        // Bookings (delete all first, then recreate)
        $content .= "        // Export Bookings (" . count($bookingsArray) . " bookings)\n";

        // Petsitter services: depois de apagar as bookings (FK restrictOnDelete) e antes de as recriar
        $content .= "        // Export Petsitter Services (" . count($servicesArray) . " services)\n";
        $content .= "        \$services = " . var_export($servicesArray, true) . ";\n";
        $content .= "        \$serviceColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('petsitter_services'));\n";
        $content .= "        foreach (\$services as \$sData) {\n";
        $content .= "            // só cria se o petsitter existir (FK para petsitters.user_id)\n";
        $content .= "            if (Petsitter::where('user_id', \$sData['petsitter_id'])->exists()) {\n";
        $content .= "                PetsitterService::forceCreate(array_intersect_key(\$sData, \$serviceColumns)); // mantém o id original\n";
        $content .= "            }\n";
        $content .= "        }\n";
        $content .= "        \$serviceIds = PetsitterService::pluck('id')->all();\n\n";

        $content .= "        \$bookings = " . var_export($bookingsArray, true) . ";\n";
        $content .= "        // só usa as colunas que existem na tabela (evita erro se uma migration ainda não estiver no branch)\n";
        $content .= "        \$bookingColumns = array_flip(\Illuminate\Support\Facades\Schema::getColumnListing('bookings'));\n";
        $content .= "        foreach (\$bookings as \$bookingData) {\n";
        $content .= "            \$bookingCreateData = array_intersect_key(\$bookingData, \$bookingColumns);\n";
        $content .= "            // se o serviço não foi recriado, fica sem serviço em vez de falhar a FK\n";
        $content .= "            if (array_key_exists('service_id', \$bookingCreateData) && !in_array(\$bookingCreateData['service_id'], \$serviceIds)) {\n";
        $content .= "                \$bookingCreateData['service_id'] = null;\n";
        $content .= "            }\n";
        $content .= "            Booking::forceCreate(\$bookingCreateData);\n";
        $content .= "        }\n\n";

        // Reviews (delete all first, then recreate)
        $content .= "        // Export Reviews (" . count($reviewsArray) . " reviews)\n";
        $content .= "        \$reviews = " . var_export($reviewsArray, true) . ";\n";
        $content .= "        \$reviewColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('reviews'));\n";
        $content .= "        foreach (\$reviews as \$reviewData) {\n";
        $content .= "            \$reviewCreateData = array_intersect_key(\$reviewData, \$reviewColumns);\n";
        $content .= "            Review::forceCreate(\$reviewCreateData);\n";
        $content .= "        }\n\n";

        // Animal Bookings (delete all first, then recreate)
        $content .= "        // Export Animal Bookings (" . count($animalBookingsArray) . " animal bookings)\n";
        $content .= "        \$animalBookings = " . var_export($animalBookingsArray, true) . ";\n";
        $content .= "        \$animalBookingColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('animal_bookings'));\n";
        $content .= "        foreach (\$animalBookings as \$abData) {\n";
        $content .= "            \$abCreateData = array_intersect_key(\$abData, \$animalBookingColumns);\n";
        $content .= "            AnimalBooking::forceCreate(\$abCreateData);\n";
        $content .= "        }\n\n";

        $content .= "        // Export Notifications (" . count($notificationsArray) . " notifications)\n";
        $content .= "        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('notifications')) {\n";
        $content .= "            \$notificationColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing('notifications'));\n";
        $content .= "            \$notifications = " . var_export($notificationsArray, true) . ";\n";
        $content .= "            foreach (\$notifications as \$notificationData) {\n";
        $content .= "                Notification::forceCreate(array_intersect_key(\$notificationData, \$notificationColumns));\n";
        $content .= "            }\n";
        $content .= "        }\n";
        $content .= "        foreach (\$additionalTables as \$table => \$rows) {\n";
        $content .= "            if (!\\Illuminate\\Support\\Facades\\Schema::hasTable(\$table)) { continue; }\n";
        $content .= "            \$tableColumns = array_flip(\\Illuminate\\Support\\Facades\\Schema::getColumnListing(\$table));\n";
        $content .= "            foreach (\$rows as \$row) {\n";
        $content .= "                \$row = array_intersect_key(\$row, \$tableColumns);\n";
        $content .= "                foreach (\$row as \$column => \$value) { if (is_array(\$value)) { \$row[\$column] = json_encode(\$value); } }\n";
        $content .= "                \\Illuminate\\Support\\Facades\\DB::table(\$table)->insert(\$row);\n";
        $content .= "            }\n";
        $content .= "        }\n\n";

        $content .= "        \Illuminate\Support\Facades\Event::dispatch(null);\n";
        $content .= "    }\n";
        $content .= "}\n";

        if (File::put($seederPath, $content) === false) {
            $this->error("Não foi possível escrever o snapshot em {$seederPath}.");

            return self::FAILURE;
        }

        $this->info("Dados exportados para ExportedDataSeeder.php");
        $this->info("Users: " . count($usersArray));
        $this->info("Petsitters: " . count($petsittersArray));
        $this->info("Animais: " . count($animalsArray));
        $this->info("Posts: " . count($postsArray));
        $this->info("Services: " . count($servicesArray));
        $this->info("Bookings: " . count($bookingsArray));
        $this->info("Reviews: " . count($reviewsArray));
        $this->info("Animal Bookings: " . count($animalBookingsArray));
        $this->info("Notifications: " . count($notificationsArray));
        $this->info("Other domain records: " . array_sum(array_map('count', $additionalTablesArray)));

        return 0;
    }

    protected function exportTable(string $table, ?string $photoColumn = null): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->get()->map(function ($row) use ($photoColumn) {
            $data = (array) $row;

            if ($photoColumn && array_key_exists($photoColumn, $data)) {
                if ($photoColumn === 'photos') {
                    $photos = is_string($data[$photoColumn]) ? json_decode($data[$photoColumn], true) : $data[$photoColumn];
                    $data[$photoColumn] = $this->exportPhotos(is_array($photos) ? $photos : null);
                } else {
                    $data[$photoColumn] = $this->exportPhoto($data[$photoColumn]);
                }
            }

            return $data;
        })->all();
    }

    /**
     * Exporta uma foto de perfil para base64
     */
    protected function exportPhoto(?string $photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }

        // Se já é base64, retorna diretamente
        if (str_starts_with($photoPath, 'data:')) {
            return $photoPath;
        }

        try {
            $fullPath = storage_path('app/public/' . $photoPath);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                $mimeType = File::mimeType($fullPath);
                return 'data:' . $mimeType . ';base64,' . base64_encode($content);
            }
        } catch (\Exception $e) {
            // Se falhar, retorna null
        }

        return null;
    }

    /**
     * Exporta um array de fotos (posts) para base64
     */
    protected function exportPhotos(?array $photoPaths): ?array
    {
        if (!$photoPaths || empty($photoPaths)) {
            return null;
        }

        $exported = [];
        foreach ($photoPaths as $path) {
            if (is_string($path) && str_starts_with($path, 'data:')) {
                $exported[] = $path;
            } else {
                $exported[] = $this->exportPhoto($path);
            }
        }

        return $exported;
    }
}
