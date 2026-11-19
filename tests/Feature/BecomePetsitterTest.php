<?php

/**
 * Testes do formulário "Quero ser petsitter" (POST /api/petsitters): documento guardado no disco privado,
 * código postal, serviço criado automaticamente, nome do utilizador atualizado e validações.
 * Tests for the "Become a petsitter" form (POST /api/petsitters): document stored on the private disk,
 * postal code, service created automatically, user name updated and validations.
 */
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/** Dados válidos do formulário, como o ModalFormPetsitter os envia. */
function becomePetsitterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Joana Silva',
        'sex' => 'female',
        'service' => 'pet-sitting',
        'age' => 28,
        'service_location' => 'Ponta Delgada',
        'postal_code' => '9500-123',
        'accepted_sizes' => ['small', 'medium'],
        'accepted_animal_types' => ['dog'],
        'price_per_day' => 15,
        'experience' => 'Cuido de cães há 5 anos.',
        'identification_documents' => UploadedFile::fake()->create('cc.pdf', 200, 'application/pdf'),
    ], $overrides);
}

/** Envia o formulário como multipart (como o browser faz) e pede resposta JSON. */
function submitBecomePetsitter($test, User $user, array $payload)
{
    return $test->actingAs($user)->post('/api/petsitters', $payload, ['Accept' => 'application/json']);
}

test('user becomes a pending petsitter with postal code, service and stored document', function () {
    $user = User::factory()->create(['name' => 'Nome Antigo']);

    $response = submitBecomePetsitter($this, $user, becomePetsitterPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.approval_status', 'pending')
        ->assertJsonPath('data.postal_code', '9500-123')
        ->assertJsonPath('data.services.0.type', 'pet-sitting')
        ->assertJsonPath('data.services.0.price', 15);

    $petsitter = Petsitter::find($user->id);

    // o documento fica no disco privado, numa pasta do utilizador, e a coluna guarda o caminho
    expect($petsitter->identification_documents)->toStartWith('identification-documents/'.$user->id.'/');
    Storage::disk('local')->assertExists($petsitter->identification_documents);

    // o serviço fica ligado ao petsitter com o preço de price_per_day
    $this->assertDatabaseHas('petsitter_services', [
        'petsitter_id' => $user->id,
        'type' => 'pet-sitting',
        'price' => 15,
    ]);

    // o nome do formulário atualiza o utilizador
    expect($user->fresh()->name)->toBe('Joana Silva');
});

test('sex is optional', function () {
    $user = User::factory()->create();

    $payload = becomePetsitterPayload();
    unset($payload['sex']);

    submitBecomePetsitter($this, $user, $payload)->assertCreated();
});

test('postal code must have the 0000-000 format', function (string $postalCode) {
    $user = User::factory()->create();

    submitBecomePetsitter($this, $user, becomePetsitterPayload(['postal_code' => $postalCode]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('postal_code');
})->with(['9500123', '950-1234', 'abcd-efg', '']);

test('service must be one of the accepted types', function () {
    $user = User::factory()->create();

    submitBecomePetsitter($this, $user, becomePetsitterPayload(['service' => 'banho']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('service');
});

test('identification document must be a jpg, png or pdf file up to 10 MB', function () {
    $user = User::factory()->create();

    // texto em vez de ficheiro
    submitBecomePetsitter($this, $user, becomePetsitterPayload(['identification_documents' => 'cc.pdf']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identification_documents');

    // tipo errado
    submitBecomePetsitter($this, $user, becomePetsitterPayload([
        'identification_documents' => UploadedFile::fake()->create('cc.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ]))->assertUnprocessable()->assertJsonValidationErrors('identification_documents');

    // maior que 10 MB
    submitBecomePetsitter($this, $user, becomePetsitterPayload([
        'identification_documents' => UploadedFile::fake()->create('cc.pdf', 10241, 'application/pdf'),
    ]))->assertUnprocessable()->assertJsonValidationErrors('identification_documents');

    expect(Petsitter::find($user->id))->toBeNull();
});

test('sizes only accept small, medium and large', function () {
    $user = User::factory()->create();

    submitBecomePetsitter($this, $user, becomePetsitterPayload(['accepted_sizes' => ['pequeno']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('accepted_sizes.0');
});

test('a user who is already a petsitter gets 409 and nothing is stored', function () {
    $petsitter = Petsitter::factory()->create();

    submitBecomePetsitter($this, $petsitter->user, becomePetsitterPayload())
        ->assertStatus(409);

    // não fica nenhum documento perdido no disco
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('the postal code is not shown to other users', function () {
    $petsitter = Petsitter::factory()->approved()->create(['postal_code' => '9500-123']);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->getJson('/api/petsitters/'.$petsitter->user_id)
        ->assertOk()
        ->assertJsonMissingPath('data.postal_code');

    $this->actingAs($petsitter->user)
        ->getJson('/api/petsitters/'.$petsitter->user_id)
        ->assertJsonPath('data.postal_code', '9500-123');
});
