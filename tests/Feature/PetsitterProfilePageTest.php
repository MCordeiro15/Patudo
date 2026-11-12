<?php


/**
 * Ficheiro PHP: PetsitterProfilePageTest.php
 * PHP file: PetsitterProfilePageTest.php
 */
use App\Models\Petsitter;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('petsitter can view their own profile with its data', function () {
    $petsitter = Petsitter::factory()->create([
        'biography' => 'Cuido de cães há vários anos.',
        'approval_status' => 'pending',
    ]);

    $this->actingAs($petsitter->user)
        ->get('/petsitters/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('isOwnProfile', true)
            ->where('petsitter.user_id', $petsitter->user_id)
            ->where('petsitter.name', $petsitter->user->name)
            ->where('petsitter.biography', 'Cuido de cães há vários anos.')
        );
});

test('user without a petsitter profile is redirected to owner profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/petsitters/profile')
        ->assertRedirect(route('owner.profile'));
});

test('user can view the selected approved petsitter profile without private data', function () {
    $viewer = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $this->actingAs($viewer)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('isOwnProfile', false)
            ->where('isAdmin', false)
            ->where('petsitter.user_id', $petsitter->user_id)
            ->where('petsitter.name', $petsitter->user->name)
            ->missing('petsitter.identification_documents')
        );
});

test('user cannot view another petsitter profile that is not approved', function () {
    $viewer = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $this->actingAs($viewer)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertNotFound();
});

test('petsitter profile pages require authentication', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $this->get('/petsitters/profile')->assertRedirect(route('login'));
    $this->get('/petsitters/'.$petsitter->user_id)->assertRedirect(route('login'));
});

test('admin can edit any petsitter profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $petsitter = Petsitter::factory()->approved()->create([
        'identification_documents' => 'some_document.pdf',
    ]);

    $this->actingAs($admin)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('isOwnProfile', false)
            ->where('isAdmin', true)
            ->where('petsitter.user_id', $petsitter->user_id)
            ->where('petsitter.identification_documents', 'some_document.pdf')
        );
});

test('admin without petsitter profile is redirected to owner profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/petsitters/profile')
        ->assertRedirect(route('owner.profile'));
});