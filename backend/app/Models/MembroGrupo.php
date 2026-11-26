<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MembroGrupo extends Pivot
{
    protected $table = 'membros_grupo';

    public $timestamps = false;

    protected $fillable = [
        'grupo_id',
        'usuario_id',
        'papel',
        'ingressou_em',
    ];
}
