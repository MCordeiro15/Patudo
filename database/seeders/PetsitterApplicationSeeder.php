<?php

/**
 * Garante que cada petsitter tem pelo menos uma candidatura. Depois de um seed, os petsitters vêm do
 * ExportedDataSeeder (ou do PetsitterSeeder) e os que não trouxerem candidatura ficam com uma, com o
 * estado e o documento que têm no perfil. Os que já têm candidatura (vindas do export) não mudam.
 * Makes sure every petsitter has at least one application, built from its current status and document.
 */
namespace Database\Seeders;

use App\Models\Petsitter;
use App\Models\PetsitterApplication;
use Illuminate\Database\Seeder;

class PetsitterApplicationSeeder extends Seeder
{
    public function run(): void
    {
        Petsitter::query()
            ->whereDoesntHave('applications')
            ->get()
            ->each(function (Petsitter $petsitter) {
                $status = $petsitter->approval_status ?: PetsitterApplication::PENDING;

                $application = new PetsitterApplication([
                    'user_id' => $petsitter->user_id,
                    'identification_documents' => $petsitter->identification_documents
                        ? [$petsitter->identification_documents]
                        : [],
                ]);
                $application->forceFill([
                    'status' => $status,
                    'decided_at' => $status === PetsitterApplication::PENDING ? null : $petsitter->updated_at,
                ])->save();
            });
    }
}
