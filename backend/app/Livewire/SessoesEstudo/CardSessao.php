<?php

declare(strict_types=1);

namespace App\Livewire\SessoesEstudo;

use App\Models\Comentario;
use App\Models\Grupo;
use App\Models\Reacao;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\SessaoEstudoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CardSessao extends Component
{
    public SessaoEstudo $session;
    public ?int $groupId = null;

    public ?string $userReaction = null;
    public array $reactionsBreakdown = [];
    public int $reactionsCount = 0;
    public bool $showReactionPicker = false;

    public bool $showComments = false;
    public int $commentsCount = 0;

    public string $newCommentText = '';
    public ?string $commentError = null;

    public ?int $editingCommentId = null;
    public string $editingCommentText = '';
    public ?string $editCommentError = null;

    public ?int $deletingCommentId = null;

    public const PREDEFINED_REACTIONS = [
        'fire' => ['emoji' => '🔥', 'label' => 'Foco', 'desc' => 'Foco total nos estudos'],
        'books' => ['emoji' => '📚', 'label' => 'Dedicado', 'desc' => 'Estudando com dedicação'],
        'applause' => ['emoji' => '👏', 'label' => 'Mandou bem', 'desc' => 'Parabéns pela consistência'],
        'target' => ['emoji' => '🎯', 'label' => 'No alvo', 'desc' => 'Meta de estudos batida'],
        'brain' => ['emoji' => '🧠', 'label' => 'Genial', 'desc' => 'Domínio do conteúdo'],
    ];

    public function mount(SessaoEstudo $session, ?int $groupId = null): void
    {
        $this->session = $session;
        $this->groupId = $groupId;
        $this->syncReactionsData();
        $this->syncCommentsCount();
    }

    protected function syncReactionsData(): void
    {
        if ($this->session->relationLoaded('reacoes')) {
            $kudos = $this->groupId !== null
                ? $this->session->reacoes->where('grupo_id', $this->groupId)->values()
                : $this->session->reacoes;
        } else {
            $query = $this->session->reacoes();
            if ($this->groupId !== null) {
                $query->where('grupo_id', $this->groupId);
            }
            $kudos = $query->get();
        }

        $this->reactionsCount = $kudos->count();
        $this->reactionsBreakdown = $kudos->groupBy('tipo_reacao')->map->count()->toArray();

        /** @var Usuario|null $user */
        $user = Auth::user();
        if ($user && $this->groupId !== null) {
            $myReaction = $kudos->firstWhere('usuario_id', $user->id);
            $this->userReaction = $myReaction ? $myReaction->tipo_reacao : null;
        } else {
            $this->userReaction = null;
        }
    }

    protected function syncCommentsCount(): void
    {
        if (isset($this->session->comentarios_count)) {
            $this->commentsCount = (int) $this->session->comentarios_count;
            return;
        }

        if ($this->session->relationLoaded('comentarios')) {
            $this->commentsCount = $this->groupId !== null
                ? $this->session->comentarios->where('grupo_id', $this->groupId)->count()
                : $this->session->comentarios->count();
            return;
        }

        $query = $this->session->comentarios();
        if ($this->groupId !== null) {
            $query->where('grupo_id', $this->groupId);
        }
        $this->commentsCount = $query->count();
    }

    protected function autorizarInteracaoNoGrupo(Usuario $user): Grupo
    {
        if ($this->groupId === null) {
            abort(403, 'Reações e comentários devem ser realizados dentro de uma comunidade.');
        }

        $grupo = Grupo::find($this->groupId);
        if (!$grupo) {
            abort(404, 'Comunidade não encontrada.');
        }

        Gate::authorize('interagirNoGrupo', [$this->session, $grupo]);

        return $grupo;
    }

    public function toggleReactionPicker(): void
    {
        if ($this->groupId === null) {
            return;
        }

        $this->showReactionPicker = !$this->showReactionPicker;
    }

    public function toggleReaction(string $reactionType): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if (!array_key_exists($reactionType, self::PREDEFINED_REACTIONS)) {
            return;
        }

        $grupo = $this->autorizarInteracaoNoGrupo($user);

        if ($this->userReaction === $reactionType) {
            Reacao::where('sessao_estudo_id', $this->session->id)
                ->where('usuario_id', $user->id)
                ->where('grupo_id', $grupo->id)
                ->delete();
            $this->userReaction = null;
        } else {
            Reacao::updateOrCreate(
                [
                    'sessao_estudo_id' => $this->session->id,
                    'usuario_id' => $user->id,
                    'grupo_id' => $grupo->id,
                ],
                [
                    'tipo_reacao' => $reactionType,
                    'created_at' => now(),
                ]
            );
            $this->userReaction = $reactionType;
        }

        $this->showReactionPicker = false;
        $this->session->setRelation(
            'reacoes',
            $this->session->reacoes()->where('grupo_id', $grupo->id)->get()
        );
        $this->syncReactionsData();
    }

    public function toggleComments(): void
    {
        $this->showComments = !$this->showComments;
        $this->commentError = null;
        $this->editingCommentId = null;
        $this->deletingCommentId = null;
    }

    public function addComment(): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if ($this->groupId === null) {
            $this->commentError = 'Comentários devem ser publicados dentro do mural de uma comunidade.';
            return;
        }

        $grupo = $this->autorizarInteracaoNoGrupo($user);

        $trimmed = trim($this->newCommentText);
        if ($trimmed === '') {
            $this->commentError = 'O comentário não pode ser vazio.';
            return;
        }

        if (mb_strlen($trimmed) > 1000) {
            $this->commentError = 'O comentário não pode ultrapassar 1.000 caracteres.';
            return;
        }

        $this->session->comentarios()->create([
            'grupo_id' => $grupo->id,
            'usuario_id' => $user->id,
            'conteudo' => $trimmed,
        ]);

        $this->newCommentText = '';
        $this->commentError = null;
        $this->commentsCount = $this->session->comentarios()
            ->where('grupo_id', $grupo->id)
            ->count();
    }

    public function startEditComment(int $commentId): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        $comment = Comentario::find($commentId);

        if (!$comment || !$user || (int) $comment->usuario_id !== (int) $user->id) {
            return;
        }

        if ($this->groupId !== null && (int) $comment->grupo_id !== (int) $this->groupId) {
            return;
        }

        $this->editingCommentId = $commentId;
        $this->editingCommentText = (string) $comment->conteudo;
        $this->editCommentError = null;
    }

    public function cancelEditComment(): void
    {
        $this->editingCommentId = null;
        $this->editingCommentText = '';
        $this->editCommentError = null;
    }

    public function saveEditComment(): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user || !$this->editingCommentId) {
            return;
        }

        $comment = Comentario::find($this->editingCommentId);
        if (
            !$comment
            || (int) $comment->usuario_id !== (int) $user->id
            || ($this->groupId !== null && (int) $comment->grupo_id !== (int) $this->groupId)
        ) {
            abort(403, 'Você não tem permissão para editar este comentário.');
        }

        $trimmed = trim($this->editingCommentText);
        if ($trimmed === '') {
            $this->editCommentError = 'O comentário não pode ser vazio.';
            return;
        }

        if (mb_strlen($trimmed) > 1000) {
            $this->editCommentError = 'O comentário não pode ultrapassar 1.000 caracteres.';
            return;
        }

        $comment->update([
            'conteudo' => $trimmed,
        ]);

        $this->cancelEditComment();
    }

    public function confirmDeleteComment(int $commentId): void
    {
        $this->deletingCommentId = $commentId;
    }

    public function cancelDeleteComment(): void
    {
        $this->deletingCommentId = null;
    }

    public function deleteComment(int $commentId): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $comment = Comentario::find($commentId);
        if (
            !$comment
            || (int) $comment->usuario_id !== (int) $user->id
            || ($this->groupId !== null && (int) $comment->grupo_id !== (int) $this->groupId)
        ) {
            abort(403, 'Você não tem permissão para excluir este comentário.');
        }

        $comment->delete();
        $this->deletingCommentId = null;

        $query = $this->session->comentarios();
        if ($this->groupId !== null) {
            $query->where('grupo_id', $this->groupId);
        }
        $this->commentsCount = $query->count();
    }

    public function deleteSession(SessaoEstudoService $sessaoService): void
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user || $this->session->usuario_id !== $user->id) {
            abort(403, 'Você não tem permissão para excluir esta sessão de estudo.');
        }

        $sessaoService->excluirSessao($user, $this->session);
        $this->dispatch('session-deleted', sessionId: $this->session->id);
    }

    public function render()
    {
        if ($this->showComments) {
            $query = $this->session->comentarios()->orderBy('created_at', 'asc');
            if ($this->groupId !== null) {
                $query->where('grupo_id', $this->groupId)->with('usuario');
            } else {
                $query->with(['usuario', 'grupo']);
            }
            $comments = $query->get();
        } else {
            $comments = collect();
        }

        $studyDateObj = $this->session->data_estudo;
        $isToday = $studyDateObj ? Carbon::parse($studyDateObj)->isToday() : false;

        return view('livewire.sessoes-estudo.card-sessao', [
            'comments' => $comments,
            'isToday' => $isToday,
            'canInteract' => $this->groupId !== null,
            'predefinedReactions' => self::PREDEFINED_REACTIONS,
        ]);
    }
}
