<main class="container animacao-surgir" style="width: 100%; max-width: 1480px; margin: 0 auto; padding: 28px 32px 60px 32px; box-sizing: border-box;">
    <!-- Cabeçalho de Boas-Vindas -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.015em;">
                Olá, {{ explode(' ', $user->name)[0] }}
            </h1>
            <p style="font-size: 13.5px; color: var(--text-secondary); margin-top: 2px;">
                Visão consolidada da sua consistência e carga acadêmica.
            </p>
        </div>
    </div>

    <!-- 4 Cards de Métricas Consolidadas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; width: 100%;">
        <x-card-stat
            titulo="Dias Ativos"
            :valor="$summary['total_active_days']"
            descricao="Total acumulado no histórico"
            icone="calendar"
            corIcone="var(--metric-active)"
        />
        <x-card-stat
            titulo="Horas Estudadas"
            :valor="$summary['total_hours'] . 'h'"
            descricao="Tempo registrado em todo o histórico"
            icone="clock"
            corIcone="var(--metric-hours)"
        />
        <x-card-stat
            titulo="Questões Resolvidas"
            :valor="$summary['total_questions']"
            descricao="Total informado em todo o histórico"
            icone="target"
            corIcone="var(--metric-questions)"
        />
        <x-card-stat
            titulo="Grupos Ativos"
            :valor="$summary['groups_count']"
            descricao="Comunidades de estudo"
            icone="users"
            corIcone="var(--text-secondary)"
        />
    </div>

    <!-- Layout Principal: Feed de Registros (Coluna Principal) + Sidebar de Comunidades (Direita) -->
    <div class="dashboard-layout">
        <!-- Coluna Principal: Seus Últimos Registros (Feed de Estudos com Prova Visual) -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h2 style="font-size: 16px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="book-open" size="18" style="color: var(--color-primary);" />
                    <span>Seus Últimos Registros</span>
                </h2>
            </div>

            @if($recentSessions->isEmpty())
                <div class="card" style="text-align: center; padding: 48px 20px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); margin-bottom: 12px;">
                        <x-icon name="book-open" size="20" />
                    </div>
                    <h3 style="font-size: 14.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                        Nenhum estudo registrado ainda.
                    </h3>
                    <p style="font-size: 12.5px; color: var(--text-muted); max-width: 340px; margin: 0 auto 18px auto; line-height: 1.5;">
                        Lance 20 minutos de leitura ou 5 questões para ativar o seu dia e começar a preencher o histórico de consistência.
                    </p>
                    <button type="button"
                            x-data
                            @click="$dispatch('abrir-modal-sessao')"
                            class="btn btn-secondary"
                            style="font-size: 13px;">
                        Fazer Primeiro Registro
                    </button>
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    @foreach($recentSessions as $sessao)
                        @livewire('sessoes-estudo.card-sessao', ['session' => $sessao], key('session-card-' . $sessao->id))
                    @endforeach
                </div>

                {{ $recentSessions->links('components.paginacao') }}
            @endif
        </div>

        <!-- Coluna Lateral (Sidebar): Meus Grupos de Estudo -->
        <aside style="position: sticky; top: 80px;">
            <div class="card" style="display: flex; flex-direction: column; justify-content: flex-start;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="font-size: 14.5px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em; display: flex; align-items: center; gap: 8px;">
                        <x-icon name="users" size="16" style="color: var(--text-secondary);" />
                        <span>Meus Grupos</span>
                    </h3>
                    <a href="{{ route('comunidades') }}" class="btn btn-ghost" style="padding: 2px 8px; font-size: 12px; color: var(--color-primary);">
                        Ver todos
                    </a>
                </div>

                @if($groups->isEmpty())
                    <p style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5;">
                        Você ainda não entrou em nenhum grupo. Participe de uma comunidade para acompanhar o feed e disputar o ranking!
                    </p>
                    <a href="{{ route('comunidades') }}" class="btn btn-secondary" style="margin-top: 10px; width: 100%; font-size: 12.5px;">
                        Explorar Comunidades
                    </a>
                @else
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        @foreach($groups as $g)
                            @php
                                $totalMembros = $g->membros_count ?? $g->members_count ?? 1;
                            @endphp
                            <a href="{{ route('comunidades.show', ['grupo' => $g->id]) }}"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: var(--radius-md); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); text-decoration: none; transition: all 120ms ease;"
                                onmouseenter="this.style.backgroundColor='var(--bg-surface-hover)'; this.style.borderColor='var(--border-hover)';"
                                onmouseleave="this.style.backgroundColor='var(--bg-surface-subtle)'; this.style.borderColor='var(--border-subtle)';">
                                <div style="min-width: 0;">
                                    <div style="font-size: 13px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $g->nome }}
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                        {{ $totalMembros }} {{ $totalMembros === 1 ? 'membro' : 'membros' }}
                                    </div>
                                </div>
                                <x-icon name="chevron-right" size="16" style="color: var(--text-muted); flex-shrink: 0; margin-left: 8px;" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    </div>
</main>
