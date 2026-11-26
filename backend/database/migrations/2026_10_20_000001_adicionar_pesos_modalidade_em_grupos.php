<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            if (!Schema::hasColumn('grupos', 'pontos_hora')) {
                $table->decimal('pontos_hora', 8, 2)->nullable()->after('metrica_padrao');
            }
            if (!Schema::hasColumn('grupos', 'pontos_questao')) {
                $table->decimal('pontos_questao', 8, 2)->nullable()->after('pontos_hora');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            if (Schema::hasColumn('grupos', 'pontos_questao')) {
                $table->dropColumn('pontos_questao');
            }
            if (Schema::hasColumn('grupos', 'pontos_hora')) {
                $table->dropColumn('pontos_hora');
            }
        });
    }
};
