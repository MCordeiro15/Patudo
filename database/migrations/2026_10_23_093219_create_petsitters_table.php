<?php


/**
 * Migração que cria a tabela petsitters com campos para biografia, experiência, preço, tamanhos aceites e localização.
 * Migration that creates the petsitters table with fields for biography, experience, price, accepted sizes, and location.
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
        /*`availableDates` is not a column. It resides in a separate table,
         which is created using `php artisan make:model Availability -m`.*/
        Schema::create('petsitters', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('biography')->nullable();
            $table->text('experience');
            $table->string('service_location');
            $table->integer('age');
            $table->decimal('price_per_day', 8, 2);
            $table->json('accepted_sizes')->nullable();
            $table->json('accepted_animal_types')->nullable();
            $table->string('sex')->nullable();
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->string('identification_documents');
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('petsitters');
    }
};
