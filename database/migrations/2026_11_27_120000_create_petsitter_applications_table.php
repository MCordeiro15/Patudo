<?php

/**
 * Migração que cria as candidaturas a petsitter: cada candidatura fica num registo próprio, com os documentos
 * de identificação (vários ficheiros), o estado, os motivos e a mensagem da recusa e o admin que decidiu.
 * Os petsitters que já existem ficam com uma candidatura, com o estado e o documento que tinham.
 * Migration that creates petsitter applications: one row per application, with several identification files,
 * the status, the rejection reasons and message and the admin who decided. Existing petsitters get one application.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petsitter_applications', function (Blueprint $table) {
            $table->id();
            // o candidato (o mesmo id que petsitters.user_id)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected
            // lista de caminhos no disco privado (ex.: frente e verso do Cartão de Cidadão)
            $table->json('identification_documents');
            $table->json('reasons')->nullable(); // códigos dos motivos da recusa
            $table->text('message')->nullable(); // mensagem do admin para o candidato
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // uma candidatura para cada petsitter que já existe, com o estado e o documento atuais
        DB::table('petsitters')->orderBy('user_id')->get()->each(function ($petsitter) {
            $status = $petsitter->approval_status ?: 'pending';

            DB::table('petsitter_applications')->insert([
                'user_id' => $petsitter->user_id,
                'status' => $status,
                'identification_documents' => json_encode(
                    $petsitter->identification_documents ? [$petsitter->identification_documents] : []
                ),
                'decided_at' => $status === 'pending' ? null : $petsitter->updated_at,
                'created_at' => $petsitter->created_at,
                'updated_at' => $petsitter->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petsitter_applications');
    }
};
