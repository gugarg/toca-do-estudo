<?php

declare(strict_types=1);

namespace App\Livewire\Grupos;

use App\Models\Grupo;
use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use App\Services\GrupoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Toca do Estudo')]
class DetalhesGrupo extends Component
{
    use WithPagination;
    public ?int $groupId = null;
    public string $activeTab = 'feed'; // 'feed' | 'leaderboard'
    public string $metric = 'dias_ativos'; // 'dias_ativos' | 'horas' | 'questoes' (aceita também active_days, hours, questions)
    public string $period = 'semanal'; // 'semanal' | 'mensal' | 'geral'

    // Modais
    public bool $showCreateModal = false;
    public bool $showJoinModal = false;
    public bool $showLeaveModal = false;
    public bool $showEditModal = false;

    // Campos de edição da comunidade
    public string $editGroupName = '';
    public string $editGroupDescription = '';

    // Campos do formulário de criação
    public string $newGroupName = '';
    public string $newGroupDescription = '';
    public string $newGroupMetric = 'dias_ativos';
    public float|string $newGroupPointsPerHour = 10.0;
    public float|string $newGroupPointsPerQuestion = 1.0;

    // Campo de ingresso
    public string $inviteCode = '';

    // Mensagens de erro e feedback
    public ?string $modalError = null;
    public ?string $feedbackMessage = null;

    /**
     * Inicializa a comunidade de estudos ativa a partir do parâmetro da rota.
     * Suporta tanto o parâmetro em português ($grupo) quanto em inglês ($group).
     */
    public function mount(?Grupo $grupo = null, ?Grupo $group = null): void
    {
        $grupoAlvo = $grupo ?? $group;

        /** @var Usuario $user */
        $user = Auth::user();

        if ($grupoAlvo && $grupoAlvo->exists) {
            // Valida se o estudante autenticado é membro da comunidade acessada
            if (!$grupoAlvo->membros()->where('usuarios.id', $user->id)->exists()) {
                abort(403, 'Você não tem acesso ao mural deste grupo.');
            }
            $this->groupId = $grupoAlvo->id;
            $this->metric = $grupoAlvo->metrica_padrao ?? 'dias_ativos';
        } else {
            // Seleciona a primeira comunidade do usuário caso nenhuma tenha sido passada na rota
            $firstGroup = $user->grupos()->first();
            if ($firstGroup) {
                $this->groupId = $firstGroup->id;
                $this->metric = $firstGroup->metrica_padrao ?? 'dias_ativos';
            }
        }
    }

    #[On('session-created')]
    public function onSessionCreated(): void
    {
        $this->resetPage();
    }

    #[On('session-deleted')]
    public function refreshFeed(): void
    {
        // Força a re-renderização do feed e do ranking quando houver remoção de sessões
    }

    public function selectGroup(int $id): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        $group = $user->grupos()->where('grupos.id', $id)->first();

        if ($group) {
            $this->groupId = $group->id;
            $this->metric = $group->metrica_padrao ?? 'dias_ativos';
            $this->feedbackMessage = null;
            $this->resetPage();
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['feed', 'leaderboard', 'mural', 'ranking'], true)) {
            $this->activeTab = in_array($tab, ['mural', 'feed']) ? 'feed' : 'leaderboard';
        }
    }

    public function setMetric(string $metric): void
    {
        if (in_array($metric, ['dias_ativos', 'active_days', 'horas_questoes', 'hours_and_questions', 'pontos', 'points'], true)) {
            $this->metric = $metric;
        }
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['semanal', 'mensal', 'geral', 'weekly', 'monthly', 'all_time'], true)) {
            $this->period = $period;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->modalError = null;
        $this->newGroupName = '';
        $this->newGroupDescription = '';
        $this->newGroupMetric = 'dias_ativos';
        $this->newGroupPointsPerHour = 10.0;
        $this->newGroupPointsPerQuestion = 1.0;
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->modalError = null;
    }

    public function createGroup(GrupoService $grupoService): void
    {
        $this->modalError = null;

        $rules = [
            'newGroupName' => ['required', 'string', 'min:2', 'max:100'],
            'newGroupDescription' => ['nullable', 'string', 'max:250'],
            'newGroupMetric' => ['required', 'in:dias_ativos,active_days,horas_questoes,hours_and_questions,pontos,points'],
        ];

        $isPontos = in_array($this->newGroupMetric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points'], true);
        if ($isPontos) {
            $rules['newGroupPointsPerHour'] = ['required', 'numeric', 'min:0.01'];
            $rules['newGroupPointsPerQuestion'] = ['required', 'numeric', 'min:0.01'];
        }

        $dados = $this->validate($rules, [
            'newGroupName.required' => 'O nome do grupo é obrigatório.',
            'newGroupName.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'newGroupName.max' => 'O nome não pode exceder 100 caracteres.',
            'newGroupDescription.max' => 'A descrição não pode exceder 250 caracteres.',
            'newGroupMetric.in' => 'Modalidade inválida. Escolha entre "Dias Ativos" e "Horas Líquidas + Questões".',
            'newGroupPointsPerHour.required' => 'Os pontos por hora líquida são obrigatórios.',
            'newGroupPointsPerHour.numeric' => 'Os pontos por hora devem ser um número válido.',
            'newGroupPointsPerHour.min' => 'Os pontos por hora líquida devem ser maiores que zero.',
            'newGroupPointsPerQuestion.required' => 'Os pontos por questão resolvida são obrigatórios.',
            'newGroupPointsPerQuestion.numeric' => 'Os pontos por questão devem ser um número válido.',
            'newGroupPointsPerQuestion.min' => 'Os pontos por questão resolvida devem ser maiores que zero.',
        ]);

        try {
            $payload = [
                'nome' => $dados['newGroupName'],
                'descricao' => $dados['newGroupDescription'],
                'metrica_padrao' => $dados['newGroupMetric'],
            ];

            if ($isPontos) {
                $payload['pontos_hora'] = $this->newGroupPointsPerHour;
                $payload['pontos_questao'] = $this->newGroupPointsPerQuestion;
            }

            $grupo = $grupoService->criarGrupo(Auth::user(), $payload);

            $this->groupId = $grupo->id;
            $this->metric = $grupo->metrica_padrao;
            $this->showCreateModal = false;
            $this->resetPage();
            $this->feedbackMessage = "Comunidade '{$grupo->nome}' criada com sucesso! Código de convite: {$grupo->codigo_convite}";
        } catch (\Throwable $e) {
            $this->modalError = $e->getMessage();
        }
    }

    public function openJoinModal(): void
    {
        $this->resetValidation();
        $this->modalError = null;
        $this->inviteCode = '';
        $this->showJoinModal = true;
    }

    public function closeJoinModal(): void
    {
        $this->showJoinModal = false;
        $this->modalError = null;
    }

    public function joinGroup(GrupoService $grupoService): void
    {
        $this->modalError = null;

        $this->validate([
            'inviteCode' => ['required', 'string', 'min:6', 'max:16'],
        ], [
            'inviteCode.required' => 'Informe o código de convite do grupo.',
            'inviteCode.min' => 'O código de convite deve ter no mínimo 6 caracteres.',
            'inviteCode.max' => 'Código de convite inválido.',
        ]);

        try {
            $grupo = $grupoService->entrarGrupoPorCodigo(Auth::user(), $this->inviteCode);

            $this->groupId = $grupo->id;
            $this->metric = $grupo->metrica_padrao;
            $this->showJoinModal = false;
            $this->resetPage();
            $this->feedbackMessage = "Você entrou na comunidade '{$grupo->nome}'!";
        } catch (\Throwable $e) {
            $this->modalError = $e->getMessage();
            $this->showJoinModal = true;
        }
    }

    public function openLeaveModal(): void
    {
        $this->modalError = null;
        $this->showLeaveModal = true;
    }

    public function openEditModal(): void
    {
        $this->modalError = null;
        $this->resetValidation();
        $group = Grupo::find($this->groupId);

        if ($group) {
            $this->editGroupName = $group->nome;
            $this->editGroupDescription = $group->descricao ?? '';
            $this->showEditModal = true;
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->modalError = null;
        $this->resetValidation();
    }

    public function updateGroupSettings(GrupoService $grupoService): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        $group = Grupo::find($this->groupId);

        if (!$group || !$user) {
            return;
        }

        if ($group->proprietario_id !== $user->id) {
            $this->modalError = "Apenas o líder proprietário pode alterar as configurações da comunidade.";
            return;
        }

        $this->validate([
            'editGroupName' => ['required', 'string', 'min:2', 'max:100'],
            'editGroupDescription' => ['nullable', 'string', 'max:500'],
        ], [
            'editGroupName.required' => 'O nome da comunidade é obrigatório.',
            'editGroupName.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'editGroupName.max' => 'O nome não pode exceder 100 caracteres.',
            'editGroupDescription.max' => 'A descrição não pode exceder 500 caracteres.',
        ]);

        try {
            $grupoService->atualizarGrupo($user, $group, [
                'nome' => trim($this->editGroupName),
                'descricao' => trim($this->editGroupDescription) ?: null,
            ]);

            $this->showEditModal = false;
            $this->feedbackMessage = "Configurações da comunidade atualizadas com sucesso!";
        } catch (\Throwable $e) {
            $this->modalError = $e->getMessage();
        }
    }

    public function closeLeaveModal(): void
    {
        $this->showLeaveModal = false;
        $this->modalError = null;
    }

    public function leaveGroup(GrupoService $grupoService): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        $group = Grupo::find($this->groupId);

        if (!$group) {
            $this->showLeaveModal = false;
            return;
        }

        try {
            $grupoService->sairGrupo($user, $group);
            $this->showLeaveModal = false;

            // Seleciona a próxima comunidade disponível
            $proximo = $user->grupos()->first();
            $this->groupId = $proximo?->id;
            $this->resetPage();
            $this->feedbackMessage = "Você saiu do grupo '{$group->nome}'.";
        } catch (\Throwable $e) {
            $this->modalError = $e->getMessage();
        }
    }

    public function render(AgregadorMetricasService $metricasService)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        $userGroups = $user->grupos()->withCount('membros')->orderBy('nome', 'asc')->get();

        $currentGroup = $this->groupId
            ? $userGroups->firstWhere('id', $this->groupId)
            : $userGroups->first();

        $isOwner = false;
        $feedSessions = collect();
        $leaderboard = collect();

        if ($currentGroup) {
            $this->groupId = $currentGroup->id;
            $isOwner = $currentGroup->proprietario_id === $user->id;

            if ($this->activeTab === 'feed') {
                $grupoIdAtual = $currentGroup->id;
                $consultarFeed = fn () => $currentGroup->sessoes()
                    ->with([
                        'usuario',
                        'reacoes' => fn ($q) => $q->where('grupo_id', $grupoIdAtual),
                    ])
                    ->withCount([
                        'comentarios' => fn ($q) => $q->where('grupo_id', $grupoIdAtual),
                    ])
                    ->orderBy('data_estudo', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(10);

                $feedSessions = $consultarFeed();

                if ($feedSessions->isEmpty() && $feedSessions->currentPage() > 1 && $feedSessions->lastPage() >= 1) {
                    $this->setPage($feedSessions->lastPage());
                    $feedSessions = $consultarFeed();
                }
            } else {
                $leaderboard = $metricasService->obterClassificacaoGrupo(
                    $currentGroup,
                    $this->metric,
                    $this->period
                );
            }
        }

        return view('livewire.grupos.detalhes-grupo', [
            'user' => $user,
            'userGroups' => $userGroups,
            'currentGroup' => $currentGroup,
            'isOwner' => $isOwner,
            'feedSessions' => $feedSessions,
            'leaderboard' => $leaderboard,
        ]);
    }
}
