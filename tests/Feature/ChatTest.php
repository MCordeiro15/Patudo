<?php

/**
 * Ficheiro PHP: ChatTest.php
 * PHP file: ChatTest.php
 */
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

    expect(array_keys($contact))->toBe(['id', 'name', 'role']);
});

test('a petsitter has no contact list and only replies', function () {
    $user = User::factory()->petsitter()->create();
    Petsitter::factory()->approved()->create(['user_id' => $user->id]);
    Petsitter::factory()->approved()->create();

    $this->actingAs($user)->getJson('/api/chat/contacts')
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

test('a petsitter session reports the Petsitter role', function () {
    $user = User::factory()->petsitter()->create();

    $this->actingAs($user)->getJson('/api/chat/session')
        ->assertOk()
        ->assertJsonPath('user.role', 'Petsitter');
});