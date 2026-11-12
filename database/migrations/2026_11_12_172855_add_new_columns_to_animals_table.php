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
        Schema::table('animals', function (Blueprint $table) {
            $table->string('aggression')->nullable()->after('special_needs');
            $table->text('medication')->nullable()->after('aggression');
            $table->string('sex')->nullable()->after('medication');
            $table->string('photo')->nullable()->after('sex');
            $table->decimal('weight', 5, 2)->nullable()->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['aggression', 'medication', 'sex', 'photo', 'weight']);
        });
    }
};
