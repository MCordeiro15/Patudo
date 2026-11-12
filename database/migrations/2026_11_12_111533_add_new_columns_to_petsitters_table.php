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
            $table->string('profile_photo')->nullable()->after('biography');
            $table->string('city')->nullable()->after('service_location');
            $table->decimal('average_rating', 3, 2)->nullable()->after('price_per_day');
            $table->string('short_bio', 255)->nullable()->after('biography');
        });
    }

    /**
     * Reverse the migrations.
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
