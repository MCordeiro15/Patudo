<?php

/**
 * Migração que cria a tabela petsitter_services com os serviços de cada petsitter (tipo e preço), sem repetir o mesmo tipo por petsitter.
 * Migration that creates the petsitter_services table with each petsitter's services (type and price), without repeating the same type per petsitter.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petsitter_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('price', 8, 2);
            $table->timestamps();

            $table->unique(['petsitter_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petsitter_services');
    }
};
