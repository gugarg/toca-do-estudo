<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove tabelas ociosas de fila (jobs, job_batches, failed_jobs) e índice redundante em sessoes_estudo.
     */
    public function up(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');

        if (Schema::hasTable('sessoes_estudo')) {
            $indices = collect(DB::select('SHOW INDEX FROM sessoes_estudo'))->pluck('Key_name')->all();

            if (in_array('sessoes_estudo_usuario_id_compartilhar_index', $indices, true)) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->dropIndex('sessoes_estudo_usuario_id_compartilhar_index');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessoes_estudo')) {
            $indices = collect(DB::select('SHOW INDEX FROM sessoes_estudo'))->pluck('Key_name')->all();

            if (!in_array('sessoes_estudo_usuario_id_compartilhar_index', $indices, true)) {
                Schema::table('sessoes_estudo', function (Blueprint $table) {
                    $table->index(['usuario_id', 'compartilhar'], 'sessoes_estudo_usuario_id_compartilhar_index');
                });
            }
        }
    }
};
