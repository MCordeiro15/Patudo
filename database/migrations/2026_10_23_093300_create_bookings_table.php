<?php


/**
 * Migração que cria a tabela bookings com campos para datas (start_date, end_date), estado, preço total e notas.
 * Migration that creates the bookings table with fields for dates (start_date, end_date), status, total price, and notes.
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
