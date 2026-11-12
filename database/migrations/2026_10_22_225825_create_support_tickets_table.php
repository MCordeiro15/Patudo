<?php


/**
 * Migração que cria a tabela support_tickets com campos para tipo (complaint/help), estado, título e descrição.
 * Migration that creates the support_tickets table with fields for type (complaint/help), status, title, and description.
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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('claimed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reported_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('subject');
            $table->text('description');
            $table->string('type');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
         * Reverte a migração, removendo as alterações feitas pelo método up.
         * Reverts the migration, removing the changes made by the up method.
         */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
