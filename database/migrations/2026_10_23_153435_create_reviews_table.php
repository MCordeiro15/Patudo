<?php


/**
 * Migração que cria a tabela reviews com campos para estrelas (1-5), comentário e relações com owner e petsitter.
 * Migration that creates the reviews table with fields for stars (1-5), comment, and relationships with owner and petsitter.
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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete(); // 0..1 review per booking
            $table->foreignId('petsitter_id')->constrained('petsitters', 'user_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('star_rating'); // 1 to 5, validated in the Form Request
            $table->text('comment')->nullable();
            $table->boolean('visible')->default(true); // admin can hide it (hide())
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
