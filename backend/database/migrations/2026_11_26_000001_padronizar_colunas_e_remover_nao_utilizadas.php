<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove colunas não utilizadas (email_verified_at, url_capa) e padroniza
     * timestamps das tabelas de domínio para português (data_criacao, data_edicao).
     */
    public function up(): void
    {
        if (Schema::hasTable('usuarios')) {
            if (Schema::hasColumn('usuarios', 'email_verified_at')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->dropColumn('email_verified_at');
                });
            }

            if (Schema::hasColumn('usuarios', 'created_at') && !Schema::hasColumn('usuarios', 'data_criacao')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->renameColumn('created_at', 'data_criacao');
                });
            }

            if (Schema::hasColumn('usuarios', 'updated_at') && !Schema::hasColumn('usuarios', 'data_edicao')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->renameColumn('updated_at', 'data_edicao');
                });
            }
        }

        if (Schema::hasTable('grupos')) {
            if (Schema::hasColumn('grupos', 'url_capa')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->dropColumn('url_capa');
                });
            }

            if (Schema::hasColumn('grupos', 'created_at') && !Schema::hasColumn('grupos', 'data_criacao')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->renameColumn('created_at', 'data_criacao');
                });
            }

            if (Schema::hasColumn('grupos', 'updated_at') && !Schema::hasColumn('grupos', 'data_edicao')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->renameColumn('updated_at', 'data_edicao');
                });
            }
        }

        if (Schema::hasTable('sessoes_estudo')) {
            if (Schema::hasColumn('sessoes_estudo', 'created_at') && !Schema::hasColumn('sessoes_estudo', 'data_criacao')) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->renameColumn('created_at', 'data_criacao');
                });
            }

            if (Schema::hasColumn('sessoes_estudo', 'updated_at') && !Schema::hasColumn('sessoes_estudo', 'data_edicao')) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->renameColumn('updated_at', 'data_edicao');
                });
            }
        }

        if (Schema::hasTable('reacoes')) {
            if (Schema::hasColumn('reacoes', 'created_at') && !Schema::hasColumn('reacoes', 'data_criacao')) {
                Schema::table('reacoes', function (Blueprint $table) {
                    $table->renameColumn('created_at', 'data_criacao');
                });
            }
        }

        if (Schema::hasTable('comentarios_sessao_estudo')) {
            if (Schema::hasColumn('comentarios_sessao_estudo', 'created_at') && !Schema::hasColumn('comentarios_sessao_estudo', 'data_criacao')) {
                Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                    $table->renameColumn('created_at', 'data_criacao');
                });
            }

            if (Schema::hasColumn('comentarios_sessao_estudo', 'updated_at') && !Schema::hasColumn('comentarios_sessao_estudo', 'data_edicao')) {
                Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                    $table->renameColumn('updated_at', 'data_edicao');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('comentarios_sessao_estudo')) {
            if (Schema::hasColumn('comentarios_sessao_estudo', 'data_criacao') && !Schema::hasColumn('comentarios_sessao_estudo', 'created_at')) {
                Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                    $table->renameColumn('data_criacao', 'created_at');
                });
            }

            if (Schema::hasColumn('comentarios_sessao_estudo', 'data_edicao') && !Schema::hasColumn('comentarios_sessao_estudo', 'updated_at')) {
                Schema::table('comentarios_sessao_estudo', function (Blueprint $table) {
                    $table->renameColumn('data_edicao', 'updated_at');
                });
            }
        }

        if (Schema::hasTable('reacoes')) {
            if (Schema::hasColumn('reacoes', 'data_criacao') && !Schema::hasColumn('reacoes', 'created_at')) {
                Schema::table('reacoes', function (Blueprint $table) {
                    $table->renameColumn('data_criacao', 'created_at');
                });
            }
        }

        if (Schema::hasTable('sessoes_estudo')) {
            if (Schema::hasColumn('sessoes_estudo', 'data_criacao') && !Schema::hasColumn('sessoes_estudo', 'created_at')) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->renameColumn('data_criacao', 'created_at');
                });
            }

            if (Schema::hasColumn('sessoes_estudo', 'data_edicao') && !Schema::hasColumn('sessoes_estudo', 'updated_at')) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->renameColumn('data_edicao', 'updated_at');
                });
            }
        }

        if (Schema::hasTable('grupos')) {
            if (Schema::hasColumn('grupos', 'data_criacao') && !Schema::hasColumn('grupos', 'created_at')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->renameColumn('data_criacao', 'created_at');
                });
            }

            if (Schema::hasColumn('grupos', 'data_edicao') && !Schema::hasColumn('grupos', 'updated_at')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->renameColumn('data_edicao', 'updated_at');
                });
            }

            if (!Schema::hasColumn('grupos', 'url_capa')) {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->string('url_capa')->nullable()->after('descricao');
                });
            }
        }

        if (Schema::hasTable('usuarios')) {
            if (Schema::hasColumn('usuarios', 'data_criacao') && !Schema::hasColumn('usuarios', 'created_at')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->renameColumn('data_criacao', 'created_at');
                });
            }

            if (Schema::hasColumn('usuarios', 'data_edicao') && !Schema::hasColumn('usuarios', 'updated_at')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->renameColumn('data_edicao', 'updated_at');
                });
            }

            if (!Schema::hasColumn('usuarios', 'email_verified_at')) {
                Schema::table('usuarios', function (Blueprint $table) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                });
            }
        }
    }
};
