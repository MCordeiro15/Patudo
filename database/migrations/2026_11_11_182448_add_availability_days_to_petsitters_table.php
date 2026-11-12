<?php


/**
 * Migração que adiciona a coluna availability_days (JSON) à tabela petsitters para armazenar os dias da semana disponíveis.
 * Migration that adds the availability_days (JSON) column to the petsitters table to store available days of the week.
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
        Schema::table('petsitters', function (Blueprint $table) {
            $table->json('availability_days')->nullable()->after('identification_documents');
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            $table->dropColumn('availability_days');
        });
    }
};
