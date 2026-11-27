<?php

/**
 * Ficheiro PHP: DashboardTest.php
 * PHP file: DashboardTest.php
 */
use App\Models\Petsitter;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('owners are redirected to petsitter search after login', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    $this->get(route('dashboard'))
        ->assertRedirect(route('petsitters.search'));
});

test('petsitters are redirected to their orders after login', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $this->postJson('/api/login', ['email' => $petsitter->user->email, 'password' => 'password'])
        ->assertOk();

    $this->get(route('dashboard'))
        ->assertRedirect(route('bookings.orders'));
});

test('administrators are redirected to administration after login', function () {
    $admin = User::factory()->admin()->create();

    $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertOk();

    $this->get(route('dashboard'))
        ->assertRedirect(route('admin.users'));
});
