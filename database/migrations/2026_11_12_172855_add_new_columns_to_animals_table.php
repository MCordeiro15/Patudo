<?php


/**
 * Migração que adiciona colunas extras à tabela animals (gender, photo, special_needs, weight).
 * Migration that adds extra columns to the animals table (gender, photo, special_needs, weight).
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
        Schema::table('animals', function (Blueprint $table) {
            $table->string('aggression')->nullable()->after('special_needs');
            $table->text('medication')->nullable()->after('aggression');
            $table->string('sex')->nullable()->after('medication');
            $table->string('photo')->nullable()->after('sex');
            $table->decimal('weight', 5, 2)->nullable()->after('photo');
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['aggression', 'medication', 'sex', 'photo', 'weight']);
        });
    }
};
