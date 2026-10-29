<?php

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
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
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

    $response = $this->actingAs($admin)->postJson("/api/users/{$targetUser->id}/suspend");

    $response->assertOk();
    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'account_status' => 'suspended',
    ]);
});
