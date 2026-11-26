<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarSessaoEstudoRequest;
use App\Http\Resources\SessaoEstudoResource;
use App\Models\Grupo;
use App\Models\Reacao;
use App\Models\SessaoEstudo;
use App\Services\SessaoEstudoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SessaoEstudoController extends Controller
{
    public function __construct(
        private readonly SessaoEstudoService $sessaoService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sessoes = $request->user()
            ->studySessions()
            ->with(['groups', 'kudos', 'user'])
            ->withCount('comments')
            ->orderBy('data_estudo', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return SessaoEstudoResource::collection($sessoes);
    }

    public function store(RegistrarSessaoEstudoRequest $request): JsonResponse
    {
        $sessao = $this->sessaoService->criarSessao(
            $request->user(),
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Sessão de estudo registrada com sucesso.',
            'data' => new SessaoEstudoResource($sessao),
        ], 201);
    }

    public function show(Request $request, SessaoEstudo $studySession): JsonResponse
    {
        Gate::authorize('view', $studySession);

        $user = $request->user();
        if ((int) $studySession->usuario_id === (int) $user->id) {
            $studySession->load(['groups', 'kudos.user', 'user']);
            $studySession->loadCount('comments');
        } else {
            $gruposDoLeitor = $user->grupos()->pluck('grupos.id')->all();
            $studySession->load([
                'groups',
                'kudos' => fn ($q) => $q->whereIn('grupo_id', $gruposDoLeitor)->with('user'),
                'user',
            ]);
            $studySession->loadCount([
                'comments' => fn ($q) => $q->whereIn('grupo_id', $gruposDoLeitor),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new SessaoEstudoResource($studySession),
        ]);
    }

    public function destroy(Request $request, SessaoEstudo $studySession): JsonResponse
    {
        $this->sessaoService->excluirSessao($request->user(), $studySession);

        return response()->json([
            'success' => true,
            'message' => 'Sessão de estudo excluída com sucesso.',
        ], 204);
    }

    public function addKudo(Request $request, SessaoEstudo $studySession): JsonResponse
    {
        $grupo = $this->resolverGrupoDoRequest($request);
        return $this->registrarReacao($request, $studySession, $grupo);
    }

    public function addKudoInGroup(Request $request, Grupo $group, SessaoEstudo $studySession): JsonResponse
    {
        return $this->registrarReacao($request, $studySession, $group);
    }

    public function removeKudo(Request $request, SessaoEstudo $studySession): JsonResponse
    {
        $grupo = $this->resolverGrupoDoRequest($request);
        return $this->removerReacao($request, $studySession, $grupo);
    }

    public function removeKudoInGroup(Request $request, Grupo $group, SessaoEstudo $studySession): JsonResponse
    {
        return $this->removerReacao($request, $studySession, $group);
    }

    private function resolverGrupoDoRequest(Request $request): Grupo
    {
        $groupIdInput = $request->input('group_id')
            ?? $request->input('grupo_id')
            ?? $request->query('group_id')
            ?? $request->query('grupo_id');

        $request->merge(['group_id' => $groupIdInput]);

        $request->validate([
            'group_id' => ['required', 'integer', 'exists:grupos,id'],
        ], [
            'group_id.required' => 'Informe a comunidade em que a reação está sendo registrada.',
            'group_id.exists' => 'A comunidade informada não foi encontrada.',
        ]);

        return Grupo::findOrFail((int) $groupIdInput);
    }

    private function registrarReacao(Request $request, SessaoEstudo $studySession, Grupo $grupo): JsonResponse
    {
        Gate::authorize('interagirNoGrupo', [$studySession, $grupo]);

        $permitidos = implode(',', Reacao::ALLOWED_REACTIONS);
        $dadosValidados = $request->validate([
            'reaction_type' => ['nullable', 'string', "in:{$permitidos}"],
        ], [
            'reaction_type.in' => 'Tipo de reação inválido.',
        ]);

        $tipoReacao = $dadosValidados['reaction_type'] ?? 'fire';

        $kudo = Reacao::updateOrCreate(
            [
                'sessao_estudo_id' => $studySession->id,
                'usuario_id' => $request->user()->id,
                'grupo_id' => $grupo->id,
            ],
            [
                'tipo_reacao' => $tipoReacao,
                'created_at' => now(),
            ]
        );

        $reacoes = $studySession->kudos()->where('grupo_id', $grupo->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Reação registrada com sucesso!',
            'data' => [
                'group_id' => $grupo->id,
                'reaction_type' => $kudo->reaction_type,
                'user_reaction' => $kudo->reaction_type,
                'reactions_count' => $reacoes->count(),
                'reactions_breakdown' => $reacoes->groupBy('reaction_type')->map->count(),
                'kudos_count' => $reacoes->count(),
            ],
        ], 201);
    }

    private function removerReacao(Request $request, SessaoEstudo $studySession, Grupo $grupo): JsonResponse
    {
        Gate::authorize('interagirNoGrupo', [$studySession, $grupo]);

        Reacao::where('sessao_estudo_id', $studySession->id)
            ->where('usuario_id', $request->user()->id)
            ->where('grupo_id', $grupo->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reação removida com sucesso.',
        ], 204);
    }
}
