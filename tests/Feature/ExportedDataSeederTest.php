<?php

use App\Models\Animal;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;
use App\Services\ImportExportedPetsitters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

test('database export import creates an approved petsitter profile and restores its role', function () {
    $user = User::factory()->create(['is_petsitter' => false]);

    app(ImportExportedPetsitters::class)->import(
        [['id' => 101, 'email' => $user->email, 'is_petsitter' => true]],
        [[
            'user_id' => 101,
            'biography' => 'Cuidados com animais.',
            'experience' => 'Cinco anos de experiência.',
            'service_location' => 'Ponta Delgada',
            'age' => 30,
            'price_per_day' => 15,
            'accepted_sizes' => ['small'],
            'accepted_animal_types' => ['dog'],
            'sex' => 'female',
            'approval_status' => 'approved',
            'identification_documents' => 'document.pdf',
        ]]
    );

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $user->id,
        'approval_status' => 'approved',
    ]);
    expect($user->fresh()->is_petsitter)->toBeTrue();
});

test('database export import restores petsitter JSON fields as arrays', function () {
    $user = User::factory()->create(['is_petsitter' => true]);

    app(ImportExportedPetsitters::class)->import(
        [['id' => 404, 'email' => $user->email, 'is_petsitter' => true]],
        [[
            'user_id' => 404,
            'experience' => 'Experiência restaurada.',
            'service_location' => 'Ponta Delgada',
            'age' => 30,
            'price_per_day' => 15,
            'accepted_sizes' => '["small", "large"]',
            'accepted_animal_types' => '["dog", "cat"]',
            'availability_days' => '["monday", "friday"]',
            'approval_status' => 'approved',
            'identification_documents' => 'document.pdf',
        ]]
    );

    $petsitter = Petsitter::findOrFail($user->id);

    expect($petsitter->accepted_sizes)->toBe(['small', 'large'])
        ->and($petsitter->accepted_animal_types)->toBe(['dog', 'cat'])
        ->and($petsitter->availability_days)->toBe(['monday', 'friday']);
});

test('database export import removes approved status when the exported user is not a petsitter', function () {
    $user = User::factory()->create(['is_petsitter' => true]);

    app(ImportExportedPetsitters::class)->import(
        [['id' => 202, 'email' => $user->email, 'is_petsitter' => false]],
        [[
            'user_id' => 202,
            'biography' => 'Perfil antigo.',
            'experience' => 'Experiência anterior.',
            'service_location' => 'Ponta Delgada',
            'age' => 30,
            'price_per_day' => 15,
            'accepted_sizes' => ['small'],
            'accepted_animal_types' => ['dog'],
            'sex' => 'female',
            'approval_status' => 'approved',
            'identification_documents' => 'document.pdf',
        ]]
    );

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $user->id,
        'approval_status' => 'rejected',
    ]);
    expect($user->fresh()->is_petsitter)->toBeFalse();
});

test('database export import does not approve a pending petsitter profile', function () {
    $user = User::factory()->create(['is_petsitter' => true]);

    app(ImportExportedPetsitters::class)->import(
        [['id' => 303, 'email' => $user->email, 'is_petsitter' => true]],
        [[
            'user_id' => 303,
            'biography' => 'Candidatura pendente.',
            'experience' => 'Experiência inicial.',
            'service_location' => 'Ponta Delgada',
            'age' => 30,
            'price_per_day' => 15,
            'accepted_sizes' => ['small'],
            'accepted_animal_types' => ['dog'],
            'sex' => 'female',
            'approval_status' => 'pending',
            'identification_documents' => 'document.pdf',
        ]]
    );

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $user->id,
        'approval_status' => 'pending',
    ]);
    expect($user->fresh()->is_petsitter)->toBeFalse();
});

test('database export rejects an approved profile when its user is no longer a petsitter', function () {
    $user = User::factory()->create(['is_petsitter' => false]);
    $petsitter = Petsitter::factory()->approved()->create(['user_id' => $user->id]);
    $user->refresh()->forceFill(['is_petsitter' => false])->save();

    app(ImportExportedPetsitters::class)->synchronizeCurrentDatabase();

    expect($petsitter->fresh()->approval_status)->toBe('rejected');
});

test('database export clears petsitter role without an approved profile', function () {
    $user = User::factory()->petsitter()->create();

    app(ImportExportedPetsitters::class)->synchronizeCurrentDatabase();

    expect($user->fresh()->is_petsitter)->toBeFalse();
});

test('database export includes notifications and preserves their user and booking references', function () {
    $petsitter = Petsitter::factory()->create();
    $user = User::factory()->create();
    $booking = Booking::create([
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'status' => 'pending',
        'total_price' => 25,
    ]);
    $notification = Notification::create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => 'booking.created',
        'message' => 'A tua reserva foi criada.',
    ]);
    $generatedSeeder = null;

    File::shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) use (&$generatedSeeder) {
        $generatedSeeder = $contents;

        return true;
    });

    $this->artisan('db:export')->assertSuccessful();

    expect($generatedSeeder)
        ->toContain("'user_id' => {$user->id}")
        ->toContain("'booking_id' => {$booking->id}")
        ->toContain("'message' => '{$notification->message}'")
        ->toContain('ImportExportedPetsitters::class)->import($users, $petsitters);')
        ->toContain('Booking::forceCreate($bookingCreateData);')
        ->toContain("array_key_exists('service_id', \$bookingCreateData)")
        ->toContain('Notification::forceCreate(array_intersect_key($notificationData, $notificationColumns));');
});

test('database export includes custom petsitter service names', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create([
        'petsitter_id' => $petsitter->user_id,
        'type' => 'Banho e tosquia',
        'price' => 20,
    ]);
    $generatedSeeder = null;

    File::shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) use (&$generatedSeeder) {
        $generatedSeeder = $contents;

        return true;
    });

    $this->artisan('db:export')->assertSuccessful();

    $this->assertDatabaseHas('petsitter_services', [
        'id' => $service->id,
        'type' => 'Banho e tosquia',
    ]);

    expect($generatedSeeder)
        ->toContain("'type' => 'Banho e tosquia'")
        ->toContain('PetsitterService::forceCreate(array_intersect_key($sData, $serviceColumns));');
});

test('database export preserves animal ids referenced by animal bookings', function () {
    $user = User::factory()->create();
    $animal = Animal::forceCreate([
        'id' => 701,
        'user_id' => $user->id,
        'name' => 'Animal com ID não sequencial',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'size' => 'Medium',
    ]);
    $generatedSeeder = null;

    File::shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) use (&$generatedSeeder) {
        $generatedSeeder = $contents;

        return true;
    });

    $this->artisan('db:export')->assertSuccessful();

    expect($generatedSeeder)
        ->toContain("'id' => {$animal->id}")
        ->toContain('Animal::forceCreate($animalCreateData);');
});

test('database export includes other domain data and excludes deleted records from the next snapshot', function () {
    $user = User::factory()->create();
    $note = 'Nota de suporte atualizada';
    DB::table('user_notes')->insert([
        'user_id' => $user->id,
        'admin_id' => null,
        'note' => $note,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $generatedSeeders = [];

    File::shouldReceive('put')->twice()->andReturnUsing(function ($path, $contents) use (&$generatedSeeders) {
        $generatedSeeders[] = $contents;

        return true;
    });

    $this->artisan('db:export')->assertSuccessful();
    DB::table('user_notes')->delete();
    $this->artisan('db:export')->assertSuccessful();

    expect($generatedSeeders[0])
        ->toContain($note)
        ->toContain("foreach (['user_notes', 'support_tickets'");
    expect($generatedSeeders[1])
        ->not->toContain($note)
        ->toContain("foreach (['user_notes', 'support_tickets'");
});

test('database export fails when the exported snapshot cannot be written', function () {
    File::shouldReceive('put')->once()->andReturn(false);

    $this->artisan('db:export')->assertExitCode(1);
});
