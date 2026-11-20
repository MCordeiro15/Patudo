<?php

/**
 * Acrescenta o código postal ao petsitter (formulário "Quero ser petsitter"). Formato português: 0000-000.
 * Adds the postal code to the petsitter ("Become a petsitter" form). Portuguese format: 0000-000.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            // nullable: os petsitters que já existem não têm código postal
            $table->string('postal_code', 8)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            $table->dropColumn('postal_code');
        });
    }
};
