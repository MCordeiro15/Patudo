<?php

/**
 * Ficheiro PHP: UserTest.php
 * PHP file: UserTest.php
 */
use App\Models\User;

test('unauthenticated user cannot access user endpoints', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

test('regular user cannot list all users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/users')->assertForbidden();
});

test('admin can list all users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $response = $this->actingAs($admin)->getJson('/api/users');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin cannot create a user with digits in the name', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->postJson('/api/users', [
        'name' => 'Ana2',
        'email' => 'ana@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertUnprocessable()->assertJsonValidationErrors('name');
});

test('user can view their own profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/users/{$user->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('user cannot view another users profile', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->getJson("/api/users/{$otherUser->id}")->assertForbidden();
});

test('user can update their own profile', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->putJson("/api/users/{$user->id}", [
        'name' => 'Érica da Conceição',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Érica da Conceição');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Érica da Conceição',
    ]);
});

test('user cannot update their profile with digits in the name', function () {
    $user = User::factory()->create(['name' => 'Ana Silva']);

    $this->actingAs($user)->putJson("/api/users/{$user->id}", [
        'name' => 'Ana2 Silva',
    ])->assertUnprocessable()->assertJsonValidationErrors('name');

    expect($user->fresh()->name)->toBe('Ana Silva');
});

test('user cannot update another users profile', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->putJson("/api/users/{$otherUser->id}", [
        'name' => 'Hacked Name',
    ])->assertForbidden();
});

test('regular user cannot suspend another user', function () {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($user)->postJson("/api/users/{$targetUser->id}/suspend")->assertForbidden();
});

test('admin can suspend a user', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin)->postJson("/api/users/{$targetUser->id}/suspend", [
        'duration_days' => 7,
        'reason' => 'Violação das regras',
        'message' => 'A sua conta foi temporariamente suspensa.',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'account_status' => 'suspended',
    ]);
});
