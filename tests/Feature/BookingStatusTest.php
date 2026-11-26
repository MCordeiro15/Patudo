<?php

/**
 * Testes da alteração do estado das reservas (PATCH /api/bookings/{booking}/status) e da listagem do petsitter.
 * Tests for changing a booking's status (PATCH /api/bookings/{booking}/status) and for the petsitter's listing.
 */
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Petsitter;
use App\Models\User;

/** Cria uma reserva entre um dono e um petsitter, no estado pedido. */
function makeBookingWithStatus(User $owner, Petsitter $petsitter, string $status = 'pending'): Booking
{
    return Booking::create([
        'user_id' => $owner->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'status' => $status,
        'total_price' => 30,
    ]);
}

test('petsitter can make the allowed status changes and notify the owner', function (string $from, string $to, string $message) {
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus(User::factory()->create(), $petsitter, $from);

    $this->actingAs($petsitter->user)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => $to])
        ->assertOk()
        ->assertJsonPath('data.status', $to);

    expect($booking->fresh()->status)->toBe($to);
    $notification = Notification::where('user_id', $booking->user_id)->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('booking')
        ->and($notification->message)->toBe($message)
        ->and($notification->read_at)->toBeNull()
        ->and($notification->booking_id)->toBe($booking->id);
})->with([
    'aceitar' => ['pending', 'approved', 'A reserva foi aceite.'],
    'recusar' => ['pending', 'rejected', 'A reserva foi recusada.'],
    'concluir' => ['approved', 'completed', 'A reserva foi concluída.'],
]);

test('owner can cancel a pending or approved booking and notify the petsitter', function (string $from) {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus($owner, $petsitter, $from);

    $this->actingAs($owner)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $notification = Notification::where('user_id', $petsitter->user_id)->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('booking')
        ->and($notification->message)->toBe('A reserva foi cancelada.')
        ->and($notification->read_at)->toBeNull()
        ->and($notification->booking_id)->toBe($booking->id);
})->with(['pending', 'approved']);

test('a status change that does not exist returns 422 and keeps the status', function (string $from, string $to) {
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus(User::factory()->create(), $petsitter, $from);

    $this->actingAs($petsitter->user)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => $to])
        ->assertUnprocessable();

    expect($booking->fresh()->status)->toBe($from);
})->with([
    'concluir um pendente' => ['pending', 'completed'],
    'aceitar um concluído' => ['completed', 'approved'],
    'aceitar um recusado' => ['rejected', 'approved'],
    'recusar um aceite' => ['approved', 'rejected'],
]);

test('an unknown status is a validation error', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus(User::factory()->create(), $petsitter);

    $this->actingAs($petsitter->user)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'talvez'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('only the petsitter of the booking can accept, reject or complete it', function (string $from, string $to) {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus($owner, $petsitter, $from);

    // o dono da reserva não é o petsitter: 403
    $this->actingAs($owner)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => $to])
        ->assertForbidden();

    // outro petsitter, de fora da reserva: 403
    $other = Petsitter::factory()->approved()->create();
    $this->actingAs($other->user)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => $to])
        ->assertForbidden();

    expect($booking->fresh()->status)->toBe($from);
})->with([
    'aceitar' => ['pending', 'approved'],
    'recusar' => ['pending', 'rejected'],
    'concluir' => ['approved', 'completed'],
]);

test('the petsitter cannot cancel the booking (only the owner can)', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingWithStatus(User::factory()->create(), $petsitter);

    $this->actingAs($petsitter->user)
        ->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'cancelled'])
        ->assertForbidden();
});

test('guest cannot change a booking status', function () {
    $booking = makeBookingWithStatus(User::factory()->create(), Petsitter::factory()->approved()->create());

    $this->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'approved'])
        ->assertUnauthorized();
});

test('the petsitter sees their bookings in every status, newest first', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $owner = User::factory()->create();

    $statuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
    $ids = [];
    foreach ($statuses as $i => $status) {
        $booking = makeBookingWithStatus($owner, $petsitter, $status);
        // created_at diferente para a ordem ser previsível
        $booking->forceFill(['created_at' => now()->subDays(10 - $i)])->save();
        $ids[] = $booking->id;
    }

    // reserva de outro petsitter: nunca aparece
    makeBookingWithStatus($owner, Petsitter::factory()->approved()->create());

    $response = $this->actingAs($petsitter->user)->getJson('/api/bookings')->assertOk();

    // todos os estados, da mais recente para a mais antiga
    expect(collect($response->json('data'))->pluck('id')->all())->toBe(array_reverse($ids));
    expect(collect($response->json('data'))->pluck('status')->sort()->values()->all())
        ->toBe(collect($statuses)->sort()->values()->all());

    // cada reserva traz o dono, os animais, as datas, o preço e o estado
    $response->assertJsonStructure(['data' => [['owner' => ['name'], 'animals', 'start_date', 'end_date', 'total_price', 'status']]]);
});
