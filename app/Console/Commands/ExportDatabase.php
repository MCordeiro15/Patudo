<?php


/**
 * Comando que exporta TODOS os dados do banco de dados para um seeder.
 * Este comando é rodado automaticamente antes de cada push via git hook.
 */
namespace App\Console\Commands;

use App\Models\User;
use App\Models\Petsitter;
use App\Models\Animal;
use App\Models\Post;
use App\Models\Booking;
use App\Models\Review;
use App\Models\AnimalBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExportDatabase extends Command
{
    protected $signature = 'db:export';

    protected $description = 'Exportar todos os dados do banco de dados para um seeder';

    public function handle()
    {
        $this->info('Exportando dados do banco de dados...');

        $seederPath = database_path('seeders/ExportedDataSeeder.php');

        // Export users
        $users = User::with('petsitter')->get();
        $usersArray = $users->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'password' => $u->password,
            'email_verified_at' => $u->email_verified_at,
            'phone' => $u->phone,
            'is_admin' => $u->is_admin ?? false,
            'is_petsitter' => $u->is_petsitter ?? false,
            'created_at' => $u->created_at,
            'updated_at' => $u->updated_at,
        ])->toArray();

        // Export petsitters com fotos em base64
        $petsitters = Petsitter::all();
        $petsittersArray = $petsitters->map(fn($p) => [
            'id' => $p->id,
            'user_id' => $p->user_id,
            'biography' => $p->biography,
            'short_bio' => $p->short_bio,
            'experience' => $p->experience,
            'service_location' => $p->service_location,
            'city' => $p->city,
            'age' => $p->age,
            'price_per_day' => $p->price_per_day,
            'average_rating' => $p->average_rating,
            'accepted_sizes' => $p->accepted_sizes ?? [],
            'sex' => $p->sex,
            'accepted_animal_types' => $p->accepted_animal_types ?? [],
            'identification_documents' => $p->identification_documents,
            'approval_status' => $p->approval_status ?? 'pending',
            'availability_days' => $p->availability_days ?? [],
            'profile_photo' => $this->exportPhoto($p->profile_photo),
            'created_at' => $p->created_at,
            'updated_at' => $p->updated_at,
        ])->toArray();

        // Export animals
        $animals = Animal::all();
        $animalsArray = $animals->map(fn($a) => [
            'user_id' => $a->user_id,
            'name' => $a->name,
            'species' => $a->species,
            'breed' => $a->breed,
            'size' => $a->size,
            'sex' => $a->sex ?? null,
            'weight' => $a->weight ?? null,
            'photo' => $a->photo ?? null,
            'special_needs' => $a->special_needs ?? null,
            'aggression' => $a->aggression ?? null,
            'medication' => $a->medication ?? null,
        ])->toArray();

        // Export posts
        $posts = Post::all();
        $postsArray = $posts->map(fn($p) => [
            'petsitter_id' => $p->petsitter_id,
            'title' => $p->title ?? null,
            'description' => $p->description,
            'photos' => $p->photos ?? null,
        ])->toArray();

        // Export bookings
        $bookings = Booking::all();
        $bookingsArray = $bookings->map(fn($b) => [
            'petsitter_id' => $b->petsitter_id,
            'user_id' => $b->user_id,
            'start_date' => $b->start_date,
            'end_date' => $b->end_date,
            'status' => $b->status ?? 'pending',
            'total_price' => $b->total_price ?? 0,
        ])->toArray();

        // Export reviews
        $reviews = Review::all();
        $reviewsArray = $reviews->map(fn($r) => [
            'booking_id' => $r->booking_id,
            'petsitter_id' => $r->petsitter_id,
            'star_rating' => $r->star_rating ?? $r->rating ?? 5,
            'comment' => $r->comment ?? null,
            'visible' => $r->visible ?? true,
        ])->toArray();

        // Export animal_bookings
        $animalBookings = \App\Models\AnimalBooking::all();
        $animalBookingsArray = $animalBookings->map(fn($ab) => [
            'booking_id' => $ab->booking_id,
            'animal_id' => $ab->animal_id,
        ])->toArray();

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
        $content .= "use Illuminate\Database\Seeder;\n";
        $content .= "use Illuminate\Support\Facades\Hash;\n\n";
        $content .= "/**\n";
        $content .= " * SEEDER GERADO AUTOMATICAMENTE - NÃO EDITE MANUALMENTE\n";
        $content .= " * Este arquivo é gerado pelo comando php artisan db:export\n";
        $content .= " * Última atualização: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= " */\n";
        $content .= "class ExportedDataSeeder extends Seeder\n";
        $content .= "{\n";
        $content .= "    public function run(): void\n";
        $content .= "    {\n";
        $content .= "        // Desabilitar eventos durante o seed\n";
        $content .= "        \Illuminate\Support\Facades\Event::fake();\n\n";

        // Users
        $content .= "        // Export Users (" . count($usersArray) . " users)\n";
        $content .= "        \$users = " . var_export($usersArray, true) . ";\n";
        $content .= "        foreach (\$users as \$userData) {\n";
        $content .= "            User::updateOrCreate(\n";
        $content .= "                ['email' => \$userData['email']],\n";
        $content .= "                [\n";
        $content .= "                    'name' => \$userData['name'],\n";
        $content .= "                    'password' => \$userData['password'],\n";
        $content .= "                    'email_verified_at' => \$userData['email_verified_at'],\n";
        $content .= "                    'phone' => \$userData['phone'],\n";
        $content .= "                    'is_admin' => \$userData['is_admin'],\n";
        $content .= "                    'is_petsitter' => \$userData['is_petsitter'],\n";
        $content .= "                ]\n";
        $content .= "            );\n";
        $content .= "        }\n\n";

        // Petsitters
        $content .= "        // Export Petsitters (" . count($petsittersArray) . " petsitters)\n";
        $content .= "        \$petsitters = " . var_export($petsittersArray, true) . ";\n";
        $content .= "        foreach (\$petsitters as \$pData) {\n";
        $content .= "            \$petsitter = Petsitter::where('user_id', \$pData['user_id'])->first();\n";
        $content .= "            if (\$petsitter) {\n";
        $content .= "                \$petsitter->update([\n";
        $content .= "                    'biography' => \$pData['biography'],\n";
        $content .= "                    'short_bio' => \$pData['short_bio'],\n";
        $content .= "                    'experience' => \$pData['experience'],\n";
        $content .= "                    'service_location' => \$pData['service_location'],\n";
        $content .= "                    'city' => \$pData['city'],\n";
        $content .= "                    'age' => \$pData['age'],\n";
        $content .= "                    'price_per_day' => \$pData['price_per_day'],\n";
        $content .= "                    'average_rating' => \$pData['average_rating'],\n";
        $content .= "                    'accepted_sizes' => \$pData['accepted_sizes'],\n";
        $content .= "                    'sex' => \$pData['sex'],\n";
        $content .= "                    'accepted_animal_types' => \$pData['accepted_animal_types'],\n";
        $content .= "                    'identification_documents' => \$pData['identification_documents'],\n";
        $content .= "                    'approval_status' => \$pData['approval_status'],\n";
        $content .= "                    'availability_days' => \$pData['availability_days'],\n";
        $content .= "                    'profile_photo' => \$pData['profile_photo'],\n";
        $content .= "                ]);\n";
        $content .= "            }\n";
        $content .= "        }\n\n";

        // Animals (delete all first, then recreate)
        $content .= "        // Export Animals (" . count($animalsArray) . " animals)\n";
        $content .= "        Animal::query()->delete();\n";
        $content .= "        \$animals = " . var_export($animalsArray, true) . ";\n";
        $content .= "        foreach (\$animals as \$aData) {\n";
        $content .= "            \$animalData = \$aData;\n";
        $content .= "            unset(\$animalData['id']);\n";
        $content .= "            Animal::create(\$animalData);\n";
        $content .= "        }\n\n";

        // Posts (delete all first, then recreate)
        $content .= "        // Export Posts (" . count($postsArray) . " posts)\n";
        $content .= "        Post::query()->delete();\n";
        $content .= "        \$posts = " . var_export($postsArray, true) . ";\n";
        $content .= "        foreach (\$posts as \$postData) {\n";
        $content .= "            \$postCreateData = \$postData;\n";
        $content .= "            unset(\$postCreateData['id']);\n";
        $content .= "            Post::create(\$postCreateData);\n";
        $content .= "        }\n\n";

        // Bookings (delete all first, then recreate)
        $content .= "        // Export Bookings (" . count($bookingsArray) . " bookings)\n";
        $content .= "        Booking::query()->delete();\n";
        $content .= "        \$bookings = " . var_export($bookingsArray, true) . ";\n";
        $content .= "        foreach (\$bookings as \$bookingData) {\n";
        $content .= "            \$bookingCreateData = \$bookingData;\n";
        $content .= "            unset(\$bookingCreateData['id']);\n";
        $content .= "            Booking::create(\$bookingCreateData);\n";
        $content .= "        }\n\n";

        // Reviews (delete all first, then recreate)
        $content .= "        // Export Reviews (" . count($reviewsArray) . " reviews)\n";
        $content .= "        Review::query()->delete();\n";
        $content .= "        \$reviews = " . var_export($reviewsArray, true) . ";\n";
        $content .= "        foreach (\$reviews as \$reviewData) {\n";
        $content .= "            \$reviewCreateData = \$reviewData;\n";
        $content .= "            unset(\$reviewCreateData['id']);\n";
        $content .= "            Review::create(\$reviewCreateData);\n";
        $content .= "        }\n\n";

        // Animal Bookings (delete all first, then recreate)
        $content .= "        // Export Animal Bookings (" . count($animalBookingsArray) . " animal bookings)\n";
        $content .= "        AnimalBooking::query()->delete();\n";
        $content .= "        \$animalBookings = " . var_export($animalBookingsArray, true) . ";\n";
        $content .= "        foreach (\$animalBookings as \$abData) {\n";
        $content .= "            \$abCreateData = \$abData;\n";
        $content .= "            unset(\$abCreateData['id']);\n";
        $content .= "            AnimalBooking::create(\$abCreateData);\n";
        $content .= "        }\n\n";

        $content .= "        \Illuminate\Support\Facades\Event::dispatch(null);\n";
        $content .= "    }\n";
        $content .= "}\n";

        File::put($seederPath, $content);
        $this->info("Dados exportados para ExportedDataSeeder.php");
        $this->info("Users: " . count($usersArray));
        $this->info("Petsitters: " . count($petsittersArray));
        $this->info("Animais: " . count($animalsArray));
        $this->info("Posts: " . count($postsArray));
        $this->info("Bookings: " . count($bookingsArray));
        $this->info("Reviews: " . count($reviewsArray));
        $this->info("Animal Bookings: " . count($animalBookingsArray));

        return 0;
    }

    /**
     * Exporta uma foto de perfil para base64
     */
    protected function exportPhoto(?string $photoPath): ?string
    {
        if (!$photoPath) {
            return null;
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
}
