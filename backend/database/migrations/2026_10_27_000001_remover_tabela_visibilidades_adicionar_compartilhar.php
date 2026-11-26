<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove tabela obsoleta de visibilidades se existir
        Schema::dropIfExists('visibilidades_sessao_grupo');

        // 2. Adiciona coluna booleana 'compartilhar' se ainda não existir
        if (Schema::hasTable('sessoes_estudo') && !Schema::hasColumn('sessoes_estudo', 'compartilhar')) {
            Schema::table('sessoes_estudo', function (Blueprint $table) {
                $table->boolean('compartilhar')->default(true)->after('caminho_foto');
                $table->index(['usuario_id', 'compartilhar']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessoes_estudo') && Schema::hasColumn('sessoes_estudo', 'compartilhar')) {
            Schema::table('sessoes_estudo', function (Blueprint $table) {
                $table->dropIndex(['usuario_id', 'compartilhar']);
                $table->dropColumn('compartilhar');
            });
        }
    }
};
