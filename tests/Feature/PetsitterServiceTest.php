<?php

/**
 * Testes dos endpoints de serviços dos petsitters: listar, criar, alterar e apagar, com as regras de permissão e o unique entre petsitter e tipo.
 * Tests for the petsitter services endpoints: list, create, update and delete, with permission rules and the unique petsitter/type constraint.
 */
use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;

test('unauthenticated user cannot use the services endpoints', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $this->getJson("/api/petsitters/{$petsitter->user_id}/services")->assertUnauthorized();
    $this->postJson("/api/petsitters/{$petsitter->user_id}/services", [])->assertUnauthorized();
    $this->patchJson('/api/services/1', [])->assertUnauthorized();
    $this->deleteJson('/api/services/1')->assertUnauthorized();
});

test('petsitter can create a service for themselves', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $response = $this->actingAs($petsitter->user)->postJson(
        "/api/petsitters/{$petsitter->user_id}/services",
        ['type' => 'passeio', 'price' => 10]
    );

    $response->assertCreated()
        ->assertJsonPath('data.type', 'passeio')
        ->assertJsonPath('data.price', 10)
        ->assertJsonPath('data.petsitter_id', $petsitter->user_id);

    $this->assertDatabaseHas('petsitter_services', [
        'petsitter_id' => $petsitter->user_id,
        'type' => 'passeio',
    ]);
});

test('petsitter can create a service with a custom name', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $this->actingAs($petsitter->user)->postJson(
        "/api/petsitters/{$petsitter->user_id}/services",
        ['type' => 'Banho e tosquia', 'price' => 20]
    )->assertCreated()
        ->assertJsonPath('data.type', 'Banho e tosquia');

    $this->assertDatabaseHas('petsitter_services', [
        'petsitter_id' => $petsitter->user_id,
        'type' => 'Banho e tosquia',
    ]);
});

test('a petsitter cannot have the same service type twice', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $this->actingAs($petsitter->user)->postJson(
        "/api/petsitters/{$petsitter->user_id}/services",
        ['type' => 'passeio', 'price' => 15]
    )->assertUnprocessable()->assertJsonValidationErrors(['type']);

    $this->assertDatabaseCount('petsitter_services', 1);
});

test('two different petsitters can have the same service type', function () {
    $a = Petsitter::factory()->approved()->create();
    $b = Petsitter::factory()->approved()->create();

    $this->actingAs($a->user)->postJson("/api/petsitters/{$a->user_id}/services", ['type' => 'passeio', 'price' => 10])->assertCreated();
    $this->actingAs($b->user)->postJson("/api/petsitters/{$b->user_id}/services", ['type' => 'passeio', 'price' => 12])->assertCreated();

    $this->assertDatabaseCount('petsitter_services', 2);
});

test('creating a service validates type and price', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $url = "/api/petsitters/{$petsitter->user_id}/services";

    $this->actingAs($petsitter->user)->postJson($url, [])
        ->assertUnprocessable()->assertJsonValidationErrors(['type', 'price']);

    $this->actingAs($petsitter->user)->postJson($url, ['type' => str_repeat('a', 256), 'price' => 5])
        ->assertUnprocessable()->assertJsonValidationErrors(['type']);

    $this->actingAs($petsitter->user)->postJson($url, ['type' => 'passeio', 'price' => -1])
        ->assertUnprocessable()->assertJsonValidationErrors(['price']);
});

test('another user cannot create a service for a petsitter', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $other = User::factory()->create();

    $this->actingAs($other)->postJson(
        "/api/petsitters/{$petsitter->user_id}/services",
        ['type' => 'passeio', 'price' => 10]
    )->assertForbidden();

    $this->assertDatabaseCount('petsitter_services', 0);
});

test('admin can create a service for any petsitter', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->postJson(
        "/api/petsitters/{$petsitter->user_id}/services",
        ['type' => 'pet-sitting', 'price' => 25]
    )->assertCreated();
});

test('services of an approved petsitter can be listed', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);
    PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'pet-sitting', 'price' => 25]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)->getJson("/api/petsitters/{$petsitter->user_id}/services")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('petsitter can update the price of their service', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $this->actingAs($petsitter->user)->patchJson("/api/services/{$service->id}", ['price' => 12])
        ->assertOk()
        ->assertJsonPath('data.price', 12)
        ->assertJsonPath('data.type', 'passeio');

    $this->actingAs($petsitter->user)->putJson("/api/services/{$service->id}", ['type' => 'passeio', 'price' => 13])
        ->assertOk();
});

test('admin can update a service for any petsitter', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->putJson("/api/services/{$service->id}", ['price' => 15])
        ->assertOk()
        ->assertJsonPath('data.price', 15)
        ->assertJsonPath('data.petsitter_id', $petsitter->user_id);

    $this->assertDatabaseHas('petsitter_services', [
        'id' => $service->id,
        'price' => 15,
    ]);
});

test('updating a service cannot create a duplicate type', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);
    $other = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'pet-sitting', 'price' => 25]);

    $this->actingAs($petsitter->user)->patchJson("/api/services/{$other->id}", ['type' => 'passeio'])
        ->assertUnprocessable()->assertJsonValidationErrors(['type']);
});

test('another user cannot update or delete a service', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);
    $other = User::factory()->create();

    $this->actingAs($other)->patchJson("/api/services/{$service->id}", ['price' => 1])->assertForbidden();
    $this->actingAs($other)->deleteJson("/api/services/{$service->id}")->assertForbidden();

    $this->assertDatabaseHas('petsitter_services', ['id' => $service->id, 'price' => 10]);
});

test('petsitter can delete their service', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $this->actingAs($petsitter->user)->deleteJson("/api/services/{$service->id}")->assertNoContent();

    $this->assertDatabaseMissing('petsitter_services', ['id' => $service->id]);
});
