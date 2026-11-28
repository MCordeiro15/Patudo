<?php

/**
 * Testes das verificações de petsitters pela API: candidatar com vários documentos, voltar a candidatar-se
 * depois de uma recusa (as antigas ficam guardadas), limite de 3 candidaturas, recusar com motivos,
 * pedir mais informação, notificações ao candidato, abrir cada documento e os dados para a página.
 * Tests for petsitter verifications through the API: applying with several files, re-applying after a
 * rejection, the 3-application limit, rejecting with reasons, requesting info, notifications and documents.
 */
use App\Models\Petsitter;
use App\Models\PetsitterApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

/** Dados válidos da candidatura, com um ou mais documentos. */
function verificationPayload(array $overrides = []): array
{
    return array_merge([
        'service' => 'Passeios',
        'age' => 30,
        'service_location' => 'Lisboa',
        'postal_code' => '1000-001',
        'price_per_day' => 15,
        'experience' => 'Cuido de cães há 3 anos.',
        'identification_documents' => [
            UploadedFile::fake()->create('cc-frente.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('cc-verso.jpg', 100, 'image/jpeg'),
        ],
    ], $overrides);
}

function applyAsPetsitter($test, User $user, array $overrides = [])
{
    return $test->actingAs($user)->post('/api/petsitters', verificationPayload($overrides), ['Accept' => 'application/json']);
}

function rejectApplication($test, User $admin, int $userId, array $body = ['reasons' => ['illegible_document']])
{
    return $test->actingAs($admin)->postJson("/api/petsitters/{$userId}/reject", $body);
}

test('applying stores several documents in a new pending application', function () {
    $user = User::factory()->create();

    applyAsPetsitter($this, $user)
        ->assertCreated()
        ->assertJsonPath('data.application.status', 'pending')
        ->assertJsonCount(2, 'data.application.documents')
        ->assertJsonPath('data.applications_remaining', 2);

    $application = PetsitterApplication::where('user_id', $user->id)->sole();
    expect($application->identification_documents)->toHaveCount(2);
    foreach ($application->identification_documents as $path) {
        Storage::disk('local')->assertExists($path);
    }

    // o perfil guarda o primeiro documento (compatibilidade com aprovar)
    expect(Petsitter::find($user->id)->identification_documents)->toBe($application->identification_documents[0]);
});

test('applying with a single file still works', function () {
    $user = User::factory()->create();

    applyAsPetsitter($this, $user, ['identification_documents' => UploadedFile::fake()->create('cc.pdf', 100, 'application/pdf')])
        ->assertCreated()
        ->assertJsonCount(1, 'data.application.documents');
});

test('every document must be a jpg, png or pdf up to 10 MB', function () {
    $user = User::factory()->create();

    applyAsPetsitter($this, $user, ['identification_documents' => [
        UploadedFile::fake()->create('cc.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('cc.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ]])->assertUnprocessable()->assertJsonValidationErrors('identification_documents');

    expect(Storage::disk('local')->allFiles())->toBeEmpty()
        ->and(PetsitterApplication::count())->toBe(0);
});

test('a rejected applicant can apply again and the old application is kept', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    applyAsPetsitter($this, $user)->assertCreated();
    rejectApplication($this, $admin, $user->id)->assertOk();

    applyAsPetsitter($this, $user)
        ->assertCreated()
        ->assertJsonPath('data.approval_status', 'pending')
        ->assertJsonPath('data.application.status', 'pending')
        ->assertJsonPath('data.applications.1.status', 'rejected')
        ->assertJsonPath('data.applications.1.reasons', ['illegible_document'])
        ->assertJsonPath('data.applications_remaining', 1);

    expect(PetsitterApplication::where('user_id', $user->id)->count())->toBe(2)
        ->and(Petsitter::where('user_id', $user->id)->count())->toBe(1);
});

test('the fourth application returns 422', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    foreach (range(1, 3) as $attempt) {
        applyAsPetsitter($this, $user)->assertCreated();
        rejectApplication($this, $admin, $user->id)->assertOk();
    }

    applyAsPetsitter($this, $user)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Atingiste o limite de 3 candidaturas a petsitter.');

    expect(PetsitterApplication::where('user_id', $user->id)->count())->toBe(3);
});

test('a pending or approved petsitter cannot apply again', function (string $status) {
    $petsitter = Petsitter::factory()->create(['approval_status' => $status]);

    applyAsPetsitter($this, $petsitter->user)->assertStatus(409);

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['pending', 'approved']);

test('rejecting without reasons returns 422', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create();

    rejectApplication($this, $admin, $petsitter->user_id, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reasons');

    rejectApplication($this, $admin, $petsitter->user_id, ['reasons' => ['nao_existe']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reasons.0');

    expect($petsitter->fresh()->approval_status)->toBe('pending');
});

test('the reason other requires a message', function () {
    $admin = User::factory()->admin()->create();
    $petsitter = Petsitter::factory()->create();

    rejectApplication($this, $admin, $petsitter->user_id, ['reasons' => ['other'], 'message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');

    rejectApplication($this, $admin, $petsitter->user_id, ['reasons' => ['other'], 'message' => 'Falta a foto do perfil.'])
        ->assertOk();
});

test('rejecting saves the reasons on the application and notifies the applicant', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();

    rejectApplication($this, $admin, $user->id, [
        'reasons' => ['illegible_document', 'inappropriate_photos'],
        'message' => 'Envia uma foto mais nítida.',
    ])
        ->assertOk()
        ->assertJsonPath('data.approval_status', 'rejected')
        ->assertJsonPath('data.application.status', 'rejected')
        ->assertJsonPath('data.application.reason_labels', ['Documento ilegível', 'Fotografias inadequadas'])
        ->assertJsonPath('data.application.decided_by.id', $admin->id);

    $notification = $user->notifications()->sole();
    expect($notification->type)->toBe('verification_rejected')
        ->and($notification->message)->toContain('Documento ilegível')
        ->and($notification->message)->toContain('Envia uma foto mais nítida.');
});

test('approving saves the decision and notifies the applicant', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();

    $this->actingAs($admin)->postJson("/api/petsitters/{$user->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.application.status', 'approved')
        ->assertJsonPath('data.application.decided_by.id', $admin->id);

    expect($user->fresh()->is_petsitter)->toBeTrue()
        ->and($user->notifications()->sole()->type)->toBe('verification_approved');
});

test('requesting info notifies the applicant and keeps the application pending', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();

    $this->actingAs($admin)->postJson("/api/petsitters/{$user->id}/request-info", ['message' => 'Envia o verso do CC.'])
        ->assertOk()
        ->assertJsonPath('data.approval_status', 'pending')
        ->assertJsonPath('data.application.status', 'pending')
        ->assertJsonPath('data.application.message', 'Envia o verso do CC.');

    $notification = $user->notifications()->sole();
    expect($notification->type)->toBe('verification_info_requested')
        ->and($notification->message)->toContain('Envia o verso do CC.');

    // a notificação aparece na lista do candidato
    $this->actingAs($user)->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonFragment(['type' => 'verification_info_requested']);
});

test('requesting info needs a message and a pending application', function () {
    $admin = User::factory()->admin()->create();
    $pending = Petsitter::factory()->create();
    $approved = Petsitter::factory()->approved()->create();

    $this->actingAs($admin)->postJson("/api/petsitters/{$pending->user_id}/request-info", ['message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');

    $this->actingAs($admin)->postJson("/api/petsitters/{$approved->user_id}/request-info", ['message' => 'Olá'])
        ->assertUnprocessable();

    expect($pending->user->notifications()->count())->toBe(0);
});

test('only an admin can reject, approve or request info', function () {
    $user = User::factory()->create();
    $petsitter = Petsitter::factory()->create();

    rejectApplication($this, $user, $petsitter->user_id)->assertForbidden();
    $this->actingAs($user)->postJson("/api/petsitters/{$petsitter->user_id}/approve")->assertForbidden();
    $this->actingAs($user)->postJson("/api/petsitters/{$petsitter->user_id}/request-info", ['message' => 'x'])->assertForbidden();
});

test('without a session the verification routes return 401', function () {
    $petsitter = Petsitter::factory()->create();

    $this->postJson("/api/petsitters/{$petsitter->user_id}/reject", ['reasons' => ['other'], 'message' => 'x'])->assertUnauthorized();
    $this->postJson("/api/petsitters/{$petsitter->user_id}/approve")->assertUnauthorized();
    $this->postJson("/api/petsitters/{$petsitter->user_id}/request-info", ['message' => 'x'])->assertUnauthorized();
});

test('the admin opens each document of an application and others cannot', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();
    $application = PetsitterApplication::where('user_id', $user->id)->sole();

    $this->actingAs($admin)->get("/api/petsitter-applications/{$application->id}/documents/0")->assertOk();
    $this->actingAs($admin)->get("/api/petsitter-applications/{$application->id}/documents/1")->assertOk();
    $this->actingAs($admin)->getJson("/api/petsitter-applications/{$application->id}/documents/2")->assertNotFound();

    $this->actingAs($user)->getJson("/api/petsitter-applications/{$application->id}/documents/0")->assertForbidden();
});

test('the admin list brings the current and previous applications', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();
    rejectApplication($this, $admin, $user->id)->assertOk();
    applyAsPetsitter($this, $user)->assertCreated();

    $this->actingAs($admin)->getJson('/api/petsitters?approval_status=pending')
        ->assertOk()
        ->assertJsonPath('data.0.application.status', 'pending')
        ->assertJsonCount(2, 'data.0.application.documents')
        ->assertJsonPath('data.0.application.documents.0.url', '/api/petsitter-applications/'.PetsitterApplication::latest('id')->value('id').'/documents/0')
        ->assertJsonCount(2, 'data.0.applications')
        ->assertJsonPath('data.0.applications_remaining', 1);
});

test('the owner profile says how many applications a rejected user can still make', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    applyAsPetsitter($this, $user)->assertCreated();
    rejectApplication($this, $admin, $user->id)->assertOk();

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('petsitterApplicationStatus', 'rejected')
            ->where('petsitterApplicationsRemaining', 2));
});

test('other users do not see the applications of a petsitter', function () {
    $petsitter = Petsitter::factory()->approved()->create();
    PetsitterApplication::create(['user_id' => $petsitter->user_id, 'identification_documents' => ['x.pdf']]);

    $this->actingAs(User::factory()->create())->getJson("/api/petsitters/{$petsitter->user_id}")
        ->assertOk()
        ->assertJsonMissingPath('data.application')
        ->assertJsonMissingPath('data.applications');
});
