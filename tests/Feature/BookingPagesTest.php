<?php

/**
 * Testes das rotas web das páginas de reserva: reserva confirmada, pedidos (petsitter) e reservas (dono).
 * Tests for the booking web pages routes: booking confirmed, orders (petsitter) and reservations (owner).
 */
use App\Models\Animal;
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
    $this->get('/orders')->assertRedirect(route('login'));
    $this->get('/reservations')->assertRedirect(route('login'));
});

test('authenticated user sees the booking confirmed page', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get('/booking-confirmed?booking=1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Owner/BookingConfirmed'));
});

test('petsitter sees the orders page', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);

    $this->actingAs($petsitter->user)
        ->get('/orders?booking='.$booking->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Petsitter/ReservationNotification'));
});

test('owner sees the reservations page', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);

    $this->actingAs($owner)
        ->get('/reservations?booking='.$booking->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Owner/ReservationNotificationToOwner'));
});

test('pending filter splits orders (pending) from reservations (not pending), as owner and as petsitter', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $pending = makeBookingForPages($owner, $petsitter);
    $approved = makeBookingForPages($owner, $petsitter);
    $approved->update(['status' => 'approved']);
    $cancelled = makeBookingForPages($owner, $petsitter);
    $cancelled->update(['status' => 'cancelled']);

    // reserva de outras pessoas: nunca aparece
    makeBookingForPages(User::factory()->create(), Petsitter::factory()->approved()->create());

    foreach ([$owner, $petsitter->user] as $viewer) {
        $orders = $this->actingAs($viewer)->getJson('/api/bookings?pending=1')->assertOk();
        expect(collect($orders->json('data'))->pluck('id')->all())->toBe([$pending->id]);

        $reservations = $this->actingAs($viewer)->getJson('/api/bookings?pending=0')->assertOk();
        expect(collect($reservations->json('data'))->pluck('id')->sort()->values()->all())
            ->toBe([$approved->id, $cancelled->id]);

        // sem filtro continuam a vir todas
        $this->actingAs($viewer)->getJson('/api/bookings')->assertOk()->assertJsonCount(3, 'data');
    }
});

test('booking list includes owner and petsitter names', function () {
    $owner = User::factory()->create(['name' => 'Ana Dona']);
    $petsitter = Petsitter::factory()->approved()->create();
    makeBookingForPages($owner, $petsitter);

    $this->actingAs($petsitter->user)
        ->getJson('/api/bookings?pending=1')
        ->assertOk()
        ->assertJsonPath('data.0.owner.name', 'Ana Dona')
        ->assertJsonPath('data.0.petsitter.name', $petsitter->user->name);
});

test('pending filter must be a boolean', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/bookings?pending=talvez')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pending');
});

test('a user outside the booking cannot load its data through the api', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);
    $stranger = User::factory()->create();

    // a página abre, mas a API que carrega a reserva recusa quem não faz parte dela
    $this->actingAs($stranger)
        ->getJson('/api/bookings/'.$booking->id)
        ->assertForbidden();
});

test('booking api includes owner and petsitter avatars, also on show', function () {
    $owner = User::factory()->create(['name' => 'Ana Dona']);
    $petsitter = Petsitter::factory()->approved()->create(['profile_photo' => null]);
    $booking = makeBookingForPages($owner, $petsitter);

    // lista (páginas Pedidos/Reservas): avatar com as iniciais quando não há foto
    $this->actingAs($owner)
        ->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonPath('data.0.owner.avatar_url', 'https://ui-avatars.com/api/?name=Ana+Dona&background=random')
        ->assertJsonPath('data.0.petsitter.avatar_url', 'https://ui-avatars.com/api/?name='.urlencode($petsitter->user->name).'&background=random');

    // detalhe (?booking=ID): o show também traz o dono e o petsitter
    $this->actingAs($owner)
        ->getJson('/api/bookings/'.$booking->id)
        ->assertOk()
        ->assertJsonPath('data.owner.name', 'Ana Dona')
        ->assertJsonPath('data.petsitter.name', $petsitter->user->name);
});

test('petsitter can open the animal card of an animal in their booking, other petsitters cannot', function () {
    $owner = User::factory()->create();
    $animal = Animal::factory()->create(['user_id' => $owner->id]);
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = makeBookingForPages($owner, $petsitter);
    $booking->animals()->attach($animal->id);

    $this->actingAs($petsitter->user)
        ->getJson('/api/animals/'.$animal->id)
        ->assertOk()
        ->assertJsonPath('data.name', $animal->name);

    $otherPetsitter = Petsitter::factory()->approved()->create();
    $this->actingAs($otherPetsitter->user)
        ->getJson('/api/animals/'.$animal->id)
        ->assertForbidden();
});
