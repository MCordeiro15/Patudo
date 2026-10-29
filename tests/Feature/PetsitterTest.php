<?php

use App\Models\Petsitter;
use App\Models\User;

test('unauthenticated user cannot access petsitters endpoint', function () {
    $this->getJson('/api/petsitters')->assertUnauthorized();
    $this->postJson('/api/petsitters', [])->assertUnauthorized();
});

test('regular user only sees approved petsitters in listing', function () {
    $user = User::factory()->create();

    $approvedPetsitter = Petsitter::factory()->approved()->create();
    $pendingPetsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($user)->getJson('/api/petsitters');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['user_id' => $approvedPetsitter->user_id])
        ->assertJsonMissing(['user_id' => $pendingPetsitter->user_id]);
});

test('admin user sees all petsitters regardless of approval status', function () {
    $admin = User::factory()->admin()->create();

    $approvedPetsitter = Petsitter::factory()->approved()->create();
    $pendingPetsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($admin)->getJson('/api/petsitters');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['user_id' => $approvedPetsitter->user_id])
        ->assertJsonFragment(['user_id' => $pendingPetsitter->user_id]);
});

test('authenticated user can register as a petsitter', function () {
    $user = User::factory()->create();

    $data = [
        'experience' => '5 years caring for dogs and cats',
        'service_location' => 'Ponta Delgada',
        'price_per_day' => 25.50,
        'accepted_sizes' => ['small', 'medium'],
        'accepted_animal_types' => ['dog', 'cat'],
        'identification_documents' => 'id_card.pdf',
    ];

    $response = $this->actingAs($user)->postJson('/api/petsitters', $data);

    $response->assertCreated()
        ->assertJsonFragment([
            'user_id' => $user->id,
            'approval_status' => 'pending',
            'experience' => '5 years caring for dogs and cats',
        ]);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $user->id,
        'approval_status' => 'pending',
    ]);
});

test('authenticated user can view a petsitter profile', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $response = $this->actingAs($user)->getJson('/api/petsitters/'.$petsitter->user_id);

    $response->assertOk()
        ->assertJsonFragment(['user_id' => $petsitter->user_id]);
});

test('petsitter owner can update their profile', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'experience' => 'Updated experience',
        'service_location' => 'Ribeira Grande',
    ]);

    $response->assertOk()
        ->assertJsonFragment([
            'experience' => 'Updated experience',
            'service_location' => 'Ribeira Grande',
        ]);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $owner->id,
        'service_location' => 'Ribeira Grande',
    ]);
});

test('user cannot update another user petsitter profile', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'experience' => 'Hacked experience',
    ]);

    $response->assertForbidden();
});

test('admin can update any petsitter profile', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($admin)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'experience' => 'Admin updated experience',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['experience' => 'Admin updated experience']);
});

test('petsitter owner can delete their profile', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->deleteJson('/api/petsitters/'.$petsitter->user_id);

    $response->assertNoContent();
    $this->assertDatabaseMissing('petsitters', ['user_id' => $owner->id]);
});

test('user cannot delete another user petsitter profile', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->deleteJson('/api/petsitters/'.$petsitter->user_id);

    $response->assertForbidden();
    $this->assertDatabaseHas('petsitters', ['user_id' => $owner->id]);
});

test('admin can approve a petsitter', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($admin)->postJson('/api/petsitters/'.$petsitter->user_id.'/approve');

    $response->assertOk()
        ->assertJsonFragment(['approval_status' => 'approved']);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $petsitter->user_id,
        'approval_status' => 'approved',
    ]);
});

test('regular user cannot approve a petsitter', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($user)->postJson('/api/petsitters/'.$petsitter->user_id.'/approve');

    $response->assertForbidden();
});

test('admin can reject a petsitter', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($admin)->postJson('/api/petsitters/'.$petsitter->user_id.'/reject');

    $response->assertOk()
        ->assertJsonFragment(['approval_status' => 'rejected']);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $petsitter->user_id,
        'approval_status' => 'rejected',
    ]);
});

test('regular user cannot reject a petsitter', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $response = $this->actingAs($user)->postJson('/api/petsitters/'.$petsitter->user_id.'/reject');

    $response->assertForbidden();
});
