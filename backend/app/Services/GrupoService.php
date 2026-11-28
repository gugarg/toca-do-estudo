<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Grupo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GrupoService
{
    /**
     * Cria um novo grupo de estudos com código de convite único.
     * Suporta exclusivamente duas modalidades: 'dias_ativos' e 'horas_questoes'.
     */
    public function criarGrupo(Usuario $owner, array $data): Grupo
    {
        $rawMetric = $data['metrica_padrao'] ?? $data['default_metric'] ?? 'dias_ativos';

        $metric = match ($rawMetric) {
            'dias_ativos', 'active_days' => Grupo::MODALIDADE_DIAS_ATIVOS,
            'horas_questoes', 'hours_and_questions', 'pontos', 'points' => Grupo::MODALIDADE_HORAS_QUESTOES,
            default => throw new InvalidArgumentException("Modalidade de competição inválida. São permitidas exclusivamente: 'dias_ativos' e 'horas_questoes'."),
        };

        $pontosHora = null;
        $pontosQuestao = null;

        if ($metric === Grupo::MODALIDADE_HORAS_QUESTOES) {
            $rawPontosHora = $data['pontos_hora'] ?? $data['points_per_hour'] ?? $data['weight_hours'] ?? null;
            $rawPontosQuestao = $data['pontos_questao'] ?? $data['points_per_question'] ?? $data['weight_questions'] ?? null;

            if ($rawPontosHora === null || $rawPontosHora === '' || !is_numeric($rawPontosHora) || (float) $rawPontosHora <= 0) {
                throw new InvalidArgumentException("Para a modalidade 'Horas líquidas + questões', os pontos por hora líquida são obrigatórios e devem ser maiores que zero.");
            }

            if ($rawPontosQuestao === null || $rawPontosQuestao === '' || !is_numeric($rawPontosQuestao) || (float) $rawPontosQuestao <= 0) {
                throw new InvalidArgumentException("Para a modalidade 'Horas líquidas + questões', os pontos por questão resolvida são obrigatórios e devem ser maiores que zero.");
            }

            $pontosHora = round((float) $rawPontosHora, 2);
            $pontosQuestao = round((float) $rawPontosQuestao, 2);
        }

        return DB::transaction(function () use ($owner, $data, $metric, $pontosHora, $pontosQuestao) {
            // Gera um código alfanumérico curto e amigável (formato TOCA-XXXXXX) para compartilhamento rápido entre colegas
            $inviteCode = $this->gerarCodigoConviteUnico();

            $group = Grupo::create([
                'proprietario_id' => $owner->id,
                'nome' => $data['nome'] ?? $data['name'],
                'descricao' => $data['descricao'] ?? $data['description'] ?? null,
                'codigo_convite' => $inviteCode,
                'metrica_padrao' => $metric,
                'pontos_hora' => $pontosHora,
                'pontos_questao' => $pontosQuestao,
            ]);

            // Cadastra o criador com papel de proprietário na comunidade
            $group->membros()->attach($owner->id, [
                'papel' => 'proprietario',
                'ingressou_em' => now(),
            ]);

            return $group->load(['proprietario', 'membros']);
        });
    }

    /**
     * Atualiza dados descritivos do grupo, impedindo qualquer alteração na modalidade ou pesos (imutabilidade).
     */
    public function atualizarGrupo(Usuario $user, Grupo $group, array $data): Grupo
    {
        if ($group->proprietario_id !== $user->id) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException("Apenas o proprietário pode editar as informações do grupo.");
        }

        // Rejeita tentativas de alterar métrica ou pesos
        if (
            array_key_exists('metrica_padrao', $data) ||
            array_key_exists('default_metric', $data) ||
            array_key_exists('pontos_hora', $data) ||
            array_key_exists('points_per_hour', $data) ||
            array_key_exists('weight_hours', $data) ||
            array_key_exists('pontos_questao', $data) ||
            array_key_exists('points_per_question', $data) ||
            array_key_exists('weight_questions', $data)
        ) {
            throw new InvalidArgumentException("Os pesos e a modalidade de competição do grupo são imutáveis após a criação.");
        }

        if (isset($data['nome']) || isset($data['name'])) {
            $group->nome = $data['nome'] ?? $data['name'];
        }

        if (array_key_exists('descricao', $data) || array_key_exists('description', $data)) {
            $group->descricao = $data['descricao'] ?? $data['description'];
        }

        $group->save();

        return $group->fresh(['proprietario', 'membros']);
    }

    /**
     * Permite a um estudante ingressar em um grupo através do código alfanumérico de convite.
     */
    public function entrarGrupoPorCodigo(Usuario $user, string $inviteCode): Grupo
    {
        $code = strtoupper(trim($inviteCode));
        $group = Grupo::where('codigo_convite', $code)->first();

        if (!$group) {
            throw new NotFoundHttpException("Nenhum grupo encontrado com o código informado.");
        }

        if ($group->membros()->where('usuarios.id', $user->id)->exists()) {
            throw new ConflictHttpException("Você já é membro deste grupo de estudos.");
        }

        $group->membros()->attach($user->id, [
            'papel' => 'membro',
            'ingressou_em' => now(),
        ]);

        return $group->load('membros');
    }

    /**
     * Permite a um estudante deixar voluntariamente um grupo de estudos.
     */
    public function sairGrupo(Usuario $user, Grupo $group): void
    {
        $membership = $group->membros()->where('usuarios.id', $user->id)->first();

        if (!$membership) {
            throw new NotFoundHttpException("Você não faz parte deste grupo de estudos.");
        }

        if (in_array($membership->pivot->papel, ['proprietario', 'owner'], true)) {
            throw new InvalidArgumentException("Você não pode sair desta comunidade porque é o proprietário, mesmo que seja o único integrante. A transferência de titularidade e a exclusão da comunidade ainda não estão disponíveis.");
        }

        $group->membros()->detach($user->id);
    }

    private function gerarCodigoConviteUnico(): string
    {
        do {
            $code = 'TOCA-' . strtoupper(Str::random(6));
        } while (Grupo::where('codigo_convite', $code)->exists());

        return $code;
    }
}
