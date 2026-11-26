<?php

/**
 * Remove o saldo fictício dos utilizadores: as reservas deixam de ser pagas com saldo.
 * Removes the fictitious user balance: bookings are no longer paid with credit.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('credit', 10, 2)->default(0)->after('account_status');
        });
    }
};
