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

    public function getRoleAttribute(): ?string
    {
        return $this->papel;
    }

    public function setRoleAttribute(?string $value): void
    {
        $this->attributes['papel'] = $value;
    }

    public function getJoinedAtAttribute(): mixed
    {
        return $this->ingressou_em;
    }

    public function setJoinedAtAttribute(mixed $value): void
    {
        $this->attributes['ingressou_em'] = $value;
    }
}
