<?php


/**
 * Ficheiro PHP: FavouriteTest.php
 * PHP file: FavouriteTest.php
 */
use App\Models\Favourite;
use App\Models\Petsitter;
use App\Models\User;

test('unauthenticated user cannot access favourites endpoint', function () {
    $this->postJson('/api/favourites', [])->assertUnauthorized();
    $this->deleteJson('/api/favourites/1')->assertUnauthorized();
});

test('authenticated user can add a petsitter to favourites', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $response = $this->actingAs($user)->postJson('/api/favourites', [
        'petsitter_id' => $petsitter->user_id,
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'user_id' => $user->id,
            'petsitter_id' => $petsitter->user_id,
        ]);

    $this->assertDatabaseHas('favourites', [
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
    ]);
});

test('adding an already favourited petsitter returns existing favourite', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    Favourite::create([
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
    ]);

    $response = $this->actingAs($user)->postJson('/api/favourites', [
        'petsitter_id' => $petsitter->user_id,
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'user_id' => $user->id,
            'petsitter_id' => $petsitter->user_id,
        ]);

    // Ensure only one record exists
    $this->assertDatabaseCount('favourites', 1);
});

test('adding a favourite requires valid petsitter_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/favourites', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['petsitter_id']);
});

test('adding a favourite with non-existent petsitter fails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/favourites', [
        'petsitter_id' => 99999,
    ]);

    $response->assertUnprocessable();
});

test('authenticated user can remove a petsitter from favourites', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    Favourite::create([
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/favourites/{$petsitter->user_id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('favourites', [
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
    ]);
});

test('removing non-existent favourite returns 404', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $response = $this->actingAs($user)->deleteJson("/api/favourites/{$petsitter->user_id}");

    $response->assertNotFound()
        ->assertJson(['message' => 'Favourite not found']);
});

test('user cannot remove another user\'s favourite', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    Favourite::create([
        'user_id' => $user1->id,
        'petsitter_id' => $petsitter->user_id,
    ]);

    $response = $this->actingAs($user2)->deleteJson("/api/favourites/{$petsitter->user_id}");

    $response->assertNotFound();

    // Favourite still exists for user1
    $this->assertDatabaseHas('favourites', [
        'user_id' => $user1->id,
        'petsitter_id' => $petsitter->user_id,
    ]);
});
