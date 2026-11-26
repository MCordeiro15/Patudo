<?php

use App\Models\Petsitter;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Factories\BookingFactory;

function createReport(User $reporter, User $reportedUser, string $status = 'open', string $subject = 'Relato'): SupportTicket
{
    return SupportTicket::create([
        'user_id' => $reporter->id,
        'reported_user_id' => $reportedUser->id,
        'subject' => $subject,
        'description' => 'Descrição da denúncia',
        'type' => 'complaint',
        'status' => $status,
    ]);
}

test('admin reports have status tabs, counts, and search', function () {
    $admin = User::factory()->admin()->create();
    $reporter = User::factory()->create();
    $reportedUser = User::factory()->create(['name' => 'Pessoa Denunciada']);

    createReport($reporter, $reportedUser, 'open', 'Relato de teste');
    createReport($reporter, $reportedUser, 'in_progress', 'Outro relato');
    createReport($reporter, $reportedUser, 'closed', 'Relato fechado');
    createReport($reporter, $reportedUser, 'archived', 'Relato arquivado');

    $this->actingAs($admin)
        ->getJson('/api/admin/reports?tab=open&search=teste')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.subject', 'Relato de teste')
        ->assertJsonPath('counts.open', 2)
        ->assertJsonPath('counts.closed', 1)
        ->assertJsonPath('counts.archived', 1);

    $this->getJson('/api/admin/reports?tab=closed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'closed');

    $this->getJson('/api/admin/reports?tab=archived')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'archived');
});

test('admin can inspect a report and archive or resolve it with an optional internal note', function () {
    $admin = User::factory()->admin()->create();
    $reporter = User::factory()->create();
    $reportedUser = User::factory()->create(['is_petsitter' => true]);
    Petsitter::factory()->approved()->create(['user_id' => $reportedUser->id]);
    $booking = BookingFactory::new()->create(['petsitter_id' => $reportedUser->id]);
    Review::create([
        'booking_id' => $booking->id,
        'petsitter_id' => $reportedUser->id,
        'star_rating' => 4,
        'comment' => 'Bom serviço.',
        'visible' => true,
    ]);
    $report = createReport($reporter, $reportedUser);
    createReport($reporter, $reportedUser, 'closed', 'Denúncia anterior');

    $this->actingAs($admin)
        ->getJson("/api/admin/reports/{$report->id}")
        ->assertOk()
        ->assertJsonPath('data.reporter.id', $reporter->id)
        ->assertJsonPath('data.reported_user.id', $reportedUser->id)
        ->assertJsonPath('data.description', 'Descrição da denúncia')
        ->assertJsonStructure(['data' => ['reported_user' => ['bookings', 'reviews', 'previous_reports']]])
        ->assertJsonCount(1, 'data.reported_user.bookings')
        ->assertJsonCount(1, 'data.reported_user.reviews')
        ->assertJsonPath('data.reported_user.previous_reports.0.subject', 'Denúncia anterior');

    $this->postJson("/api/admin/reports/{$report->id}/archive", ['note' => 'Nota interna'])
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $this->assertDatabaseHas('user_notes', [
        'user_id' => $reportedUser->id,
        'admin_id' => $admin->id,
        'note' => 'Nota interna',
    ]);

    $warningReport = createReport($reporter, $reportedUser);
    $this->postJson("/api/admin/reports/{$warningReport->id}/warning", [
        'message' => 'Aviso sobre as regras da comunidade.',
        'note' => 'Aviso enviado após análise.',
    ])->assertCreated()
        ->assertJsonPath('data.type', 'admin_warning')
        ->assertJsonPath('data.message', 'Aviso sobre as regras da comunidade.');

    $this->assertDatabaseHas('user_notes', [
        'user_id' => $reportedUser->id,
        'admin_id' => $admin->id,
        'note' => 'Aviso enviado após análise.',
    ]);

    $resolvedReport = createReport($reporter, $reportedUser);
    $this->postJson("/api/admin/reports/{$resolvedReport->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');
});

test('admin can suspend for the selected duration and notify the user', function (int $days) {
    $this->travelTo(now()->startOfSecond());
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $suspendedAt = now();

    $this->actingAs($admin)
        ->postJson("/api/admin/users/{$user->id}/suspend", [
            'duration_days' => $days,
            'reason' => 'Violação das regras',
            'message' => 'A sua conta foi temporariamente suspensa.',
        ])
        ->assertOk()
        ->assertJsonPath('data.account_status', 'suspended');

    expect($user->fresh()->suspended_until->equalTo($suspendedAt->copy()->addDays($days)))->toBeTrue();
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'account_suspended',
        'message' => 'A sua conta foi temporariamente suspensa.',
    ]);
})->with([7, 15, 30]);

test('suspended users are blocked except for general support and cannot view a suspended profile', function () {
    $admin = User::factory()->admin()->create();
    $suspendedAdmin = User::factory()->admin()->create([
        'account_status' => 'suspended',
        'suspended_until' => now()->addDays(7),
    ]);
    $suspendedUser = User::factory()->create([
        'account_status' => 'suspended',
        'suspended_until' => now()->addDays(7),
        'suspension_reason' => 'Violação das regras',
    ]);
    $reporter = User::factory()->create();

    $suspendedUser->notifications()->create([
        'type' => 'account_suspended',
        'message' => 'A sua conta foi temporariamente suspensa.',
    ]);

    $this->actingAs($suspendedUser)->getJson('/api/bookings')
        ->assertForbidden()
        ->assertJsonPath('account_status', 'suspended')
        ->assertJsonPath('notice', 'A sua conta foi temporariamente suspensa.')
        ->assertJsonPath('suspended_until', $suspendedUser->suspended_until->toISOString());
    $this->getJson('/api/support-tickets')->assertForbidden();
    $this->get('/support')->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Support')
            ->where('suspension.status', 'suspended')
            ->where('suspension.until', $suspendedUser->suspended_until->toIso8601String())
            ->where('suspension.reason', 'Violação das regras')
            ->where('suspension.notice', 'A sua conta foi temporariamente suspensa.'));
    $this->postJson('/api/support-tickets', [
        'subject' => 'Preciso de ajuda',
        'description' => 'Quero esclarecer a suspensão.',
        'type' => 'help',
    ])->assertCreated();
    $this->postJson('/api/support-tickets', [
        'reported_user_id' => $reporter->id,
        'subject' => 'Outra denúncia',
        'description' => 'Não permitido durante suspensão.',
        'type' => 'complaint',
    ])->assertForbidden();

    $this->actingAs($reporter)->getJson("/api/users/{$suspendedUser->id}")->assertForbidden();
    $this->actingAs($admin)->getJson("/api/users/{$suspendedUser->id}")->assertOk();
    $this->actingAs($suspendedAdmin)->getJson('/api/admin/reports')->assertForbidden();
});

test('suspended users can log in and receive their suspension status', function () {
    $this->travelTo(now()->startOfSecond());
    $suspendedUntil = now()->addDays(15);
    $user = User::factory()->create([
        'account_status' => 'suspended',
        'suspended_until' => $suspendedUntil,
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('data.account_status', 'suspended')
        ->assertJsonPath('data.suspended_until', $suspendedUntil->toISOString());
});

test('expired suspensions reactivate and bans remain permanent', function () {
    $expiredUser = User::factory()->create([
        'account_status' => 'suspended',
        'suspended_until' => now()->subSecond(),
    ]);
    $bannedUser = User::factory()->create([
        'account_status' => 'banned',
        'suspended_until' => null,
    ]);

    $this->artisan('users:reactivate-suspended')->assertSuccessful();

    expect($expiredUser->fresh()->account_status)->toBe('active')
        ->and($expiredUser->fresh()->suspended_until)->toBeNull()
        ->and($bannedUser->fresh()->account_status)->toBe('banned');
});

test('admin can permanently ban a user without an expiry', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/admin/users/{$user->id}/ban", [
            'reason' => 'Abuso grave',
            'message' => 'A sua conta foi banida permanentemente.',
        ])
        ->assertOk()
        ->assertJsonPath('data.account_status', 'banned')
        ->assertJsonPath('data.suspended_until', null);

    $this->artisan('users:reactivate-suspended')->assertSuccessful();
    expect($user->fresh()->account_status)->toBe('banned');
});
