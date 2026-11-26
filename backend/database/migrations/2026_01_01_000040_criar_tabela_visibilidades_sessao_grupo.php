<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('visibilidades_sessao_grupo');
    }

    public function down(): void
    {
        Schema::dropIfExists('visibilidades_sessao_grupo');
    }
};
