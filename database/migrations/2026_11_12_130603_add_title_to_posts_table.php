<?php


/**
 * Migração que adiciona a coluna title à tabela posts para permitir títulos nos posts.
 * Migration that adds the title column to the posts table to allow titles on posts.
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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->nullable()->after('petsitter_id');
        });
    }

    /**
     * Reverte a migração, removendo as alterações feitas pelo método up.
     * Reverts the migration, removing the changes made by the up method.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
