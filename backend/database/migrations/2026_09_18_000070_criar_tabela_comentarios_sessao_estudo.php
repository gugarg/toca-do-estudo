<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios_sessao_estudo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_estudo_id')->constrained('sessoes_estudo')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->text('conteudo');
            $table->timestamps();

            $table->index(['sessao_estudo_id', 'created_at']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios_sessao_estudo');
    }
};
