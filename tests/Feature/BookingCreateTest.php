<?php

/**
 * Testes da criação de reservas (KAN-215): disponibilidade do petsitter pelos dias da semana,
 * pagamento fictício com o saldo do utilizador e regras já existentes do POST /api/bookings.
 * Tests for booking creation (KAN-215): petsitter availability by weekday, fictitious payment with
 * the user's balance and the existing POST /api/bookings rules.
 */
use App\Models\Animal;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;

/**
 * Cenário base: dono com saldo, animal dele, petsitter aprovado (segunda a sexta) com serviço a 20 €/dia.
 *
 * @return array{0: User, 1: Petsitter, 2: PetsitterService, 3: Animal}
 */
function bookingScenario(float $credit = 500, array $availability = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']): array
{
    $owner = User::factory()->create(['credit' => $credit]);
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
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

test('a booking is paid with the balance and created as pending', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario(credit: 100);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.user_id', $owner->id)
        ->assertJsonPath('data.zone', 'Ponta Delgada')
        ->assertJsonPath('data.message', 'Ele gosta de passear de manhã.');

    // 20 € x 3 dias = 60 €, descontados do saldo
    expect((float) $owner->fresh()->credit)->toBe(40.0);
});

test('without enough balance it returns 422 and nothing is created or charged', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario(credit: 59.99);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credit');

    expect(Booking::count())->toBe(0)
        ->and((float) $owner->fresh()->credit)->toBe(59.99);
});

test('the exact balance is enough and leaves it at zero', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario(credit: 60);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertCreated();

    expect((float) $owner->fresh()->credit)->toBe(0.0);
});

test('a day the petsitter does not work returns 422 on start_date with the day name', function () {
    // só segunda e terça; a reserva vai de segunda a quarta
    [$owner, $petsitter, $service, $animal] = bookingScenario(availability: ['monday', 'tuesday']);

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date' => 'quarta-feira']);

    expect(Booking::count())->toBe(0)
        ->and((float) $owner->fresh()->credit)->toBe(500.0);
});

test('a long booking needs every weekday', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario(credit: 1000);
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

test('animals of another user, a petsitter not approved and booking yourself return 422', function () {
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
        ->assertUnprocessable()
        ->assertJsonValidationErrors('petsitter_id');

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

    expect((float) $owner->fresh()->credit)->toBe(500.0);
});

test('the message is limited to 500 characters', function () {
    [$owner, $petsitter, $service, $animal] = bookingScenario();

    $this->actingAs($owner)
        ->postJson('/api/bookings', bookingPayload($petsitter, $service, $animal, ['message' => str_repeat('a', 501)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

test('the owner sees their own balance in /api/me', function () {
    $owner = User::factory()->create(['credit' => 123.45]);

    $this->actingAs($owner)->getJson('/api/me')->assertJsonPath('data.credit', 123.45);
});
