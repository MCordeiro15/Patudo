<?php

/**
 * Ficheiro PHP: ChatTest.php
 * PHP file: ChatTest.php
 */
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\User;

beforeEach(function () {
    config([
        'services.talkjs.app_id' => 'app_teste',
        'services.talkjs.secret_key' => null,
    ]);
});

test('unauthenticated user cannot access chat endpoints', function () {
    $this->getJson('/api/chat/session')->assertUnauthorized();
    $this->getJson('/api/chat/contacts')->assertUnauthorized();
});

test('session returns the authenticated user and no token without a secret key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/chat/session')
        ->assertOk()
        ->assertJsonPath('app_id', 'app_teste')
        ->assertJsonPath('user.id', (string) $user->id)
        ->assertJsonPath('user.role', 'Dono')
        ->assertJsonPath('token', null);
});

test('session returns a token signed for the authenticated user when a secret key exists', function () {
    config(['services.talkjs.secret_key' => 'segredo_de_teste']);
    $user = User::factory()->create();

    $token = $this->actingAs($user)->getJson('/api/chat/session')->assertOk()->json('token');

    [$header, $payload, $signature] = explode('.', $token);
    $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, 'segredo_de_teste', true)), '+/', '-_'), '=');
    $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

    expect($signature)->toBe($expected)
        ->and($claims['tokenType'])->toBe('user')
        ->and($claims['iss'])->toBe('app_teste')
        ->and($claims['sub'])->toBe((string) $user->id);
});

test('an owner sees only approved petsitters as contacts', function () {
    $owner = User::factory()->create();
    $approved = Petsitter::factory()->approved()->create();
    Petsitter::factory()->create(); // não aprovado / not approved

    $response = $this->actingAs($owner)->getJson('/api/chat/contacts')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe((string) $approved->user_id)
        ->and($response->json('data.0.role'))->toBe('Petsitter');
});

test('contacts do not expose email or phone', function () {
    $owner = User::factory()->create();
    Petsitter::factory()->approved()->create();

    $contact = $this->actingAs($owner)->getJson('/api/chat/contacts')->assertOk()->json('data.0');

    expect(array_keys($contact))->toBe(['id', 'name', 'role', 'photo_url']);
});

test('a petsitter without bookings has no contact list and only replies', function () {
    $user = User::factory()->petsitter()->create();
    Petsitter::factory()->approved()->create(['user_id' => $user->id]);
    Petsitter::factory()->approved()->create();

    $this->actingAs($user)->getJson('/api/chat/contacts')
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

test('a petsitter can message the owners of their bookings, and only them', function () {
    $petsitter = Petsitter::factory()->approved()->create(['user_id' => User::factory()->petsitter()->create()->id]);
    $owner = User::factory()->create(['name' => 'Ana Dona']);
    $otherOwner = User::factory()->create();

    // reserva com este petsitter (o dono aparece nos contactos, uma só vez)
    foreach (['pending', 'approved'] as $status) {
        Booking::create([
            'user_id' => $owner->id,
            'petsitter_id' => $petsitter->user_id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => $status,
            'total_price' => 30,
        ]);
    }

    // reserva de outro dono com outro petsitter: não aparece
    Booking::create([
        'user_id' => $otherOwner->id,
        'petsitter_id' => Petsitter::factory()->approved()->create()->user_id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'status' => 'approved',
        'total_price' => 30,
    ]);

    $this->actingAs($petsitter->user)->getJson('/api/chat/contacts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $owner->id)
        ->assertJsonPath('data.0.name', 'Ana Dona')
        ->assertJsonPath('data.0.role', 'Dono');
});

test('a petsitter session reports the Petsitter role', function () {
    $user = User::factory()->petsitter()->create();

    $this->actingAs($user)->getJson('/api/chat/session')
        ->assertOk()
        ->assertJsonPath('user.role', 'Petsitter');
});
// PNG de 1x1 píxel em base64, como as fotos ficam guardadas na base de dados
const CHAT_TEST_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

test('contacts and session use the same avatar as petsitter search', function () {
    $owner = User::factory()->create(['avatar' => CHAT_TEST_PNG]);
    $withPhoto = Petsitter::factory()->approved()->create(['profile_photo' => CHAT_TEST_PNG]);
    $petsitterWithoutProfilePhoto = User::factory()->petsitter()->create(['avatar' => CHAT_TEST_PNG]);
    $withoutPhoto = Petsitter::factory()->approved()->create([
        'user_id' => $petsitterWithoutProfilePhoto->id,
        'profile_photo' => null,
    ]);

    $searchAvatars = collect($this->getJson('/api/petsitters/public')->assertOk()->json('data'))
        ->keyBy('user_id');

    $contacts = collect($this->actingAs($owner)->getJson('/api/chat/contacts')->assertOk()->json('data'))
        ->keyBy('id');

    expect($contacts[(string) $withPhoto->user_id]['photo_url'])->toContain('/users/'.$withPhoto->user_id.'/avatar')
        ->and($contacts[(string) $withoutPhoto->user_id]['photo_url'])
        ->toBe($searchAvatars[$withoutPhoto->user_id]['avatar_url']);

    $this->actingAs($owner)->getJson('/api/chat/session')
        ->assertJsonPath('user.photo_url', fn ($url) => str_contains($url, '/users/'.$owner->id.'/avatar'));

    $this->actingAs($petsitterWithoutProfilePhoto->fresh())->getJson('/api/chat/session')
        ->assertJsonPath('user.photo_url', $searchAvatars[$withoutPhoto->user_id]['avatar_url']);
});

test('the avatar route returns the stored photo as an image', function () {
    $owner = User::factory()->create(['avatar' => CHAT_TEST_PNG]);
    $petsitter = Petsitter::factory()->approved()->create(['profile_photo' => CHAT_TEST_PNG]);

    $this->actingAs(User::factory()->create())
        ->get('/users/'.$owner->id.'/avatar')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs(User::factory()->create())
        ->get('/users/'.$petsitter->user_id.'/avatar')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('the avatar route returns 404 without a photo or with a non-image type', function () {
    $noPhoto = User::factory()->create(['avatar' => null]);
    $svg = User::factory()->create(['avatar' => 'data:image/svg+xml;base64,'.base64_encode('<svg></svg>')]);

    $this->actingAs($noPhoto)->get('/users/'.$noPhoto->id.'/avatar')->assertNotFound();
    $this->actingAs($noPhoto)->get('/users/'.$svg->id.'/avatar')->assertNotFound();
});

test('the avatar route requires authentication', function () {
    $user = User::factory()->create(['avatar' => CHAT_TEST_PNG]);

    $this->get('/users/'.$user->id.'/avatar')->assertRedirect(route('login'));
});
