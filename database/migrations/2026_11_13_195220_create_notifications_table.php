<?php


/**
 * Migração que cria a tabela notifications com campos para tipo, mensagem, estado de leitura e relação com o utilizador.
 * Migration that creates the notifications table with fields for type, message, read status, and relationship with the user.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migração, criando a tabela notifications.
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverte a migração, removendo a tabela notifications.
     * Reverts the migration, removing the changes made by the up method.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
