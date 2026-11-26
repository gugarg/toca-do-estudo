<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessaoEstudoResource;
use App\Models\SessaoEstudo;
use App\Services\AgregadorMetricasService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainelController extends Controller
{
    public function __construct(
        private readonly AgregadorMetricasService $agregadorMetricas,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $estatisticas = $this->agregadorMetricas->obterEstatisticasUsuario($usuario->id);

        // Consulta os registros dos últimos 365 dias para alimentar o mapa de calor anual de frequência de estudos
        $umAnoAtras = Carbon::now()->subDays(365)->toDateString();

        $dadosMapaCalor = SessaoEstudo::where('usuario_id', $usuario->id)
            ->where('data_estudo', '>=', $umAnoAtras)
            ->selectRaw('data_estudo, SUM(minutos) as total_minutes, SUM(questoes) as total_questions, COUNT(id) as session_count')
            ->groupBy('data_estudo')
            ->orderBy('data_estudo', 'asc')
            ->get();

        $heatmap = $dadosMapaCalor->map(fn($item) => [
            'date' => is_string($item->data_estudo) ? $item->data_estudo : $item->data_estudo->format('Y-m-d'),
            'is_active' => true,
            'minutes' => (int) $item->total_minutes,
            'hours' => round($item->total_minutes / 60.0, 1),
            'questions' => (int) $item->total_questions,
            'session_count' => (int) $item->session_count,
        ]);

        // Agrupa o volume de minutos e questões nas 8 principais matérias para exibir o gráfico de foco
        $distribuicaoDisciplinas = $this->agregadorMetricas->obterDistribuicaoDisciplinas($usuario->id, 8);

        // Carrega as 5 postagens mais recentes do estudante com dados pré-carregados para renderização rápida
        $sessoesRecentes = $usuario->studySessions()
            ->with(['groups', 'kudos', 'user'])
            ->orderBy('data_estudo', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'avatar_url' => $usuario->avatar_url ? url("storage/{$usuario->avatar_url}") : null,
                ],
                'summary' => [
                    'total_active_days' => $estatisticas->total_active_days,
                    'total_hours' => $estatisticas->total_hours,
                    'total_minutes' => $estatisticas->total_minutes,
                    'total_questions' => $estatisticas->total_questions,
                    'groups_count' => $usuario->groups()->count(),
                ],
                'heatmap' => $heatmap,
                'subjects' => $distribuicaoDisciplinas,
                'recent_sessions' => SessaoEstudoResource::collection($sessoesRecentes),
            ],
        ]);
    }
}
