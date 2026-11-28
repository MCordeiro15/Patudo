<?php

/**
 * Ficheiro PHP: PetsitterProfilePageTest.php
 * PHP file: PetsitterProfilePageTest.php
 */
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\Review;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('petsitter can view their own profile with its data', function () {
    $petsitter = Petsitter::factory()->approved()->create([
        'biography' => 'Cuido de cães há vários anos.',
    ]);

    $this->actingAs($petsitter->user)
        ->get('/petsitters/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('isOwnProfile', true)
            ->where('petsitter.user_id', $petsitter->user_id)
            ->where('petsitter.name', $petsitter->user->name)
            ->where('petsitter.biography', 'Cuido de cães há vários anos.')
        );
});

test('petsitter own profile includes the average and count of visible reviews', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    foreach ([5 => Review::PUBLISHED, 4 => Review::FLAGGED, 1 => Review::REMOVED] as $rating => $status) {
        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'petsitter_id' => $petsitter->user_id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDays(3)->toDateString(),
            'status' => 'completed',
            'total_price' => 30,
        ]);

        Review::forceCreate([
            'booking_id' => $booking->id,
            'petsitter_id' => $petsitter->user_id,
            'star_rating' => $rating,
            'comment' => 'Boa experiência.',
            'status' => $status,
        ]);
    }

    $petsitter->refreshAverageRating();

    $this->actingAs($petsitter->user)
        ->get('/petsitters/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('petsitter.average_rating', 4.5)
            ->where('petsitter.reviews_count', 2)
        );
});

test('user without a petsitter profile is redirected to owner profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/petsitters/profile')
        ->assertRedirect(route('owner.profile'));
});

test('user whose petsitter role is disabled sees the owner profile even if an old profile remains approved', function () {
    $user = User::factory()->create(['is_petsitter' => false]);
    Petsitter::factory()->approved()->create(['user_id' => $user->id]);
    $user->forceFill(['is_petsitter' => false])->save();

    $this->actingAs($user)
        ->get('/petsitters/profile')
        ->assertRedirect(route('owner.profile'));

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Profile')
            ->where('petsitterApplicationStatus', 'inactive'));
});

test('user with a flag but no approved profile has no active petsitter role in the page props', function () {
    $user = User::factory()->petsitter()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Profile')
            ->where('auth.user.is_petsitter', false)
            ->where('petsitterApplicationStatus', null));
});

test('user can view the selected approved petsitter profile without private data', function () {
    $viewer = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();

    $this->actingAs($viewer)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/ProfileOwnerView')
            ->where('petsitterId', $petsitter->user_id)
        );
});

test('users cannot view a suspended petsitter profile', function () {
    $viewer = User::factory()->create();
    $petsitter = Petsitter::factory()->approved()->create();
    $petsitter->user->update([
        'account_status' => 'suspended',
        'suspended_until' => now()->addDays(7),
    ]);

    $this->actingAs($viewer)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertForbidden();
});

test('user cannot view another petsitter profile that is not approved', function () {
    $viewer = User::factory()->create();
    $petsitter = Petsitter::factory()->create(['approval_status' => 'pending']);

    $this->actingAs($viewer)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertNotFound();
});

test('petsitter profile pages require authentication', function () {
    $petsitter = Petsitter::factory()->approved()->create();

    $this->get('/petsitters/profile')->assertRedirect(route('login'));
    $this->get('/petsitters/'.$petsitter->user_id)->assertRedirect(route('login'));
});

test('admin can edit any petsitter profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $petsitter = Petsitter::factory()->approved()->create([
        'identification_documents' => 'some_document.pdf',
    ]);

    $this->actingAs($admin)
        ->get('/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Petsitter/Profile')
            ->where('isOwnProfile', false)
            ->where('isAdmin', true)
            ->where('petsitter.user_id', $petsitter->user_id)
            ->where('petsitter.identification_documents', 'some_document.pdf')
        );
});

test('admin without petsitter profile is redirected to owner profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/petsitters/profile')
        ->assertRedirect(route('owner.profile'));
});

test('nonexistent petsitter profile returns 404', function () {
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/petsitters/999999')
        ->assertNotFound();
});
