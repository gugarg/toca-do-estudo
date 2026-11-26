<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Grupo;
use App\Models\Usuario;

class GrupoPolicy
{
    public function view(Usuario $usuario, Grupo $grupo): bool
    {
        return $grupo->membros()->where('usuarios.id', $usuario->id)->exists();
    }
}
