<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_estudo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->date('data_estudo');
            $table->string('disciplina', 100);
            $table->unsignedSmallInteger('minutos');
            $table->unsignedSmallInteger('questoes')->default(0);
            $table->text('anotacoes')->nullable();
            $table->string('caminho_foto')->nullable();
            $table->boolean('compartilhar')->default(true);
            $table->timestamps();

            $table->index(['usuario_id', 'data_estudo']);
            $table->index(['usuario_id', 'compartilhar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_estudo');
    }
};
