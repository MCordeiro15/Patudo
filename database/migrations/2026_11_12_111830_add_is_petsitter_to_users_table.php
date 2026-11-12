<?php


/**
 * Migração que adiciona a coluna is_petsitter (boolean) à tabela users para identificar utilizadores que são petsitters.
 * Migration that adds the is_petsitter (boolean) column to the users table to identify users who are petsitters.
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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_petsitter')->default(false)->after('is_admin');
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_petsitter');
        });
    }
};
