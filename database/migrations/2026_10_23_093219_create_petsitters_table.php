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
        /*`availableDates` is not a column. It resides in a separate table,
         which is created using `php artisan make:model Availability -m`.*/
        Schema::create('petsitters', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('biography')->nullable();
            $table->text('experience');
            $table->string('service_location');
            $table->decimal('price_per_day', 8, 2);
            $table->json('accepted_sizes')->nullable();
            $table->json('accepted_animal_types')->nullable();
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->string('identification_documents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petsitters');
    }
};
