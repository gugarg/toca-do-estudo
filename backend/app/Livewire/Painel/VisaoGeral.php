<?php

declare(strict_types=1);

namespace App\Livewire\Painel;

use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Toca do Estudo')]
class VisaoGeral extends Component
{
    use WithPagination;

    #[On('session-created')]
    public function onSessionCreated(): void
    {
        // Volta para a primeira página para exibir o novo registro no topo
        $this->resetPage();
    }

    #[On('session-deleted')]
    #[On('refresh-dashboard')]
    public function refresh(): void
    {
        // Re-renderiza o componente reativamente quando houver exclusão ou atualização
    }

    public function render(AgregadorMetricasService $metricasService)
    {
        /** @var Usuario $user */
        $user = Auth::user();

        // Consulta dinamicamente as métricas analíticas consolidadas do estudante
        $stats = $metricasService->obterEstatisticasUsuario($user->id);

        $summary = [
            'total_active_days' => $stats->total_dias_ativos,
            'total_hours' => $stats->total_hours,
            'total_minutes' => $stats->total_minutos,
            'total_questions' => $stats->total_questoes,
            'groups_count' => $user->grupos()->count(),
        ];

        // 1. Histórico paginado de sessões de estudo (5 por página)
        $consultarSessoes = fn () => $user->sessoesEstudo()
            ->with(['reacoes', 'usuario'])
            ->withCount('comentarios')
            ->orderBy('data_estudo', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(5);

        $recentSessions = $consultarSessoes();

        if ($recentSessions->isEmpty() && $recentSessions->currentPage() > 1 && $recentSessions->lastPage() >= 1) {
            $this->setPage($recentSessions->lastPage());
            $recentSessions = $consultarSessoes();
        }

        // 2. Grupos do estudante
        $groups = $user->grupos()
            ->withCount('membros')
            ->orderBy('nome', 'asc')
            ->get();

        return view('livewire.painel.visao-geral', [
            'user' => $user,
            'summary' => $summary,
            'recentSessions' => $recentSessions,
            'groups' => $groups,
        ]);
    }
}
