<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Grupo;
use App\Models\SessaoEstudo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ComentarioController extends Controller
{
    public function index(Request $request, SessaoEstudo $studySession): AnonymousResourceCollection
    {
        $groupId = $request->query('group_id') ?? $request->query('grupo_id');
        if ($groupId !== null && $groupId !== '') {
            $grupo = Grupo::findOrFail((int) $groupId);
            return $this->listarComentarios($request, $studySession, $grupo);
        }

        Gate::authorize('view', $studySession);

        $user = $request->user();
        $query = $studySession->comments()->with(['user', 'grupo'])->orderBy('created_at', 'asc');

        // Se não for o autor da sessão, limita aos grupos que o leitor também integra
        if ((int) $studySession->usuario_id !== (int) $user->id) {
            $gruposDoLeitor = $user->grupos()->pluck('grupos.id')->all();
            $query->whereIn('grupo_id', $gruposDoLeitor);
        }

        return ComentarioResource::collection($query->get());
    }

    public function indexInGroup(Request $request, Grupo $group, SessaoEstudo $studySession): AnonymousResourceCollection
    {
        return $this->listarComentarios($request, $studySession, $group);
    }

    private function listarComentarios(Request $request, SessaoEstudo $studySession, Grupo $grupo): AnonymousResourceCollection
    {
        Gate::authorize('interagirNoGrupo', [$studySession, $grupo]);

        $comentarios = $studySession->comments()
            ->where('grupo_id', $grupo->id)
            ->with(['user', 'grupo'])
            ->orderBy('created_at', 'asc')
            ->get();

        return ComentarioResource::collection($comentarios);
    }

    public function store(Request $request, SessaoEstudo $studySession): JsonResponse
    {
        $groupIdInput = $request->input('group_id') ?? $request->input('grupo_id');
        $request->merge(['group_id' => $groupIdInput]);

        $request->validate([
            'group_id' => ['required', 'integer', 'exists:grupos,id'],
        ], [
            'group_id.required' => 'Informe a comunidade em que o comentário está sendo publicado.',
            'group_id.exists' => 'A comunidade informada não foi encontrada.',
        ]);

        $grupo = Grupo::findOrFail((int) $groupIdInput);

        return $this->criarComentario($request, $studySession, $grupo);
    }

    public function storeInGroup(Request $request, Grupo $group, SessaoEstudo $studySession): JsonResponse
    {
        return $this->criarComentario($request, $studySession, $group);
    }

    private function criarComentario(Request $request, SessaoEstudo $studySession, Grupo $grupo): JsonResponse
    {
        Gate::authorize('interagirNoGrupo', [$studySession, $grupo]);

        $conteudoBruto = $request->input('content') ?? $request->input('comment');
        $request->merge(['content' => $conteudoBruto]);

        $dadosValidados = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:1000'],
        ], [
            'content.required' => 'O comentário é obrigatório.',
            'content.min' => 'O comentário deve conter pelo menos 1 caractere.',
            'content.max' => 'O comentário não pode ultrapassar 1000 caracteres.',
        ]);

        if (trim($dadosValidados['content']) === '') {
            throw ValidationException::withMessages([
                'content' => 'O comentário não pode ser vazio.',
            ]);
        }

        $comentario = $studySession->comments()->create([
            'grupo_id' => $grupo->id,
            'usuario_id' => $request->user()->id,
            'conteudo' => trim($dadosValidados['content']),
        ]);

        $comentario->load(['user', 'grupo']);

        return response()->json([
            'success' => true,
            'message' => 'Comentário adicionado com sucesso.',
            'data' => new ComentarioResource($comentario),
            'comments_count' => $studySession->comments()->where('grupo_id', $grupo->id)->count(),
        ], 201);
    }

    public function update(Request $request, Comentario $comment): JsonResponse
    {
        if ((int) $comment->user_id !== (int) $request->user()->id) {
            abort(403, 'Você não tem permissão para editar este comentário.');
        }

        $conteudoBruto = $request->input('content') ?? $request->input('comment');
        $request->merge(['content' => $conteudoBruto]);

        $dadosValidados = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:1000'],
        ], [
            'content.required' => 'O comentário é obrigatório.',
            'content.min' => 'O comentário deve conter pelo menos 1 caractere.',
            'content.max' => 'O comentário não pode ultrapassar 1000 caracteres.',
        ]);

        if (trim($dadosValidados['content']) === '') {
            throw ValidationException::withMessages([
                'content' => 'O comentário não pode ser vazio.',
            ]);
        }

        $comment->update([
            'content' => trim($dadosValidados['content']),
        ]);

        $comment->load(['user', 'grupo']);

        return response()->json([
            'success' => true,
            'message' => 'Comentário atualizado com sucesso.',
            'data' => new ComentarioResource($comment),
        ]);
    }

    public function destroy(Request $request, Comentario $comment): JsonResponse
    {
        if ((int) $comment->user_id !== (int) $request->user()->id) {
            abort(403, 'Você não tem permissão para excluir este comentário.');
        }

        $sessaoEstudo = $comment->studySession;
        $grupoId = $comment->grupo_id;
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comentário excluído com sucesso.',
            'comments_count' => $sessaoEstudo
                ? $sessaoEstudo->comments()->where('grupo_id', $grupoId)->count()
                : 0,
        ]);
    }
}
