<?php

/**
 * Ficheiro PHP: PetsitterTest.php
 * PHP file: PetsitterTest.php
 */
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('public and regular listings hide approved profiles when the user is no longer a petsitter', function () {
    $user = User::factory()->create(['is_petsitter' => false]);
    $petsitter = Petsitter::factory()->approved()->create(['user_id' => $user->id]);
    $user->refresh()->forceFill(['is_petsitter' => false])->save();
    $viewer = User::factory()->create();

    $this->getJson('/api/petsitters/public')
        ->assertOk()
        ->assertJsonMissing(['user_id' => $petsitter->user_id]);

    $this->actingAs($viewer)
        ->getJson('/api/petsitters')
        ->assertOk()
        ->assertJsonMissing(['user_id' => $petsitter->user_id]);

    $this->getJson('/api/petsitters/'.$petsitter->user_id)->assertForbidden();
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

test('admin can filter petsitters by approval status', function () {
    $admin = User::factory()->admin()->create();

    Petsitter::factory()->approved()->create();
    $pendingPetsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $this->actingAs($admin)->getJson('/api/petsitters?approval_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['user_id' => $pendingPetsitter->user_id]);
});

test('regular user cannot use the approval status filter', function () {
    $user = User::factory()->create();

    Petsitter::factory()->approved()->create();
    Petsitter::factory()->create(['approval_status' => 'pending']);

    $this->actingAs($user)->getJson('/api/petsitters?approval_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('pending petsitters page is only accessible to admins', function () {
    $this->get('/admin/petsitters/pending')->assertRedirect();
    $this->actingAs(User::factory()->create())->get('/admin/petsitters/pending')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/petsitters/pending')->assertOk();
});

test('authenticated user can register as a petsitter', function () {
    $user = User::factory()->create();

    Storage::fake('local');

    $data = [
        'experience' => '5 years caring for dogs and cats',
        'service_location' => 'Ponta Delgada',
        'postal_code' => '9500-123',
        'price_per_day' => 25.50,
        'age' => 30,
        'accepted_sizes' => ['small', 'medium'],
        'accepted_animal_types' => ['dog', 'cat'],
        'service' => 'pet-sitting',
        'identification_documents' => UploadedFile::fake()->create('id_card.pdf', 100, 'application/pdf'),
    ];

    $response = $this->actingAs($user)->post('/api/petsitters', $data, ['Accept' => 'application/json']);

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

test('regular user cannot view a pending petsitter profile', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $this->actingAs($user)
        ->getJson('/api/petsitters/'.$petsitter->user_id)
        ->assertForbidden();
});

test('petsitter profile hides private fields from other users', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $this->actingAs($user)
        ->getJson('/api/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertJsonPath('data.biography', $petsitter->biography)
        ->assertJsonPath('data.user.name', $petsitter->user->name)
        ->assertJsonMissingPath('data.identification_documents')
        ->assertJsonMissingPath('data.user.email');
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

test('petsitter profile update rejects digits in the person name and postal code', function () {
    $owner = User::factory()->create(['name' => 'Ana Silva']);
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'name' => 'Ana2 Silva',
    ])->assertUnprocessable()->assertJsonValidationErrors('name');

    $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'postal_code' => '9500123',
    ])->assertUnprocessable()->assertJsonValidationErrors('postal_code');

    expect($owner->fresh()->name)->toBe('Ana Silva');
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

test('admin cannot approve a petsitter without identification documents', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create([
        'approval_status' => 'pending',
        'identification_documents' => '',
    ]);

    $response = $this->actingAs($admin)->postJson('/api/petsitters/'.$petsitter->user_id.'/approve');

    $response->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Não é possível aprovar um petsitter sem documentos de identificação.']);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $petsitter->user_id,
        'approval_status' => 'pending',
    ]);
});

test('admin can approve a petsitter with identification documents', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create([
        'approval_status' => 'pending',
        'identification_documents' => 'id_card.pdf',
    ]);

    $response = $this->actingAs($admin)->postJson('/api/petsitters/'.$petsitter->user_id.'/approve');

    $response->assertOk()
        ->assertJsonFragment(['approval_status' => 'approved']);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $petsitter->user_id,
        'approval_status' => 'approved',
    ]);
});

test('petsitter update returns 422 validation errors when required fields are missing', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'experience' => '',
        'service_location' => '',
        'age' => 10,
        'price_per_day' => -5,
    ]);

    $response->assertUnprocessable();
});

test('petsitter update cannot change approval_status', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create([
        'user_id' => $owner->id,
        'approval_status' => 'pending',
    ]);

    $response = $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'experience' => 'Updated experience',
        'approval_status' => 'approved',
    ]);

    $response->assertOk();

    // approval_status should remain unchanged
    $petsitter->refresh();
    expect($petsitter->approval_status)->toBe('pending');
});

test('petsitter update validates all editable fields', function () {
    $owner = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($owner)->putJson('/api/petsitters/'.$petsitter->user_id, [
        'biography' => 'New biography',
        'experience' => '10 years of experience',
        'service_location' => 'Lisboa',
        'price_per_day' => 30.0,
        'age' => 25,
        'sex' => 'female',
        'accepted_sizes' => ['small', 'large'],
        'accepted_animal_types' => ['dog', 'cat'],
    ]);

    $response->assertOk()
        ->assertJsonFragment(['biography' => 'New biography'])
        ->assertJsonFragment(['experience' => '10 years of experience'])
        ->assertJsonFragment(['service_location' => 'Lisboa'])
        ->assertJsonFragment(['price_per_day' => 30.0])
        ->assertJsonFragment(['age' => 25])
        ->assertJsonFragment(['sex' => 'female'])
        ->assertJsonFragment(['accepted_sizes' => ['small', 'large']])
        ->assertJsonFragment(['accepted_animal_types' => ['dog', 'cat']]);

    $this->assertDatabaseHas('petsitters', [
        'user_id' => $owner->id,
        'biography' => 'New biography',
        'service_location' => 'Lisboa',
    ]);
});
