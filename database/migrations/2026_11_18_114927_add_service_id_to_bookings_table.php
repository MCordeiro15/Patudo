<?php

/**
 * Migração que acrescenta service_id à tabela bookings, para guardar o serviço escolhido em cada reserva. É opcional (nulo) por causa das reservas antigas.
 * Migration that adds service_id to the bookings table, to store the service chosen in each booking. It is nullable because of older bookings.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // restrictOnDelete: um serviço com reservas não pode ser apagado
            $table->foreignId('service_id')->nullable()->after('petsitter_id')
                ->constrained('petsitter_services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
