<?php


/**
 * Migração que cria a tabela petsitter_availabilities para armazenar datas de disponibilidade individual de cada petsitter.
 * Migration that creates the petsitter_availabilities table to store individual availability dates for each petsitter.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
 * Run the migrations.
 * Run the migrations.
 */
    public function up(): void
    {
        Schema::create('petsitter_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            $table->unique(['petsitter_id', 'date']);
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('petsitter_availabilities');
    }
};
