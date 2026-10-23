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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
