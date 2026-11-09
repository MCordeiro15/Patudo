<?php

use App\Models\SupportTicket;
use App\Models\User;

test('unauthenticated user cannot access support tickets endpoint', function () {
    $this->getJson('/api/support-tickets')->assertUnauthorized();
    $this->postJson('/api/support-tickets', [])->assertUnauthorized();
});

test('authenticated user can open a complaint ticket', function () {
    $user = User::factory()->create();
    $reportedUser = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/support-tickets', [
        'reported_user_id' => $reportedUser->id,
        'subject' => 'Inappropriate behavior',
        'description' => 'This petsitter was rude during the service.',
        'type' => 'complaint',
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'subject' => 'Inappropriate behavior',
            'type' => 'complaint',
            'status' => 'open',
        ]);

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'reported_user_id' => $reportedUser->id,
        'status' => 'open',
        'type' => 'complaint',
    ]);
});

test('authenticated user can open a help ticket without reported user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/support-tickets', [
        'subject' => 'Need help with my account',
        'description' => 'I cannot update my profile.',
        'type' => 'help',
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'subject' => 'Need help with my account',
            'type' => 'help',
            'status' => 'open',
        ]);

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'reported_user_id' => null,
        'type' => 'help',
    ]);
});

test('opening a ticket requires valid fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/support-tickets', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'description', 'type']);
});

test('opening a ticket with invalid type fails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/support-tickets', [
        'subject' => 'Test',
        'description' => 'Test description',
        'type' => 'invalid_type',
    ]);

    $response->assertUnprocessable();
});

test('opening a ticket with non-existent reported user fails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/support-tickets', [
        'reported_user_id' => 99999,
        'subject' => 'Test',
        'description' => 'Test description',
        'type' => 'complaint',
    ]);

    $response->assertUnprocessable();
});

test('regular user only sees their own tickets', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    SupportTicket::create([
        'user_id' => $user1->id,
        'subject' => 'User 1 ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    SupportTicket::create([
        'user_id' => $user2->id,
        'subject' => 'User 2 ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user1)->getJson('/api/support-tickets');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['subject' => 'User 1 ticket'])
        ->assertJsonMissing(['subject' => 'User 2 ticket']);
});

test('admin user sees all tickets', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    SupportTicket::create([
        'user_id' => $user1->id,
        'subject' => 'User 1 ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    SupportTicket::create([
        'user_id' => $user2->id,
        'subject' => 'User 2 ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->getJson('/api/support-tickets');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['subject' => 'User 1 ticket'])
        ->assertJsonFragment(['subject' => 'User 2 ticket']);
});

test('regular user can view their own ticket', function () {
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'My ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user)->getJson("/api/support-tickets/{$ticket->id}");

    $response->assertOk()
        ->assertJsonFragment(['subject' => 'My ticket']);
});

test('regular user cannot view another user ticket', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user2->id,
        'subject' => 'User 2 ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user1)->getJson("/api/support-tickets/{$ticket->id}");

    $response->assertForbidden();
});

test('admin can view any ticket', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'help',
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->getJson("/api/support-tickets/{$ticket->id}");

    $response->assertOk()
        ->assertJsonFragment(['subject' => 'User ticket']);
});

test('admin can claim a ticket', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'open',
    ]);

    $response = $this->actingAs($admin)->postJson("/api/support-tickets/{$ticket->id}/claim");

    $response->assertOk()
        ->assertJsonFragment([
            'claimed_by' => $admin->id,
            'status' => 'in_progress',
        ]);

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'claimed_by' => $admin->id,
        'status' => 'in_progress',
    ]);
});

test('regular user cannot claim a ticket', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user2->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user1)->postJson("/api/support-tickets/{$ticket->id}/claim");

    $response->assertForbidden();
});

test('claiming an already claimed ticket returns conflict', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'open',
    ]);

    $ticket->update(['claimed_by' => $admin1->id, 'status' => 'in_progress']);

    $response = $this->actingAs($admin2)->postJson("/api/support-tickets/{$ticket->id}/claim");

    $response->assertConflict()
        ->assertJson(['message' => 'Ticket already claimed']);
});

test('admin can close a ticket', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'in_progress',
        'claimed_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->postJson("/api/support-tickets/{$ticket->id}/close");

    $response->assertOk()
        ->assertJsonFragment(['status' => 'closed']);

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'closed',
    ]);
});

test('regular user cannot close a ticket', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user2->id,
        'subject' => 'User ticket',
        'description' => 'Description',
        'type' => 'complaint',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user1)->postJson("/api/support-tickets/{$ticket->id}/close");

    $response->assertForbidden();
});
