<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SessaoEstudoPolicy
{
    private function extrairDataEstudo(SessaoEstudo $sessao): string
    {
        $data = $sessao->data_estudo;

        if ($data instanceof CarbonInterface) {
            return $data->toDateString();
        }

        return Carbon::parse((string) $data)->toDateString();
    }

    public function view(Usuario $usuario, SessaoEstudo $sessao): bool
    {
        if ((int) $sessao->usuario_id === (int) $usuario->id) {
            return true;
        }

        if (!$sessao->compartilhar) {
            return false;
        }

        $dataEstudo = $this->extrairDataEstudo($sessao);

        return DB::table('membros_grupo as autor')
            ->join('membros_grupo as leitor', 'leitor.grupo_id', '=', 'autor.grupo_id')
            ->where('autor.usuario_id', $sessao->usuario_id)
            ->where('leitor.usuario_id', $usuario->id)
            ->whereRaw('DATE(autor.ingressou_em) <= ?', [$dataEstudo])
            ->exists();
    }

    /**
     * Garante que reações e comentários só ocorram em uma comunidade da qual
     * tanto o autor da sessão quanto o usuário que interage sejam membros,
     * com compartilhamento habilitado e data de estudo posterior/igual à entrada do autor no grupo.
     */
    public function interagirNoGrupo(Usuario $usuario, SessaoEstudo $sessao, Grupo $grupo): bool
    {
        if (!$sessao->compartilhar) {
            return false;
        }

        $dataEstudo = $this->extrairDataEstudo($sessao);

        $autorNoGrupo = DB::table('membros_grupo')
            ->where('grupo_id', $grupo->id)
            ->where('usuario_id', $sessao->usuario_id)
            ->whereRaw('DATE(ingressou_em) <= ?', [$dataEstudo])
            ->exists();

        if (!$autorNoGrupo) {
            return false;
        }

        if ((int) $sessao->usuario_id === (int) $usuario->id) {
            return true;
        }

        return DB::table('membros_grupo')
            ->where('grupo_id', $grupo->id)
            ->where('usuario_id', $usuario->id)
            ->exists();
    }
}
