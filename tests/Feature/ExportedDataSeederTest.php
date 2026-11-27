<?php

use App\Models\Petsitter;
use App\Models\User;
use App\Services\ImportExportedPetsitters;

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
