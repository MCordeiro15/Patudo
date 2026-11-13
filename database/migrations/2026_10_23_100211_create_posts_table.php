<?php


/**
 * Migração que cria a tabela posts com campos para petsitter_id, descrição e array de fotos (JSON).
 * Migration that creates the posts table with fields for petsitter_id, description, and photo array (JSON).
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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->cascadeOnDelete();
            $table->text('description');
            $table->json('photos')->nullable();
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
