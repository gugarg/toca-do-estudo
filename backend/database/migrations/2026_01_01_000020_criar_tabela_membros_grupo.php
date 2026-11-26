<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membros_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('papel', 20)->default('membro');
            $table->timestamp('ingressou_em')->useCurrent();

            $table->unique(['grupo_id', 'usuario_id']);
            $table->index(['grupo_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membros_grupo');
    }
};
