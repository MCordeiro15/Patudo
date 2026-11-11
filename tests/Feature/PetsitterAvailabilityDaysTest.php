<?php

use App\Models\Petsitter;
use App\Models\User;

test('petsitter can update their availability days', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday', 'wednesday', 'friday'],
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'availability_days' => ['monday', 'wednesday', 'friday'],
        ]);

    $petsitter->refresh();
    expect($petsitter->availability_days)->toBe(['monday', 'wednesday', 'friday']);
});

test('petsitter can set all seven days', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    ]);

    $response->assertOk();

    $petsitter->refresh();
    expect($petsitter->availability_days)->toHaveCount(7);
});

test('petsitter can set empty availability days', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => [],
    ]);

    $response->assertOk();

    $petsitter->refresh();
    expect($petsitter->availability_days)->toBe([]);
});

test('unauthenticated user cannot update availability days', function () {
    $response = $this->putJson('/api/petsitters/availability/days', [
        'days' => ['monday'],
    ]);

    $response->assertUnauthorized();
});

test('user without petsitter profile cannot update availability days', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday'],
    ]);

    $response->assertNotFound();
});

test('invalid day value returns 422', function () {
    $user = User::factory()->create();
    Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday', 'invalidday'],
    ]);

    $response->assertUnprocessable();
});

test('duplicate days return 422', function () {
    $user = User::factory()->create();
    Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday', 'monday', 'wednesday'],
    ]);

    $response->assertUnprocessable();
});

test('more than 7 days returns 422', function () {
    $user = User::factory()->create();
    Petsitter::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'monday'],
    ]);

    $response->assertUnprocessable();
});

test('GET petsitter returns availability_days in resource', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create([
        'user_id' => $user->id,
        'availability_days' => ['monday', 'wednesday', 'friday'],
    ]);

    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)->getJson("/api/petsitters/{$petsitter->user_id}");

    $response->assertOk()
        ->assertJsonPath('data.availability_days', ['monday', 'wednesday', 'friday']);
});

test('petsitter can update availability days to override previous value', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create([
        'user_id' => $user->id,
        'availability_days' => ['monday', 'tuesday'],
    ]);

    $response = $this->actingAs($user)->putJson('/api/petsitters/availability/days', [
        'days' => ['wednesday', 'thursday'],
    ]);

    $response->assertOk();

    $petsitter->refresh();
    expect($petsitter->availability_days)->toBe(['wednesday', 'thursday']);
});
