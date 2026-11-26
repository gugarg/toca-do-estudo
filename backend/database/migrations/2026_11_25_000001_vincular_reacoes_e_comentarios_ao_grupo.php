<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula reações e comentários diretamente à comunidade (grupo_id) em que foram feitos.
     */
    public function up(): void
    {
        // 1. Adiciona coluna grupo_id temporariamente anulável para permitir backfill seguro
        if (!Schema::hasColumn('reacoes', 'grupo_id')) {
            Schema::table('reacoes', function (Blueprint $table) {
                $table->unsignedBigInteger('grupo_id')->nullable()->after('sessao_estudo_id');
            });
        }

        if (!Schema::hasColumn('comentarios_sessao_estudo', 'grupo_id')) {
            Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                $table->unsignedBigInteger('grupo_id')->nullable()->after('sessao_estudo_id');
            });
        }

        // 2. Backfill dos registros preexistentes: prioriza primeiro grupo em comum entre quem interagiu e o autor
        $this->backfillGrupoId('reacoes');
        $this->backfillGrupoId('comentarios_sessao_estudo');

        // 3. Em reacoes: cria a nova UNIQUE composta (que também atende a FK de sessao_estudo_id)
        // antes de remover a UNIQUE antiga, e torna grupo_id NOT NULL com FK.
        $indicesReacoes = collect(DB::select('SHOW INDEX FROM reacoes'))->pluck('Key_name')->all();

        Schema::table('reacoes', function (Blueprint $table) use ($indicesReacoes) {
            if (!in_array('reacoes_sessao_usuario_grupo_unique', $indicesReacoes, true)) {
                $table->unique(['sessao_estudo_id', 'usuario_id', 'grupo_id'], 'reacoes_sessao_usuario_grupo_unique');
            }

            if (!in_array('idx_reacoes_sessao_grupo', $indicesReacoes, true)) {
                $table->index(['sessao_estudo_id', 'grupo_id'], 'idx_reacoes_sessao_grupo');
            }
        });

        $indicesReacoes = collect(DB::select('SHOW INDEX FROM reacoes'))->pluck('Key_name')->all();
        if (in_array('reacoes_sessao_estudo_id_usuario_id_unique', $indicesReacoes, true)) {
            Schema::table('reacoes', function (Blueprint $table) {
                $table->dropUnique('reacoes_sessao_estudo_id_usuario_id_unique');
            });
        }

        Schema::table('reacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('grupo_id')->nullable(false)->change();
            $table->foreign('grupo_id', 'reacoes_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->onDelete('cascade');
        });

        // 4. Em comentarios_sessao_estudo: torna grupo_id NOT NULL com FK e índice composto
        $indicesComentarios = collect(DB::select('SHOW INDEX FROM comentarios_sessao_estudo'))->pluck('Key_name')->all();

        Schema::table('comentarios_sessao_estudo', function (Blueprint $table) use ($indicesComentarios) {
            $table->unsignedBigInteger('grupo_id')->nullable(false)->change();
            $table->foreign('grupo_id', 'comentarios_sessao_estudo_grupo_id_foreign')
                ->references('id')
                ->on('grupos')
                ->onDelete('cascade');

            if (!in_array('idx_comentarios_sessao_grupo_data', $indicesComentarios, true)) {
                $table->index(['sessao_estudo_id', 'grupo_id', 'created_at'], 'idx_comentarios_sessao_grupo_data');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('comentarios_sessao_estudo', 'grupo_id')) {
            Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                $table->dropForeign('comentarios_sessao_estudo_grupo_id_foreign');
                $table->dropIndex('idx_comentarios_sessao_grupo_data');
                $table->dropColumn('grupo_id');
            });
        }

        if (Schema::hasColumn('reacoes', 'grupo_id')) {
            $indicesReacoes = collect(DB::select('SHOW INDEX FROM reacoes'))->pluck('Key_name')->all();

            Schema::table('reacoes', function (Blueprint $table) use ($indicesReacoes) {
                $table->dropForeign('reacoes_grupo_id_foreign');
                if (!in_array('reacoes_sessao_estudo_id_usuario_id_unique', $indicesReacoes, true)) {
                    $table->unique(['sessao_estudo_id', 'usuario_id'], 'reacoes_sessao_estudo_id_usuario_id_unique');
                }
            });

            Schema::table('reacoes', function (Blueprint $table) {
                $table->dropUnique('reacoes_sessao_usuario_grupo_unique');
                $table->dropIndex('idx_reacoes_sessao_grupo');
                $table->dropColumn('grupo_id');
            });
        }
    }

    private function backfillGrupoId(string $tabela): void
    {
        $registros = DB::table($tabela)
            ->whereNull('grupo_id')
            ->get(['id', 'sessao_estudo_id', 'usuario_id']);

        foreach ($registros as $registro) {
            $sessao = DB::table('sessoes_estudo')
                ->where('id', $registro->sessao_estudo_id)
                ->first(['usuario_id']);

            if (!$sessao) {
                DB::table($tabela)->where('id', $registro->id)->delete();
                continue;
            }

            // Prioriza o primeiro grupo em comum entre autor da sessão e autor da interação
            $grupoId = DB::table('membros_grupo as autor')
                ->join('membros_grupo as interagente', 'interagente.grupo_id', '=', 'autor.grupo_id')
                ->where('autor.usuario_id', $sessao->usuario_id)
                ->where('interagente.usuario_id', $registro->usuario_id)
                ->orderBy('autor.grupo_id', 'asc')
                ->value('autor.grupo_id');

            if (!$grupoId) {
                $grupoId = DB::table('membros_grupo')
                    ->where('usuario_id', $sessao->usuario_id)
                    ->orderBy('grupo_id', 'asc')
                    ->value('grupo_id');
            }

            if ($grupoId) {
                DB::table($tabela)
                    ->where('id', $registro->id)
                    ->update(['grupo_id' => $grupoId]);
            } else {
                DB::table($tabela)->where('id', $registro->id)->delete();
            }
        }
    }
};
