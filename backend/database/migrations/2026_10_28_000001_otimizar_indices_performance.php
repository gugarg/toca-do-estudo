<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Elimina tabela zumbi descontinuada de cache
        Schema::dropIfExists('cache_estatisticas_usuario');

        // 2. Otimização de índices em membros_grupo
        if (Schema::hasTable('membros_grupo')) {
            $indicesMembros = collect(DB::select('SHOW INDEX FROM membros_grupo'))->pluck('Key_name')->all();

            Schema::table('membros_grupo', function (Blueprint $table) use ($indicesMembros) {
                // Dropa índice idêntico redundante mantendo a UNIQUE KEY
                if (in_array('membros_grupo_grupo_id_usuario_id_index', $indicesMembros, true)) {
                    $table->dropIndex('membros_grupo_grupo_id_usuario_id_index');
                }

                // Adiciona índice reverso composto (usuario_id, grupo_id) para aceleração de buscas dos grupos do usuário
                if (!in_array('idx_membros_grupo_usuario_grupo', $indicesMembros, true)) {
                    $table->index(['usuario_id', 'grupo_id'], 'idx_membros_grupo_usuario_grupo');
                }
            });
        }

        // 3. Otimização de índices em reacoes
        if (Schema::hasTable('reacoes')) {
            $indicesReacoes = collect(DB::select('SHOW INDEX FROM reacoes'))->pluck('Key_name')->all();

            Schema::table('reacoes', function (Blueprint $table) use ($indicesReacoes) {
                // Dropa índice simples redundante coberto pela UNIQUE KEY (sessao_estudo_id, usuario_id)
                if (in_array('reacoes_sessao_estudo_id_index', $indicesReacoes, true)) {
                    $table->dropIndex('reacoes_sessao_estudo_id_index');
                }
            });
        }

        // 4. Otimização de índices em sessoes_estudo (Feeding e Ranking com Index Scan)
        if (Schema::hasTable('sessoes_estudo')) {
            $indicesSessoes = collect(DB::select('SHOW INDEX FROM sessoes_estudo'))->pluck('Key_name')->all();

            Schema::table('sessoes_estudo', function (Blueprint $table) use ($indicesSessoes) {
                if (!in_array('idx_sessoes_estudo_feed_ranking', $indicesSessoes, true)) {
                    $table->index(['usuario_id', 'compartilhar', 'data_estudo'], 'idx_sessoes_estudo_feed_ranking');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessoes_estudo')) {
            Schema::table('sessoes_estudo', function (Blueprint $table) {
                $table->dropIndex('idx_sessoes_estudo_feed_ranking');
            });
        }

        if (Schema::hasTable('reacoes')) {
            Schema::table('reacoes', function (Blueprint $table) {
                $table->index('sessao_estudo_id', 'reacoes_sessao_estudo_id_index');
            });
        }

        if (Schema::hasTable('membros_grupo')) {
            Schema::table('membros_grupo', function (Blueprint $table) {
                $table->dropIndex('idx_membros_grupo_usuario_grupo');
                $table->index(['grupo_id', 'usuario_id'], 'membros_grupo_grupo_id_usuario_id_index');
            });
        }
    }
};
