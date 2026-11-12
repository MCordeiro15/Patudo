<?php


/**
 * Migração que cria a tabela favourites para a relação many-to-many entre utilizadores e petsitters favoritos.
 * Migration that creates the favourites table for the many-to-many relationship between users and favorite petsitters.
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
        Schema::create('favourites', function (Blueprint $table) {
            $table->timestamps();
            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['petsitter_id', 'user_id']);
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('favourites');
    }
};
