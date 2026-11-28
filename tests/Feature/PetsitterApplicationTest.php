<?php

/**
 * Testes da tabela de candidaturas a petsitter (petsitter_applications): vários documentos de identificação,
 * candidaturas antigas guardadas, campos do admin protegidos, migração dos petsitters que já existiam,
 * seeder e export.
 * Tests for the petsitter applications table: several identification files, old applications kept,
 * admin-only fields, migration of existing petsitters, seeder and export.
 */
use App\Models\Petsitter;
use App\Models\PetsitterApplication;
use App\Models\User;
use Database\Seeders\PetsitterApplicationSeeder;
use Illuminate\Support\Facades\File;

/** Cria uma candidatura com o estado dado (o estado não é mass assignable). */
function makeApplication(Petsitter $petsitter, string $status = PetsitterApplication::PENDING, array $documents = ['identification-documents/doc.pdf']): PetsitterApplication
{
    $application = new PetsitterApplication([
        'user_id' => $petsitter->user_id,
        'identification_documents' => $documents,
    ]);
    $application->forceFill(['status' => $status])->save();

    return $application;
}

test('an application keeps several identification documents as a list', function () {
    $petsitter = Petsitter::factory()->create();

    $application = makeApplication($petsitter, documents: [
        'identification-documents/1/cc-frente.jpg',
        'identification-documents/1/cc-verso.jpg',
    ]);

    expect($application->fresh()->identification_documents)->toBe([
        'identification-documents/1/cc-frente.jpg',
        'identification-documents/1/cc-verso.jpg',
    ]);
});

test('a new application starts pending and the admin fields cannot be mass assigned', function () {
    $petsitter = Petsitter::factory()->create();
    $admin = User::factory()->admin()->create();

    $application = PetsitterApplication::create([
        'user_id' => $petsitter->user_id,
        'identification_documents' => ['identification-documents/doc.pdf'],
        'status' => PetsitterApplication::APPROVED,
        'reasons' => ['other'],
        'message' => 'aprovado',
        'decided_by' => $admin->id,
        'decided_at' => now(),
    ])->fresh();

    expect($application->status)->toBe(PetsitterApplication::PENDING)
        ->and($application->reasons)->toBeNull()
        ->and($application->message)->toBeNull()
        ->and($application->decided_by)->toBeNull()
        ->and($application->decided_at)->toBeNull();
});

test('old applications are kept and the latest one is the newest', function () {
    $petsitter = Petsitter::factory()->create();
    $first = makeApplication($petsitter, PetsitterApplication::REJECTED);
    $second = makeApplication($petsitter, PetsitterApplication::REJECTED);
    $third = makeApplication($petsitter);

    expect($petsitter->applications()->pluck('id')->all())->toBe([$third->id, $second->id, $first->id])
        ->and($petsitter->latestApplication->id)->toBe($third->id)
        ->and($petsitter->user->petsitterApplications()->count())->toBe(3);
});

test('rejection reasons and the deciding admin are stored', function () {
    $petsitter = Petsitter::factory()->create();
    $admin = User::factory()->admin()->create();
    $application = makeApplication($petsitter);

    $application->forceFill([
        'status' => PetsitterApplication::REJECTED,
        'reasons' => ['illegible_document', 'other'],
        'message' => 'Envia uma foto mais nítida.',
        'decided_by' => $admin->id,
        'decided_at' => now(),
    ])->save();

    $application = $application->fresh();
    expect($application->reasons)->toBe(['illegible_document', 'other'])
        ->and($application->decidedBy->id)->toBe($admin->id)
        ->and($application->decided_at)->not->toBeNull();
});

test('applications are deleted with the user and keep existing when the deciding admin is deleted', function () {
    $petsitter = Petsitter::factory()->create();
    $admin = User::factory()->admin()->create();
    $application = makeApplication($petsitter);
    $application->forceFill(['decided_by' => $admin->id])->save();

    $admin->delete();
    expect($application->fresh()->decided_by)->toBeNull();

    $petsitter->user->delete();
    expect(PetsitterApplication::find($application->id))->toBeNull();
});

test('the migration creates one application for each existing petsitter', function () {
    $approved = Petsitter::factory()->approved()->create(['identification_documents' => 'identification-documents/a.pdf']);
    $pending = Petsitter::factory()->create(['identification_documents' => 'identification-documents/b.pdf']);

    $migration = require database_path('migrations/2026_11_27_120000_create_petsitter_applications_table.php');
    $migration->down();
    $migration->up();

    expect(PetsitterApplication::count())->toBe(2);

    $forApproved = PetsitterApplication::where('user_id', $approved->user_id)->first();
    expect($forApproved->status)->toBe('approved')
        ->and($forApproved->identification_documents)->toBe(['identification-documents/a.pdf'])
        ->and($forApproved->decided_at)->not->toBeNull();

    $forPending = PetsitterApplication::where('user_id', $pending->user_id)->first();
    expect($forPending->status)->toBe('pending')
        ->and($forPending->identification_documents)->toBe(['identification-documents/b.pdf'])
        ->and($forPending->decided_at)->toBeNull();
});

test('the seeder gives an application only to petsitters without one', function () {
    $withoutApplication = Petsitter::factory()->approved()->create(['identification_documents' => 'identification-documents/c.pdf']);
    $withApplication = Petsitter::factory()->create();
    makeApplication($withApplication, PetsitterApplication::REJECTED);

    $this->seed(PetsitterApplicationSeeder::class);
    $this->seed(PetsitterApplicationSeeder::class); // correr duas vezes não duplica

    expect($withoutApplication->applications()->count())->toBe(1)
        ->and($withoutApplication->latestApplication->status)->toBe('approved')
        ->and($withoutApplication->latestApplication->identification_documents)->toBe(['identification-documents/c.pdf'])
        ->and($withApplication->applications()->count())->toBe(1)
        ->and($withApplication->latestApplication->status)->toBe('rejected');
});

test('the database export includes the applications', function () {
    $petsitter = Petsitter::factory()->create();
    makeApplication($petsitter, documents: ['identification-documents/x.pdf', 'identification-documents/y.pdf']);
    $generatedSeeder = null;

    File::shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) use (&$generatedSeeder) {
        $generatedSeeder = $contents;

        return true;
    });

    $this->artisan('db:export')->assertSuccessful();

    expect($generatedSeeder)->toContain("'petsitter_applications' =>")
        ->toContain('identification-documents\\\\/x.pdf');
});
