<?php

/**
 * Migração que troca o campo visible das avaliações por um estado: published (publicada), flagged (sinalizada
 * por um utilizador, à espera do admin) ou removed (removida pelo admin). As escondidas passam a removed.
 * Migration that replaces the reviews' visible field with a status: published, flagged or removed.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('published')->after('comment')->index();
        });

        // as avaliações escondidas pelo admin (visible = false) ficam como removidas
        DB::table('reviews')->where('visible', false)->update(['status' => 'removed']);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('visible');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('visible')->default(true)->after('comment');
        });

        DB::table('reviews')->where('status', 'removed')->update(['visible' => false]);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
