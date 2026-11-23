<?php


/**
 * Ficheiro PHP: AnimalTest.php
 * PHP file: AnimalTest.php
 */
use App\Models\Animal;
use App\Models\User;

test('unauthenticated user cannot access animals', function () {
    $this->getJson('/api/animals')->assertUnauthorized();
    $this->postJson('/api/animals', [])->assertUnauthorized();
});

test('user can list their own animals', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Animal::create([
        'user_id' => $user->id,
        'name' => 'Rex',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'size' => 'Medium',
    ]);

    Animal::create([
        'user_id' => $otherUser->id,
        'name' => 'Garfield',
        'species' => 'Cat',
        'breed' => 'Persian',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($user)->getJson('/api/animals');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Rex'])
        ->assertJsonMissing(['name' => 'Garfield']);
});

test('admin can list all animals from all users', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Animal::create([
        'user_id' => $user1->id,
        'name' => 'Rex',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'size' => 'Medium',
    ]);

    Animal::create([
        'user_id' => $user2->id,
        'name' => 'Garfield',
        'species' => 'Cat',
        'breed' => 'Persian',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($admin)->getJson('/api/animals');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['name' => 'Rex'])
        ->assertJsonFragment(['name' => 'Garfield']);
});

test('user can create an animal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/animals', [
        'name' => 'Bobby',
        'species' => 'Dog',
        'breed' => 'Bulldog',
        'size' => 'Small',
        'special_needs' => 'Allergic to chicken',
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'name' => 'Bobby',
            'species' => 'Dog',
            'size' => 'Small',
            'special_needs' => 'Allergic to chicken',
        ]);

    $this->assertDatabaseHas('animals', [
        'user_id' => $user->id,
        'name' => 'Bobby',
        'species' => 'Dog',
        'size' => 'Small',
    ]);
});

test('fails to create animal when validation fails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/animals', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'species', 'breed', 'size']);
});

test('user can show their own animal', function () {
    $user = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $user->id,
        'name' => 'Mimi',
        'species' => 'Cat',
        'breed' => 'Siamese',
        'size' => 'Small',
    ]);

    $response = $this->actingAs($user)->getJson("/api/animals/{$animal->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Mimi']);
});

test('user cannot show another users animal', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $otherUser->id,
        'name' => 'Mimi',
        'species' => 'Cat',
        'breed' => 'Siamese',
        'size' => 'Small',
    ]);

    $response = $this->actingAs($user)->getJson("/api/animals/{$animal->id}");

    $response->assertForbidden();
});

test('user can update their own animal', function () {
    $user = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $user->id,
        'name' => 'Max',
        'species' => 'Dog',
        'breed' => 'German Shepherd',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($user)->putJson("/api/animals/{$animal->id}", [
        'name' => 'Maximus',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Maximus']);

    $this->assertDatabaseHas('animals', [
        'id' => $animal->id,
        'name' => 'Maximus',
    ]);
});

test('user cannot update another users animal', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $otherUser->id,
        'name' => 'Max',
        'species' => 'Dog',
        'breed' => 'German Shepherd',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($user)->putJson("/api/animals/{$animal->id}", [
        'name' => 'HackedName',
    ]);

    $response->assertForbidden();
});

test('user can delete their own animal', function () {
    $user = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $user->id,
        'name' => 'Thor',
        'species' => 'Dog',
        'breed' => 'Husky',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/animals/{$animal->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('animals', [
        'id' => $animal->id,
    ]);
});

test('user cannot delete another users animal', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $otherUser->id,
        'name' => 'Thor',
        'species' => 'Dog',
        'breed' => 'Husky',
        'size' => 'Large',
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/animals/{$animal->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('animals', [
        'id' => $animal->id,
    ]);
});

test('admin can update and delete another users animal', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $animal = Animal::create([
        'user_id' => $user->id,
        'name' => 'Thor',
        'species' => 'Dog',
        'breed' => 'Husky',
        'size' => 'Large',
    ]);

    $this->actingAs($admin)->putJson("/api/animals/{$animal->id}", [
        'name' => 'AdminUpdated',
    ])->assertOk();

    $this->actingAs($admin)->deleteJson("/api/animals/{$animal->id}")->assertNoContent();

    $this->assertDatabaseMissing('animals', [
        'id' => $animal->id,
    ]);
});

test('animal can be created with a photo and an age', function () {
    $user = User::factory()->create();
    // PNG de 1x1 píxel (sem precisar da extensão GD para gerar imagens falsas)
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    $photo = \Illuminate\Http\UploadedFile::fake()->createWithContent('rex.png', $png);

    $response = $this->actingAs($user)->post('/api/animals', [
        'name' => 'Rex',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'size' => 'medium',
        'age' => 3,
        'photo' => $photo,
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.age', 3);

    // a foto fica guardada como data URI, pronta a usar num <img>
    expect($response->json('data.photo'))->toStartWith('data:image/png;base64,');
});

test('animal photo must be an image and the age must be valid', function () {
    $user = User::factory()->create();
    $pdf = \Illuminate\Http\UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf');

    $this->actingAs($user)->post('/api/animals', [
        'name' => 'Rex',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'size' => 'medium',
        'age' => 99,
        'photo' => $pdf,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photo', 'age']);
});
