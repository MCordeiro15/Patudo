<?php

use App\Models\Petsitter;
use App\Models\PetsitterAvailability;
use App\Models\User;

test('petsitter can toggle date availability on and off', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $user->id]);

    $date = '2026-11-15';

    // 1. Toggle ON
    $response = $this->actingAs($user)->postJson('/api/petsitters/calendar/toggle', [
        'date' => $date,
    ]);

    $response->assertOk()
        ->assertJson([
            'activated' => true,
            'date' => $date,
        ]);

    expect(PetsitterAvailability::where('petsitter_id', $petsitter->user_id)->whereDate('date', $date)->exists())->toBeTrue();

    // 2. Toggle OFF (same date)
    $responseOff = $this->actingAs($user)->postJson('/api/petsitters/calendar/toggle', [
        'date' => $date,
    ]);

    $responseOff->assertOk()
        ->assertJson([
            'activated' => false,
            'date' => $date,
        ]);

    expect(PetsitterAvailability::where('petsitter_id', $petsitter->user_id)->whereDate('date', $date)->exists())->toBeFalse();
});

test('unauthenticated user cannot toggle availability', function () {
    $response = $this->postJson('/api/petsitters/calendar/toggle', [
        'date' => '2026-11-15',
    ]);

    $response->assertUnauthorized();
});

test('regular user cannot toggle availability for another petsitter', function () {
    $user1 = User::factory()->create();
    $petsitter1 = Petsitter::factory()->create(['user_id' => $user1->id]);

    $user2 = User::factory()->create();
    $petsitter2 = Petsitter::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->postJson('/api/petsitters/calendar/toggle', [
        'petsitter_id' => $petsitter2->user_id,
        'date' => '2026-11-15',
    ]);

    $response->assertForbidden();
});

test('admin can toggle availability for any petsitter', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $user->id]);

    $date = '2026-11-20';

    $response = $this->actingAs($admin)->postJson('/api/petsitters/calendar/toggle', [
        'petsitter_id' => $petsitter->user_id,
        'date' => $date,
    ]);

    $response->assertOk()
        ->assertJson(['activated' => true]);

    expect(PetsitterAvailability::where('petsitter_id', $petsitter->user_id)->whereDate('date', $date)->exists())->toBeTrue();
});

test('toggle fails when user has no petsitter profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/petsitters/calendar/toggle', [
        'date' => '2026-11-15',
    ]);

    $response->assertNotFound();
});

test('client can get petsitter calendar within date window', function () {
    $petsitter = Petsitter::factory()->create();

    PetsitterAvailability::factory()->create([
        'petsitter_id' => $petsitter->user_id,
        'date' => '2026-11-01',
    ]);
    PetsitterAvailability::factory()->create([
        'petsitter_id' => $petsitter->user_id,
        'date' => '2026-11-10',
    ]);
    PetsitterAvailability::factory()->create([
        'petsitter_id' => $petsitter->user_id,
        'date' => '2026-11-20',
    ]);
    PetsitterAvailability::factory()->create([
        'petsitter_id' => $petsitter->user_id,
        'date' => '2026-12-05',
    ]);

    $response = $this->getJson("/api/petsitters/{$petsitter->user_id}/calendar?start_date=2026-11-05&end_date=2026-11-25");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['date' => '2026-11-10'])
        ->assertJsonFragment(['date' => '2026-11-20'])
        ->assertJsonMissing(['date' => '2026-11-01'])
        ->assertJsonMissing(['date' => '2026-12-05']);
});

test('calendar returns 404 for non-existent petsitter', function () {
    $response = $this->getJson('/api/petsitters/99999/calendar');

    $response->assertNotFound();
});
