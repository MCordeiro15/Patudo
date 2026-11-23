<?php

/**
 * Migração que adiciona a idade aos animais e alarga a coluna da foto, que passa a guardar a imagem em base64 (data URI).
 * Migration that adds the age to animals and widens the photo column, which now stores the image as base64 (data URI).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            // idade em anos (opcional)
            $table->unsignedTinyInteger('age')->nullable()->after('size');
            // a foto passa a ser "data:image/png;base64,...", como o avatar dos users
            $table->longText('photo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('age');
            $table->string('photo')->nullable()->change();
        });
    }
};
