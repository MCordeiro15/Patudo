<?php

/**
 * Testes da criação de reservas (KAN-215): disponibilidade do petsitter pelos dias da semana
 * e regras já existentes do POST /api/bookings.
 * Tests for booking creation (KAN-215): petsitter availability by weekday and the existing
 * POST /api/bookings rules.
 */
use App\Models\Animal;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;

/**
 * Cenário base: dono, animal dele, petsitter aprovado (segunda a sexta) com serviço a 20 €/dia.
 *
 * @return array{0: User, 1: Petsitter, 2: PetsitterService, 3: Animal}
 */
function bookingScenario(array $availability = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']): array
{
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id, 'size' => 'small']);
    $petsitter = Petsitter::factory()->approved()->create(['availability_days' => $availability]);
    $service = PetsitterService::create(['petsitter_id' => $petsitter->user_id, 'type' => 'pet-sitting', 'price' => 20]);

    return [$owner, $petsitter, $service, $animal];
}

/** Reserva de segunda a quarta da próxima semana (3 dias = 60 €), com overrides. */
function bookingPayload(Petsitter $petsitter, PetsitterService $service, Animal $animal, array $overrides = []): array
{
    $monday = now()->next('Monday');

    return array_merge([
        'petsitter_id' => $petsitter->user_id,
        'service_id' => $service->id,
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->copy()->addDays(2)->toDateString(),
        'animal_ids' => [$animal->id],
        'zone' => 'Ponta Delgada',
        'entrance_hour' => '10:00',
        'exit_hour' => '18:00',
        'message' => 'Ele gosta de passear de manhã.',
    ], $overrides);
}

test('a booking is created as pending with the total from the service price', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.user_id', $owner->id)
        ->assertJsonPath('data.zone', 'Ponta Delgada')
        ->assertJsonPath('data.message', 'Ele gosta de passear de manhã.');

    // 20 € x 3 dias = 60 €
    expect((float) Booking::first()->total_price)->toBe(60.0);
});

test('a petsitter cannot be booked with an animal size they do not accept', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $animal->update(['size' => 'Large']);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('animal_ids');

    expect(Booking::count())->toBe(0);
});

test('a petsitter accepts an animal whose size is listed regardless of case', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $animal->update(['size' => 'Medium']);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated();

    expect(Booking::count())->toBe(1);
});

test('creating a booking sends the petsitter an unread notification linked to the booking', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();

    $response = $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated();

    $bookingId = $response->json('data.id');
    $notification = Notification::where('user_id', $petsitter->user_id)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('booking')
        ->and($notification->message)->toBe('Novo pedido de reserva')
        ->and($notification->read_at)->toBeNull()
        ->and($notification->booking_id)->toBe($bookingId);
});

test('a day the petsitter does not work returns 422 on start_date with the day name', function () {
    // só segunda e terça; a reserva vai de segunda a quarta
    [$owner, $petsitter, $service, $animal] = bookingScenario(availability: ['monday', 'tuesday']);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date' => 'quarta-feira']);

    expect(Booking::count())->toBe(0);
});

test('a long booking needs every weekday', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $monday = now()->next('Monday');

    // segunda a segunda seguinte passa pelo fim de semana: a factory só trabalha de segunda a sexta
    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
            'end_date' => $monday->copy()->addDays(7)->toDateString(),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date' => 'sábado e domingo']);
});

test('a petsitter without availability days cannot be booked', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario(availability: []);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('start_date');
});

test('the user id comes from the session, not from the request', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $other = User::factory()->create();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, ['user_id' => $other->id]))
        ->assertCreated()
        ->assertJsonPath('data.user_id', $owner->id);
});

test('foreign animals and pending profiles are validation errors while approved sitters cannot book', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();

    // animal de outra pessoa
    $foreignAnimal = Animal::factory()->create();
    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, ['animal_ids' => [$foreignAnimal->id]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('animal_ids.0');

    // petsitter pendente
    $pending = Petsitter::factory()->create(['approval_status' => 'pending']);
    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, ['petsitter_id' => $pending->user_id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('petsitter_id');

    // reservar a si próprio
    $this->actingAs($petsitter->user)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertForbidden()
        ->assertJsonPath('message', 'Um petsitter não pode fazer reservas.');

    expect(Booking::count())->toBe(0);
});

test('exit hour before entrance hour on the same day returns 422', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $monday = now()->next('Monday')->toDateString();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
            'start_date' => $monday,
            'end_date' => $monday,
            'entrance_hour' => '18:00',
            'exit_hour' => '10:00',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('exit_hour');

    expect(Booking::count())->toBe(0);
});

test('the message is limited to 500 characters', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, ['message' => str_repeat('a', 501)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

test('a petsitter cannot create a booking (403)', function () {
    [, $petsitter, $service] = bookingScenario();

    // outro petsitter, aprovado (is_petsitter = true), com um animal seu
    $otherPetsitter = User::factory()->petsitter()->create();
    Petsitter::factory()->approved()->create(['user_id' => $otherPetsitter->id]);
    $ownAnimal = Animal::factory()->create(['user_id' => $otherPetsitter->id]);

    $this->actingAs($otherPetsitter)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $ownAnimal))
        ->assertForbidden()
        ->assertJsonPath('message', 'Um petsitter não pode fazer reservas.');

    expect(Booking::count())->toBe(0);
});

test('a candidate still pending approval can book as an owner', function () {
    [, $petsitter, $service] = bookingScenario();

    // candidatura pendente: ainda não é petsitter (is_petsitter = false)
    $candidate = Petsitter::factory()->create(['approval_status' => 'pending'])->user;
    $animal = Animal::factory()->create(['user_id' => $candidate->id]);

    $this->actingAs($candidate)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated();
});

test('a petsitter cannot receive overlapping bookings for different services', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $otherService = PetsitterService::create([
        'petsitter_id' => $petsitter->user_id,
        'type' => 'dog-walking',
        'price' => 15,
    ]);
    $monday = now()->next('Monday')->toDateString();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
            'start_date' => $monday,
            'end_date' => $monday,
            'entrance_hour' => '12:00',
            'exit_hour' => '13:00',
        ]))
        ->assertCreated();

    $this->postJson('/api/bookings', bookingPayload($petsitter, $otherService, $animal, [
        'start_date' => $monday,
        'end_date' => $monday,
        'entrance_hour' => '12:30',
        'exit_hour' => '13:30',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('start_date');

    expect(Booking::count())->toBe(1);
});

test('a booking can start exactly when the previous booking ends', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $monday = now()->next('Monday')->toDateString();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
            'start_date' => $monday,
            'end_date' => $monday,
            'entrance_hour' => '12:00',
            'exit_hour' => '13:00',
        ]))
        ->assertCreated();

    $this->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
        'start_date' => $monday,
        'end_date' => $monday,
        'entrance_hour' => '13:00',
        'exit_hour' => '14:00',
    ]))
        ->assertCreated();

    expect(Booking::count())->toBe(2);
});

test('booking availability check reports overlapping and consecutive hours', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();
    $monday = now()->next('Monday')->toDateString();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, [
            'start_date' => $monday,
            'end_date' => $monday,
            'entrance_hour' => '12:00',
            'exit_hour' => '13:00',
        ]))
        ->assertCreated();

    $this->getJson('/api/bookings/availability?'.http_build_query([
        'petsitter_id' => $petsitter->user_id,
        'start_date' => $monday,
        'end_date' => $monday,
        'entrance_hour' => '12:30',
        'exit_hour' => '13:30',
    ]))
        ->assertOk()
        ->assertJsonPath('available', false);

    $this->getJson('/api/bookings/availability?'.http_build_query([
        'petsitter_id' => $petsitter->user_id,
        'start_date' => $monday,
        'end_date' => $monday,
        'entrance_hour' => '13:00',
        'exit_hour' => '14:00',
    ]))
        ->assertOk()
        ->assertJsonPath('available', true);
});
