<?php

/**
 * Testes da moderação de avaliações (KAN-239): estados published / flagged / removed, sinalizar
 * (POST /api/reviews/{id}/flag), lista do admin com filtros e pesquisa, publicar e remover.
 * Tests for review moderation: statuses, flagging, the admin list with filters and search, publish and remove.
 */
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\Review;
use App\Models\User;

/** Review de um dono a um petsitter, numa reserva concluída. */
function makeReview(Petsitter $petsitter, int $stars, string $status = Review::PUBLISHED, ?User $author = null, ?string $comment = null): Review
{
    $author ??= User::factory()->create();
    $booking = Booking::create([
        'user_id' => $author->id,
        'petsitter_id' => $petsitter->user_id,
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->subDays(3)->toDateString(),
        'status' => 'completed',
        'total_price' => 30,
    ]);

    $review = Review::forceCreate([
        'booking_id' => $booking->id,
        'petsitter_id' => $petsitter->user_id,
        'star_rating' => $stars,
        'comment' => $comment ?? 'Correu muito bem.',
        'status' => $status,
    ]);
    $petsitter->refreshAverageRating();

    return $review;
}

test('a new review starts as published', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $owner = User::factory()->create();
    $booking = Booking::create([
        'user_id' => $owner->id, 'petsitter_id' => $petsitter->user_id, 'status' => 'completed', 'total_price' => 30,
        'start_date' => now()->subDays(3)->toDateString(), 'end_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($owner)->postJson("/api/bookings/{$booking->id}/review", ['star_rating' => 5])
        ->assertCreated()
        ->assertJsonPath('data.status', 'published');
});

test('the status cannot be changed by the author when editing the review', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $author = User::factory()->create();
    $review = makeReview($petsitter, 4, Review::REMOVED, $author);

    $this->actingAs($author)->patchJson("/api/reviews/{$review->id}", ['star_rating' => 5, 'status' => 'published'])
        ->assertOk();

    expect($review->fresh()->status)->toBe(Review::REMOVED);
});

test('a user can flag a review and it stays on the profile until the admin decides', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $review = makeReview($petsitter, 2);
    $other = User::factory()->create();

    $this->actingAs($other)->postJson("/api/reviews/{$review->id}/flag")
        ->assertOk()
        ->assertJsonPath('data.status', 'flagged');

    // sinalizar outra vez não dá erro
    $this->actingAs(User::factory()->create())->postJson("/api/reviews/{$review->id}/flag")->assertOk();

    $this->actingAs($other)->getJson("/api/petsitters/{$petsitter->user_id}/reviews")
        ->assertOk()
        ->assertJsonCount(1, 'data');
    expect((float) $petsitter->fresh()->average_rating)->toBe(2.0);
});

test('the author cannot flag their own review and a removed review cannot be flagged', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $author = User::factory()->create();
    $review = makeReview($petsitter, 2, Review::PUBLISHED, $author);
    $removed = makeReview($petsitter, 1, Review::REMOVED);

    // sem sessão primeiro (depois de um actingAs o teste fica autenticado)
    $this->postJson("/api/reviews/{$review->id}/flag")->assertUnauthorized();
    $this->actingAs($author)->postJson("/api/reviews/{$review->id}/flag")->assertForbidden();
    $this->actingAs(User::factory()->create())->postJson("/api/reviews/{$removed->id}/flag")->assertForbidden();

    expect($review->fresh()->status)->toBe(Review::PUBLISHED)
        ->and($removed->fresh()->status)->toBe(Review::REMOVED);
});

test('admin removes a review: it leaves the profile, the count and the average', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    makeReview($petsitter, 5);
    $bad = makeReview($petsitter, 1, Review::FLAGGED);
    expect((float) $petsitter->fresh()->average_rating)->toBe(3.0);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/api/admin/reviews/{$bad->id}/remove")
        ->assertOk()
        ->assertJsonPath('data.status', 'removed');

    expect((float) $petsitter->fresh()->average_rating)->toBe(5.0);

    $viewer = User::factory()->create();
    $this->actingAs($viewer)->getJson("/api/petsitters/{$petsitter->user_id}/reviews")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.star_rating', 5);
    $this->actingAs($viewer)->getJson("/api/petsitters/{$petsitter->user_id}")
        ->assertJsonPath('data.reviews_count', 1);
    // removida: outro utilizador já não a vê
    $this->actingAs($viewer)->getJson("/api/reviews/{$bad->id}")->assertForbidden();
});

test('admin publishes a flagged or removed review and it counts again', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    makeReview($petsitter, 5);
    $removed = makeReview($petsitter, 3, Review::REMOVED);
    $flagged = makeReview($petsitter, 4, Review::FLAGGED);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->postJson("/api/admin/reviews/{$removed->id}/publish")
        ->assertOk()->assertJsonPath('data.status', 'published');
    $this->actingAs($admin)->postJson("/api/admin/reviews/{$flagged->id}/publish")
        ->assertOk()->assertJsonPath('data.status', 'published');

    expect((float) $petsitter->fresh()->average_rating)->toBe(4.0);
});

test('the old hide route marks the review as removed', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $review = makeReview($petsitter, 1);

    $this->actingAs(User::factory()->admin()->create())->patchJson("/api/reviews/{$review->id}/hide")
        ->assertOk()->assertJsonPath('data.status', 'removed')->assertJsonPath('data.visible', false);
});

test('admin list is paginated with the filters counters and the review details', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $author = User::factory()->create(['name' => 'Carla Dias']);
    makeReview($petsitter, 5, Review::PUBLISHED, $author, 'Excelente!');
    makeReview($petsitter, 1, Review::FLAGGED);
    makeReview($petsitter, 2, Review::FLAGGED);
    makeReview($petsitter, 1, Review::REMOVED);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->getJson('/api/admin/reviews')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('meta.counts', ['all' => 4, 'published' => 1, 'flagged' => 2, 'removed' => 1])
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonStructure(['data' => [['id', 'star_rating', 'comment', 'status', 'author' => ['id', 'name'], 'petsitter' => ['id', 'name'], 'created_at']]]);

    $this->actingAs($admin)->getJson('/api/admin/reviews?status=flagged')->assertJsonCount(2, 'data');
    $this->actingAs($admin)->getJson('/api/admin/reviews?status=removed')->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson('/api/admin/reviews?status=published')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.author.name', 'Carla Dias');
    $this->actingAs($admin)->getJson('/api/admin/reviews?status=banana')->assertUnprocessable();
});

test('admin list searches the comment, the author and the petsitter', function () {
    $sofia = Petsitter::factory()->approved()->create();
    $sofia->user->update(['name' => 'Sofia Mendes']);
    $rui = Petsitter::factory()->approved()->create();
    $rui->user->update(['name' => 'Rui Costa']);
    makeReview($sofia, 5, Review::PUBLISHED, User::factory()->create(['name' => 'Carla Dias']), 'Muito carinhosa');
    makeReview($rui, 2, Review::FLAGGED, User::factory()->create(['name' => 'Pedro Lopes']), 'Chegou atrasado');
    $admin = User::factory()->admin()->create();

    $search = fn (string $q) => $this->actingAs($admin)->getJson('/api/admin/reviews?search='.urlencode($q));

    $search('atrasado')->assertJsonCount(1, 'data')->assertJsonPath('data.0.petsitter.name', 'Rui Costa');
    $search('Carla')->assertJsonCount(1, 'data')->assertJsonPath('data.0.comment', 'Muito carinhosa');
    $search('Sofia')->assertJsonCount(1, 'data')->assertJsonPath('data.0.author.name', 'Carla Dias');
    $search('ninguém')->assertJsonCount(0, 'data');
});

test('only admins can use the admin review routes', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    $review = makeReview($petsitter, 3);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/admin/reviews')->assertForbidden();
    $this->actingAs($user)->postJson("/api/admin/reviews/{$review->id}/remove")->assertForbidden();
    $this->actingAs($user)->postJson("/api/admin/reviews/{$review->id}/publish")->assertForbidden();

    expect($review->fresh()->status)->toBe(Review::PUBLISHED);
});

test('the migration turns hidden reviews into removed ones', function () {
    // a migração já correu (RefreshDatabase); confirma o resultado: sem a coluna visible e com status
    expect(\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'visible'))->toBeFalse()
        ->and(\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'status'))->toBeTrue();
});
