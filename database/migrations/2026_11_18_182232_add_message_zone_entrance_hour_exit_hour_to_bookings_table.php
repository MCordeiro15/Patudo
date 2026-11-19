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
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('message')->nullable();
            $table->text('zone')->nullable();
            $table->time('entrance_hour')->nullable();
            $table->time('exit_hour')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'message',
                'zone',
                'entrance_hour',
                'exit_hour',
            ]);
        });
    }
};
