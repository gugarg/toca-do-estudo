<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CriarGrupoRequest;
use App\Http\Resources\GrupoResource;
use App\Http\Resources\SessaoEstudoResource;
use App\Models\Grupo;
use App\Services\GrupoService;
use App\Services\AgregadorMetricasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GrupoController extends Controller
{
    public function __construct(
        private readonly GrupoService $grupoService,
        private readonly AgregadorMetricasService $agregadorMetricas,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $grupos = $request->user()
            ->groups()
            ->with(['owner', 'members'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return GrupoResource::collection($grupos);
    }

    public function store(CriarGrupoRequest $request): JsonResponse
    {
        $grupo = $this->grupoService->criarGrupo(
            $request->user(),
            $request->validated(),
            $request->file('cover_photo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Grupo de estudos criado com sucesso!',
            'data' => new GrupoResource($grupo),
        ], 201);
    }

    public function show(Request $request, Grupo $group): JsonResponse
    {
        Gate::authorize('view', $group);
        $group->load(['owner', 'members']);

        return response()->json([
            'success' => true,
            'data' => new GrupoResource($group),
        ]);
    }

    /**
     * Atualização de dados descritivos do grupo.
     * Rejeita estritamente qualquer tentativa de alterar a modalidade ou pesos (imutabilidade).
     */
    public function update(Request $request, Grupo $group): JsonResponse
    {
        if ($group->proprietario_id !== $request->user()->id) {
            abort(403, 'Apenas o proprietário pode editar as informações do grupo.');
        }

        // Rejeita tentativas de alterar métrica ou pesos com erros específicos por campo
        $errosImutabilidade = [];
        if ($request->has('metrica_padrao') || $request->has('default_metric')) {
            $errosImutabilidade['metrica_padrao'] = ['A modalidade de competição do grupo é imutável após a criação.'];
        }
        if ($request->has('pontos_hora') || $request->has('points_per_hour') || $request->has('weight_hours')) {
            $errosImutabilidade['pontos_hora'] = ['Os pontos por hora líquida são imutáveis após a criação.'];
        }
        if ($request->has('pontos_questao') || $request->has('points_per_question') || $request->has('weight_questions')) {
            $errosImutabilidade['pontos_questao'] = ['Os pontos por questão resolvida são imutáveis após a criação.'];
        }

        if (!empty($errosImutabilidade)) {
            return response()->json([
                'success' => false,
                'message' => 'Os pesos e a modalidade de competição do grupo são imutáveis após a criação.',
                'errors' => $errosImutabilidade,
            ], 422);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'nome' => ['sometimes', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'descricao' => ['nullable', 'string', 'max:500'],
        ]);

        $grupoAtualizado = $this->grupoService->atualizarGrupo($request->user(), $group, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Grupo atualizado com sucesso.',
            'data' => new GrupoResource($grupoAtualizado),
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'invite_code' => ['required', 'string', 'max:12'],
        ]);

        $grupo = $this->grupoService->entrarGrupoPorCodigo(
            $request->user(),
            $request->input('invite_code')
        );

        return response()->json([
            'success' => true,
            'message' => "Você agora é membro do grupo {$grupo->name}!",
            'data' => new GrupoResource($grupo),
        ]);
    }

    public function leave(Request $request, Grupo $group): JsonResponse
    {
        $this->grupoService->sairGrupo($request->user(), $group);

        return response()->json([
            'success' => true,
            'message' => "Você saiu do grupo {$group->name}.",
        ]);
    }

    public function feed(Request $request, Grupo $group): AnonymousResourceCollection
    {
        // Garante que apenas estudantes membros da comunidade consigam acessar as publicações e fotos do mural
        if (!$group->members()->where('usuarios.id', $request->user()->id)->exists()) {
            abort(403, 'Você não tem acesso ao mural deste grupo.');
        }

        $sessoes = $group->sessions()
            ->with([
                'user',
                'kudos' => fn ($q) => $q->where('grupo_id', $group->id),
                'groups',
            ])
            ->withCount([
                'comments' => fn ($q) => $q->where('grupo_id', $group->id),
            ])
            ->orderBy('data_estudo', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return SessaoEstudoResource::collection($sessoes);
    }

    public function leaderboard(Request $request, Grupo $group): JsonResponse
    {
        // Mantém a privacidade: o ranking interno só é visível para quem já faz parte do grupo
        if (!$group->members()->where('usuarios.id', $request->user()->id)->exists()) {
            abort(403, 'Você não tem acesso à classificação deste grupo.');
        }

        $metrica = $request->query('metric', $group->default_metric ?: 'dias_ativos');
        $periodo = $request->query('period', 'weekly');

        $metricaNormalizada = match ($metrica) {
            'horas_questoes', 'hours_and_questions', 'pontos', 'points' => Grupo::MODALIDADE_HORAS_QUESTOES,
            'dias_ativos', 'active_days' => Grupo::MODALIDADE_DIAS_ATIVOS,
            default => (in_array($group->metrica_padrao, [Grupo::MODALIDADE_HORAS_QUESTOES, 'hours_and_questions', 'pontos', 'points'], true)
                ? Grupo::MODALIDADE_HORAS_QUESTOES
                : Grupo::MODALIDADE_DIAS_ATIVOS),
        };

        if (!in_array($periodo, ['weekly', 'monthly', 'all_time'], true)) {
            $periodo = 'weekly';
        }

        $classificacoes = $this->agregadorMetricas->obterClassificacaoGrupo($group, $metricaNormalizada, $periodo);

        return response()->json([
            'success' => true,
            'data' => [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'metric_applied' => $metricaNormalizada,
                'points_per_hour' => $group->pontos_hora,
                'points_per_question' => $group->pontos_questao,
                'period_applied' => $periodo,
                'rankings' => $classificacoes,
            ],
        ]);
    }
}
