<?php

use App\Models\Animal;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Petsitter;
use App\Models\SupportTicket;
use App\Models\User;

test('admin user list is paginated in groups of eight and supports search and filters', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(9)->create();

    $target = User::factory()->petsitter()->create([
        'name' => 'Pessoa Procurada',
        'email' => 'procurada@example.test',
        'account_status' => 'suspended',
    ]);

    $this->actingAs($admin)->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 8)
        ->assertJsonCount(8, 'data');

    $this->getJson('/api/admin/users?search=procurada&type=petsitter&status=suspended')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $target->id);
});

test('admin can export the filtered user list as csv', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create([
        'name' => 'CSV Pessoa',
        'email' => 'csv@example.test',
    ]);

    $response = $this->actingAs($admin)
        ->get('/api/admin/users/export?search=csv');

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())
        ->toContain('id,name,email')
        ->toContain((string) $target->id)
        ->toContain('csv@example.test');
});

test('admin can see user account data, counts, and recent activity', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->petsitter()->create();
    Animal::factory()->create(['user_id' => $user->id]);
    $petsitter = Petsitter::factory()->create();
    Booking::create([
        'user_id' => $user->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'status' => 'pending',
        'total_price' => 100,
    ]);
    SupportTicket::create([
        'user_id' => $user->id,
        'subject' => 'Ajuda',
        'description' => 'Pedido de ajuda',
        'type' => 'help',
        'status' => 'open',
    ]);

    $this->actingAs($admin)->getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.account_status', 'active')
        ->assertJsonPath('data.counts.animals', 1)
        ->assertJsonPath('data.counts.bookings', 1)
        ->assertJsonPath('data.counts.support_tickets', 1)
        ->assertJsonFragment(['type' => 'animal']);
});

test('admin can suspend a user and send a notification warning', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/admin/users/{$user->id}/suspend", [
            'duration_days' => 7,
            'reason' => 'Violação das regras',
            'message' => 'A sua conta foi temporariamente suspensa.',
        ])
        ->assertOk()
        ->assertJsonPath('data.account_status', 'suspended');

    $this->postJson("/api/admin/users/{$user->id}/warning", [
        'message' => 'Por favor, reveja as regras da comunidade.',
    ])->assertCreated()
        ->assertJsonPath('data.type', 'admin_warning')
        ->assertJsonPath('data.message', 'Por favor, reveja as regras da comunidade.');

    expect(Notification::where('user_id', $user->id)->count())->toBe(2);

    $this->getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonFragment([
            'type' => 'notification',
            'description' => 'Aviso enviado pela administração',
        ]);
});

test('admin can create and read internal notes while non admins are forbidden', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/admin/users/{$user->id}/notes", ['note' => 'Nota interna'])
        ->assertCreated()
        ->assertJsonPath('data.note', 'Nota interna');

    $this->getJson("/api/admin/users/{$user->id}/notes")
        ->assertOk()
        ->assertJsonPath('data.0.note', 'Nota interna');

    $this->actingAs(User::factory()->create())
        ->getJson("/api/admin/users/{$user->id}/notes")
        ->assertForbidden();
});

test('guests and non admins cannot access admin user endpoints', function () {
    $user = User::factory()->create();

    $this->getJson('/api/admin/users')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/admin/users/{$user->id}")
        ->assertForbidden();
});
