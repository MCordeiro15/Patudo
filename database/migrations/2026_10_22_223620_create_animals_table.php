<?php


/**
 * Migração que cria a tabela animals com campos básicos (id, user_id, name, species, breed, size, timestamps).
 * Migration that creates the animals table with basic fields (id, user_id, name, species, breed, size, timestamps).
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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('species');
            $table->string('breed');
            $table->string('size');
            $table->text('special_needs')->nullable();
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
