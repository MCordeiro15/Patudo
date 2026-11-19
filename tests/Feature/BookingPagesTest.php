<?php

/**
 * Testes das rotas web das páginas de reserva: reserva confirmada e notificação de reserva (dono vs petsitter).
 * Tests for the booking web pages routes: booking confirmed and booking notification (owner vs petsitter).
 */
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** Cria uma reserva pendente entre um dono e um petsitter. */
function makeBookingForPages(User $owner, Petsitter $petsitter): Booking
{
    return Booking::create([
        'user_id' => $owner->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
        'total_price' => 30,
    ]);
}

test('guest is redirected to login on the booking pages', function () {
    $this->get('/booking-confirmed?booking=1')->assertRedirect(route('login'));
    $this->get('/reservation-notification?booking=1')->assertRedirect(route('login'));
});

test('authenticated user sees the booking confirmed page', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get('/booking-confirmed?booking=1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Owner/BookingConfirmed'));
});

test('booking owner sees the owner notification page', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);

    $this->actingAs($owner)
        ->get('/reservation-notification?booking='.$booking->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Owner/ReservationNotificationToOwner'));
});

test('booking petsitter sees the petsitter notification page', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);

    $this->actingAs($petsitter->user)
        ->get('/reservation-notification?booking='.$booking->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Petsitter/ReservationNotification'));
});

test('a user outside the booking cannot see the notification page', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get('/reservation-notification?booking='.$booking->id)
        ->assertForbidden();
});

test('notification page returns 404 for a missing or unknown booking', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->get('/reservation-notification')->assertNotFound();
    $this->actingAs($owner)->get('/reservation-notification?booking=999999')->assertNotFound();
});
