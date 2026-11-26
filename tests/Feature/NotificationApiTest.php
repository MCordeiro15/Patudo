<?php

/**
 * Testes de Feature para os endpoints de Notificações API.
 * Feature tests for Notification API endpoints.
 */
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Petsitter;
use App\Models\User;

test('unauthenticated user cannot access notifications', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
    $this->postJson('/api/notifications', [])->assertUnauthorized();
});

test('user can list their own notifications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Nova reserva recebida',
    ]);

    Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'review',
        'message' => 'Nova avaliação',
    ]);

    $response = $this->actingAs($user)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['message' => 'Nova reserva recebida'])
        ->assertJsonPath('unread_count', 1);
});

test('booking notifications include a link to the reservation and count as unread', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $booking = Booking::create([
        'user_id' => $owner->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => '2040-04-09',
        'end_date' => '2040-04-11',
        'status' => 'approved',
        'total_price' => 30,
    ]);
    $notification = Notification::create([
        'user_id' => $owner->id,
        'booking_id' => $booking->id,
        'type' => 'booking',
        'message' => 'A reserva foi aceite.',
    ]);

    $this->actingAs($owner)->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('data.0.booking_id', $booking->id)
        ->assertJsonPath('data.0.booking_start_date', '2040-04-09')
        ->assertJsonPath('data.0.booking_url', "/reservations?booking={$booking->id}")
        ->assertJsonPath('data.0.read', false);
});

test('user notifications are ordered newest first', function () {
    $user = User::factory()->create();

    Notification::create([
        'user_id' => $user->id,
        'type' => 'old',
        'message' => 'Notificação antiga',
    ]);

    sleep(1);

    Notification::create([
        'user_id' => $user->id,
        'type' => 'new',
        'message' => 'Notificação recente',
    ]);

    $response = $this->actingAs($user)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.message', 'Notificação recente')
        ->assertJsonPath('data.1.message', 'Notificação antiga');
});

test('user can view their own notification', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Reserva confirmada',
    ]);

    $response = $this->actingAs($user)->getJson("/api/notifications/{$notification->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $notification->id)
        ->assertJsonPath('data.message', 'Reserva confirmada');
});

test('user cannot view another users notification', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'booking',
        'message' => 'Segredo',
    ]);

    $this->actingAs($user)->getJson("/api/notifications/{$notification->id}")->assertForbidden();
});

test('admin can view any notification', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Visível para admin',
    ]);

    $response = $this->actingAs($admin)->getJson("/api/notifications/{$notification->id}");

    $response->assertOk()
        ->assertJsonPath('data.message', 'Visível para admin');
});

test('regular user cannot create notification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/notifications', [
        'user_id' => $user->id,
        'type' => 'test',
        'message' => 'Não deve funcionar',
    ])->assertForbidden();
});

test('admin can create notification for any user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->postJson('/api/notifications', [
        'user_id' => $user->id,
        'type' => 'system',
        'message' => 'Manutenção agendada',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'system')
        ->assertJsonPath('data.message', 'Manutenção agendada')
        ->assertJsonPath('data.user_id', $user->id);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'system',
        'message' => 'Manutenção agendada',
    ]);
});

test('create notification fails when validation fails', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/notifications', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'type', 'message']);
});

test('user can mark their notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Pendente',
        'read_at' => null,
    ]);
    $otherNotification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Outra notificação',
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)->patchJson("/api/notifications/{$notification->id}/read");

    $response->assertOk()
        ->assertJsonPath('data.read', true);

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();

    $listResponse = $this->actingAs($user)->getJson('/api/notifications');

    $listResponse
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $listedNotifications = collect($listResponse->json('data'))->keyBy('id');
    expect($listedNotifications[$notification->id]['read'])->toBeTrue()
        ->and($listedNotifications[$otherNotification->id]['read'])->toBeFalse();

    $otherNotification->refresh();
    expect($otherNotification->read_at)->toBeNull();
});

test('user can mark their notification as unread', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Lida anteriormente',
        'read_at' => now(),
    ]);
    $otherNotification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Continua lida',
        'read_at' => now(),
    ]);

    $response = $this->actingAs($user)->patchJson("/api/notifications/{$notification->id}/unread");

    $response->assertOk()
        ->assertJsonPath('data.read', false)
        ->assertJsonPath('data.read_at', null);

    $notification->refresh();
    expect($notification->read_at)->toBeNull();

    $listResponse = $this->actingAs($user)->getJson('/api/notifications');
    $listResponse
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $listedNotifications = collect($listResponse->json('data'))->keyBy('id');
    expect($listedNotifications[$notification->id]['read'])->toBeFalse()
        ->and($listedNotifications[$otherNotification->id]['read'])->toBeTrue();
});

test('user cannot mark another users notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'booking',
        'message' => 'Não é sua',
    ]);

    $this->actingAs($user)->patchJson("/api/notifications/{$notification->id}/read")->assertForbidden();
});

test('user cannot mark another users notification as unread', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'booking',
        'message' => 'Não é sua',
        'read_at' => now(),
    ]);

    $this->actingAs($user)->patchJson("/api/notifications/{$notification->id}/unread")->assertForbidden();
});

test('user can delete their own notification', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Apagar',
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/notifications/{$notification->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('user cannot delete another users notification', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $otherUser->id,
        'type' => 'booking',
        'message' => 'Não apague',
    ]);

    $this->actingAs($user)->deleteJson("/api/notifications/{$notification->id}")->assertForbidden();
});

test('admin can delete any notification', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'booking',
        'message' => 'Admin pode apagar',
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/notifications/{$notification->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('notification resource returns read state', function () {
    $user = User::factory()->create();

    $unread = Notification::create([
        'user_id' => $user->id,
        'type' => 'unread',
        'message' => 'Não lida',
        'read_at' => null,
    ]);

    $read = Notification::create([
        'user_id' => $user->id,
        'type' => 'read',
        'message' => 'Lida',
        'read_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $data = $response->json('data');
    $unreadResult = collect($data)->firstWhere('id', $unread->id);
    $readResult = collect($data)->firstWhere('id', $read->id);

    expect($unreadResult['read'])->toBeFalse();
    expect($readResult['read'])->toBeTrue();
});
