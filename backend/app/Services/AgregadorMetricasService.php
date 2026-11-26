<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\EstatisticasUsuarioDTO;
use App\Models\Grupo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgregadorMetricasService
{
    /**
     * Consulta e processa dinamicamente as métricas analíticas do estudante
     * em tempo real (3FN estrita), sem necessidade de tabela física de cache.
     */
    public function obterEstatisticasUsuario(int $userId): EstatisticasUsuarioDTO
    {
        $agregados = DB::table('sessoes_estudo')
            ->where('usuario_id', $userId)
            ->selectRaw('
                COUNT(DISTINCT data_estudo) as total_dias_ativos,
                COALESCE(SUM(minutos), 0) as total_minutos,
                COALESCE(SUM(questoes), 0) as total_questoes
            ')
            ->first();

        return new EstatisticasUsuarioDTO(
            usuario_id: $userId,
            total_dias_ativos: (int) ($agregados->total_dias_ativos ?? 0),
            total_minutos: (int) ($agregados->total_minutos ?? 0),
            total_questoes: (int) ($agregados->total_questoes ?? 0),
            calculado_em: now(),
        );
    }

    // Monta a classificação dos membros de um grupo com uma única consulta SQL agregada, filtrando pelo período selecionado.
    public function obterClassificacaoGrupo(Grupo $group, string $metric = 'dias_ativos', string $period = 'semanal'): Collection
    {
        $filtroData = match ($period) {
            'weekly', 'semanal' => Carbon::now()->startOfWeek()->toDateString(),
            'monthly', 'mensal' => Carbon::now()->startOfMonth()->toDateString(),
            default => null,
        };

        $linhas = DB::table('membros_grupo')
            ->join('usuarios', 'usuarios.id', '=', 'membros_grupo.usuario_id')
            ->leftJoin('sessoes_estudo', function ($join) use ($filtroData) {
                $join->on('sessoes_estudo.usuario_id', '=', 'usuarios.id')
                    ->where('sessoes_estudo.compartilhar', '=', true)
                    ->whereRaw('sessoes_estudo.data_estudo >= DATE(membros_grupo.ingressou_em)');
                if ($filtroData !== null) {
                    $join->where('sessoes_estudo.data_estudo', '>=', $filtroData);
                }
            })
            ->where('membros_grupo.grupo_id', $group->id)
            ->groupBy('usuarios.id', 'usuarios.nome', 'usuarios.url_avatar')
            ->selectRaw('
                usuarios.id as user_id,
                usuarios.nome as name,
                usuarios.url_avatar as avatar_url,
                COUNT(DISTINCT sessoes_estudo.data_estudo) as active_days_in_period,
                COALESCE(SUM(sessoes_estudo.minutos), 0) as total_minutes,
                COALESCE(SUM(sessoes_estudo.questoes), 0) as questions_in_period
            ')
            ->get();

        // Normaliza a modalidade de avaliação (suporta exclusivamente as duas modalidades oficiais)
        $modalidadeAlvo = match ($metric) {
            'horas_questoes', 'hours_and_questions', 'pontos', 'points' => Grupo::MODALIDADE_HORAS_QUESTOES,
            'dias_ativos', 'active_days' => Grupo::MODALIDADE_DIAS_ATIVOS,
            default => (in_array($group->metrica_padrao, [Grupo::MODALIDADE_HORAS_QUESTOES, 'hours_and_questions', 'pontos', 'points'], true)
                ? Grupo::MODALIDADE_HORAS_QUESTOES
                : Grupo::MODALIDADE_DIAS_ATIVOS),
        };

        $pesoHoras = (float) ($group->pontos_hora ?? 10.0);
        $pesoQuestoes = (float) ($group->pontos_questao ?? 1.0);

        $classificacoes = $linhas->map(function ($linha) use ($modalidadeAlvo, $pesoHoras, $pesoQuestoes) {
            $diasAtivos = (int) $linha->active_days_in_period;
            $somaMinutos = (int) $linha->total_minutes;
            $somaQuestoes = (int) $linha->questions_in_period;
            $horasLiquidas = round($somaMinutos / 60.0, 2);
            $horasExibicao = round($somaMinutos / 60.0, 1);

            $pontosHoras = round($horasLiquidas * $pesoHoras, 2);
            $pontosQuestoes = round($somaQuestoes * $pesoQuestoes, 2);
            $pontuacaoTotal = round($pontosHoras + $pontosQuestoes, 2);

            // Formatação amigável do tempo de estudo (ex: '6h', '3h 55m' ou '45m')
            $horasInt = intdiv($somaMinutos, 60);
            $minutosRestantes = $somaMinutos % 60;
            if ($horasInt > 0 && $minutosRestantes > 0) {
                $tempoFormatado = "{$horasInt}h {$minutosRestantes}m";
            } elseif ($horasInt > 0) {
                $tempoFormatado = "{$horasInt}h";
            } else {
                $tempoFormatado = "{$minutosRestantes}m";
            }

            if ($modalidadeAlvo === Grupo::MODALIDADE_HORAS_QUESTOES) {
                $score = $pontuacaoTotal;
                $rotuloMetrica = "{$pontuacaoTotal} pts ({$tempoFormatado} • {$somaQuestoes} q)";
            } else {
                $score = $diasAtivos;
                $rotuloMetrica = $diasAtivos === 1 ? '1 dia ativo' : "{$diasAtivos} dias ativos";
            }

            return [
                'user_id' => (int) $linha->user_id,
                'name' => $linha->name,
                'avatar_url' => $linha->avatar_url ? url("storage/{$linha->avatar_url}") : null,
                'score' => $score,
                'metric_label' => $rotuloMetrica,
                'active_days_in_period' => $diasAtivos,
                'minutes_in_period' => $somaMinutos,
                'hours_in_period' => $horasExibicao,
                'raw_hours' => $horasLiquidas,
                'questions_in_period' => $somaQuestoes,
                'points_from_hours' => $pontosHoras,
                'points_from_questions' => $pontosQuestoes,
                'total_points' => $pontuacaoTotal,
            ];
        });

        // Ordena decrescente pela pontuação com regras consistentes de desempate
        $ordenado = $classificacoes->sort(function ($a, $b) use ($modalidadeAlvo) {
            if ($b['score'] != $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            if ($b['minutes_in_period'] != $a['minutes_in_period']) {
                return $b['minutes_in_period'] <=> $a['minutes_in_period'];
            }
            if ($b['questions_in_period'] != $a['questions_in_period']) {
                return $b['questions_in_period'] <=> $a['questions_in_period'];
            }
            if ($b['active_days_in_period'] != $a['active_days_in_period']) {
                return $b['active_days_in_period'] <=> $a['active_days_in_period'];
            }
            return $a['user_id'] <=> $b['user_id'];
        })->values();

        // Atribui posições (1º, 2º, 3º...)
        return $ordenado->map(function ($item, $indice) {
            $item['position'] = $indice + 1;
            return $item;
        });
    }
}
