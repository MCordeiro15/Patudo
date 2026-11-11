<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            $table->json('availability_days')->nullable()->after('identification_documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petsitters', function (Blueprint $table) {
            $table->dropColumn('availability_days');
        });
    }
};
