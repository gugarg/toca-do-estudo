<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cache_estatisticas_usuario');
    }

    public function down(): void
    {
        // Tabela descontinuada - cálculos 100% dinâmicos em 3FN
    }
};
