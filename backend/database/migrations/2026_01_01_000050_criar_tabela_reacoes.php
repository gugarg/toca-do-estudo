<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_estudo_id')->constrained('sessoes_estudo')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('tipo_reacao', 20)->default('fire');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sessao_estudo_id', 'usuario_id']);
            $table->index('sessao_estudo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reacoes');
    }
};
