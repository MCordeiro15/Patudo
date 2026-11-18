<?php

/**
 * Testes da ligação entre bookings e serviços: o service_id é obrigatório ao criar, tem de ser do petsitter escolhido, define o preço total e aparece no resource.
 * Tests for the link between bookings and services: service_id is required on create, must belong to the chosen petsitter, sets the total price and shows up in the resource.
 */
use App\Models\Animal;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;

/** Dados válidos de uma reserva de 3 dias, para os testes alterarem só o que interessa. */
function validBookingPayload(Petsitter $petsitter, PetsitterService $service, Animal $animal): array
{
    return [
        'petsitter_id' => $petsitter->user_id,
        'service_id' => $service->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(), // 3 dias contados (início e fim)
        'animal_ids' => [$animal->id],
    ];
}

test('booking is created with the chosen service and its price', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'pet-sitting', 'price' => 20]);

    $response = $this->actingAs($owner)->postJson('/api/bookings', validBookingPayload($petsitter, $service, $animal));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.service_id', $service->id)
        ->assertJsonPath('data.service.name', 'pet-sitting')
        ->assertJsonPath('data.service.price', 20);

    // 20 € x 3 dias
    expect((float) $response->json('data.total_price'))->toBe(60.0);

    $this->assertDatabaseHas('bookings', [
        'user_id' => $owner->id,
        'petsitter_id' => $petsitter->user_id,
        'service_id' => $service->id,
    ]);
});

test('the total price ignores the petsitter base price and uses the service price', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create(['price_per_day' => 999]);
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $response = $this->actingAs($owner)->postJson('/api/bookings', validBookingPayload($petsitter, $service, $animal));

    $response->assertCreated();
    expect((float) $response->json('data.total_price'))->toBe(30.0);
});

test('booking requires a service_id', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $payload = validBookingPayload($petsitter, $service, $animal);
    unset($payload['service_id']);

    $this->actingAs($owner)->postJson('/api/bookings', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['service_id']);

    $this->assertDatabaseCount('bookings', 0);
});

test('booking rejects a service that belongs to another petsitter', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $other = Petsitter::factory()->approved()->create();
    $foreignService = PetsitterService::create(['petsitter_id' => $other->user_id, 'type' => 'passeio', 'price' => 10]);

    $this->actingAs($owner)->postJson('/api/bookings', validBookingPayload($petsitter, $foreignService, $animal))
        ->assertUnprocessable()->assertJsonValidationErrors(['service_id']);

    $this->assertDatabaseCount('bookings', 0);
});

test('booking rejects a service that does not exist', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $payload = validBookingPayload($petsitter, $service, $animal);
    $payload['service_id'] = 99999;

    $this->actingAs($owner)->postJson('/api/bookings', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['service_id']);
});

test('listing and showing bookings include the service', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $id = $this->actingAs($owner)->postJson('/api/bookings', validBookingPayload($petsitter, $service, $animal))
        ->json('data.id');

    $this->actingAs($owner)->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonPath('data.0.service.name', 'passeio')
        ->assertJsonPath('data.0.service.price', 10);

    $this->actingAs($owner)->getJson("/api/bookings/{$id}")
        ->assertOk()
        ->assertJsonPath('data.service.name', 'passeio');
});

test('an old booking without a service returns service as null', function () {
    $booking = Booking::factory()->create(['status' => 'pending']);

    $this->actingAs($booking->user)->getJson("/api/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.service_id', null)
        ->assertJsonPath('data.service', null);
});

test('a service that already has bookings cannot be deleted', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'passeio', 'price' => 10]);

    $this->actingAs($owner)->postJson('/api/bookings', validBookingPayload($petsitter, $service, $animal))->assertCreated();

    $this->actingAs($petsitter->user)->deleteJson("/api/services/{$service->id}")
        ->assertStatus(409);

    $this->assertDatabaseHas('petsitter_services', ['id' => $service->id]);
});
