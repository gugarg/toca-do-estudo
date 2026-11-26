<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proprietario_id')->constrained('usuarios')->onDelete('restrict');
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->string('url_capa')->nullable();
            $table->string('codigo_convite', 12)->unique();
            $table->string('metrica_padrao', 20)->default('dias_ativos');
            $table->decimal('pontos_hora', 8, 2)->nullable();
            $table->decimal('pontos_questao', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
