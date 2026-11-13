<?php


/**
 * Migração que adiciona colunas extras à tabela petsitters (gender, ID documents, etc.).
 * Migration that adds extra columns to the petsitters table (gender, ID documents, etc.).
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
            $table->string('profile_photo')->nullable()->after('biography');
            $table->string('city')->nullable()->after('service_location');
            $table->decimal('average_rating', 3, 2)->nullable()->after('price_per_day');
            $table->string('short_bio', 255)->nullable()->after('biography');
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            $table->dropColumn('profile_photo');
            $table->dropColumn('city');
            $table->dropColumn('average_rating');
            $table->dropColumn('short_bio');
        });
    }
};
