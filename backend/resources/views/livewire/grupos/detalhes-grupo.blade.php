<main class="container" style="width: 100%; max-width: 1480px; margin: 0 auto; padding: 28px 32px 60px 32px; box-sizing: border-box; position: relative;">
    <div class="animacao-surgir">
    <!-- Barra Superior de Comunidades e Ações Rápidas -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 20px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.015em;">
                Comunidades de Estudo
            </h1>
            <p style="font-size: 13.5px; color: var(--text-secondary); margin-top: 2px;">
                Compartilhe consistência, acompanhe o mural e dispute rankings temáticos.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <!-- Botão para abrir modal de entrada em comunidade por código de convite -->
            <button type="button"
                    wire:click="openJoinModal"
                    class="btn btn-secondary"
                    style="gap: 6px; font-size: 12.5px;">
                <x-icon name="key-round" size="14" />
                <span>Entrar por Código</span>
            </button>
            <!-- Botão para abrir formulário de criação de nova comunidade -->
            <button type="button"
                    wire:click="openCreateModal"
                    class="btn btn-primary"
                    style="gap: 6px; font-size: 12.5px;">
                <x-icon name="plus" size="14" />
                <span>Criar Comunidade</span>
            </button>
        </div>
    </div>

    <!-- Alerta de Feedback -->
    @if($feedbackMessage)
        <x-alert type="success" :message="$feedbackMessage" wireDismiss="$set('feedbackMessage', null)" />
    @endif

    @if($userGroups->isEmpty())
        <!-- Estado Vazio: Estudante não pertence a nenhum grupo -->
        <x-empty-state icon="users"
                       title="Você ainda não entrou em nenhuma comunidade"
                       description="A Toca do Estudo é construída em torno da prestação mútua de contas. Entre em um grupo usando o código de convite de seus colegas ou funde seu próprio grupo de estudos.">
            <x-slot:actions>
                <button type="button" wire:click="openJoinModal" class="btn btn-secondary">
                    <x-icon name="key-round" size="15" />
                    <span>Digitar Código de Convite</span>
                </button>
                <button type="button" wire:click="openCreateModal" class="btn btn-primary">
                    <x-icon name="plus" size="15" />
                    <span>Criar Minha Primeira Comunidade</span>
                </button>
            </x-slot:actions>
        </x-empty-state>
    @else
        <!-- Seletor de Grupos em Abas/Chips -->
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 16px;">
            @foreach($userGroups as $g)
                @php $isSelected = $currentGroup && $currentGroup->id === $g->id; @endphp
                <button type="button"
                        wire:click="selectGroup({{ $g->id }})"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: var(--radius-md); border: 1px solid; border-color: {{ $isSelected ? 'var(--border-focus)' : 'var(--border-subtle)' }}; background-color: {{ $isSelected ? 'var(--bg-surface-elevated)' : 'var(--bg-surface)' }}; color: {{ $isSelected ? 'var(--text-primary)' : 'var(--text-secondary)' }}; font-weight: {{ $isSelected ? 600 : 500 }}; font-size: 13px; cursor: pointer; white-space: nowrap; transition: all 120ms ease;">
                    <x-icon name="users" size="14" style="color: {{ $isSelected ? 'var(--color-primary)' : 'var(--text-muted)' }};" />
                    <span>{{ $g->nome }}</span>
                    <span style="font-size: 11px; color: var(--text-muted); padding: 0 5px; border-radius: 3px; background-color: var(--bg-surface-subtle);">
                        {{ $g->members_count }}
                    </span>
                </button>
            @endforeach
        </div>

        @if($currentGroup)
            <!-- Cabeçalho da Comunidade Selecionada -->
            <div class="card" style="margin-bottom: 20px; padding: 20px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <h2 style="font-size: 18px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.015em;">
                                {{ $currentGroup->nome }}
                            </h2>

                            @php
                                $isHorasQuestoes = in_array($currentGroup->metrica_padrao, [\App\Models\Grupo::MODALIDADE_HORAS_QUESTOES, 'hours_and_questions', 'pontos', 'points', 'hours', 'questions'], true);
                                $rotuloModalidade = $isHorasQuestoes
                                    ? 'Modalidade: Horas Líquidas + Questões (' . (float)($currentGroup->pontos_hora ?? 10) . ' pts/h • ' . (float)($currentGroup->pontos_questao ?? 1) . ' pts/q)'
                                    : 'Modalidade: Dias Ativos';
                            @endphp
                            <span class="badge badge-hours" style="font-weight: 600;">
                                {{ $rotuloModalidade }}
                            </span>
                        </div>

                        @if($currentGroup->descricao)
                            <p style="font-size: 13px; color: var(--text-secondary); margin-top: 6px; max-width: 650px; line-height: 1.5;">
                                {{ $currentGroup->descricao }}
                            </p>
                        @endif

                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 12px; font-size: 12px; color: var(--text-muted);">
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="users" size="14" />
                                <span>{{ $currentGroup->members_count }} membros</span>
                            </span>

                            <!-- Box de Código de Convite com Cópia Rápida -->
                            <div x-data="{ copiado: false }" style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Código de convite:</span>
                                <span style="font-family: monospace; font-size: 12px; font-weight: 600; color: var(--text-primary); background-color: var(--bg-surface-subtle); padding: 2px 7px; border-radius: 4px; border: 1px solid var(--border-subtle); letter-spacing: 0.05em;">
                                    {{ $currentGroup->codigo_convite }}
                                </span>
                                <!-- Botão para copiar o código de convite da comunidade -->
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $currentGroup->codigo_convite }}'); copiado = true; setTimeout(() => copiado = false, 2000)"
                                        class="btn-icon"
                                        title="Copiar código de convite">
                                    <template x-if="!copiado">
                                        <x-icon name="copy" size="13" />
                                    </template>
                                    <template x-if="copiado">
                                        <span style="display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: var(--metric-active);">
                                            <x-icon name="check" size="13" />
                                            <span>Copiado</span>
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Ações da Comunidade (Menu Discreto de 3 Pontos) -->
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($isOwner)
                            <span style="font-size: 11.5px; color: var(--text-muted); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 500;">
                                Líder da Comunidade
                            </span>
                        @endif

                        <div x-data="{ menuAberto: false }" style="position: relative;">
                            <!-- Botão discreto de 3 pontos -->
                            <button type="button"
                                    @click="menuAberto = !menuAberto"
                                    class="btn-icon"
                                    style="width: 32px; height: 32px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); background-color: var(--bg-surface-elevated); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 120ms ease;"
                                    title="Opções da Comunidade"
                                    aria-label="Opções da Comunidade">
                                <x-icon name="more-vertical" size="16" />
                            </button>

                            <!-- Menu Dropdown -->
                            <div x-show="menuAberto"
                                 x-cloak
                                 @click.outside="menuAberto = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 style="position: absolute; right: 0; top: 100%; margin-top: 6px; width: 220px; background-color: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); z-index: 50; padding: 4px; display: flex; flex-direction: column; gap: 2px;">

                                @if($isOwner)
                                    <!-- Opção de Configurações do Grupo (Exclusiva do Líder) -->
                                    <button type="button"
                                            wire:click="openEditModal"
                                            @click="menuAberto = false"
                                            style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: none; background: none; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-primary); text-align: left; cursor: pointer; transition: background 120ms ease;"
                                            onmouseenter="this.style.backgroundColor='var(--bg-surface-hover)'"
                                            onmouseleave="this.style.backgroundColor='transparent'">
                                        <x-icon name="settings" size="14" style="color: var(--text-secondary);" />
                                        <span>Configurações do Grupo</span>
                                    </button>

                                    <div style="height: 1px; background-color: var(--border-subtle); margin: 3px 0;"></div>

                                    <!-- Opção Informativa de Sair (Líder) -->
                                    <button type="button"
                                            wire:click="openLeaveModal"
                                            @click="menuAberto = false"
                                            style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: none; background: none; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-muted); text-align: left; cursor: pointer; transition: background 120ms ease;"
                                            onmouseenter="this.style.backgroundColor='var(--bg-surface-hover)'"
                                            onmouseleave="this.style.backgroundColor='transparent'">
                                        <x-icon name="log-out" size="14" style="color: var(--text-muted);" />
                                        <span>Sair da Comunidade</span>
                                    </button>
                                @else
                                    <!-- Opção de Sair do Grupo (Membro Comum) -->
                                    <button type="button"
                                            wire:click="openLeaveModal"
                                            @click="menuAberto = false"
                                            style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: none; background: none; border-radius: var(--radius-sm); font-size: 13px; color: var(--color-danger); text-align: left; cursor: pointer; transition: background 120ms ease;"
                                            onmouseenter="this.style.backgroundColor='rgba(239, 68, 68, 0.08)'"
                                            onmouseleave="this.style.backgroundColor='transparent'">
                                        <x-icon name="log-out" size="14" style="color: var(--color-danger);" />
                                        <span>Sair da Comunidade</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seletor de Abas (Feed vs Leaderboard) -->
                <div style="display: flex; gap: 8px; margin-top: 20px; border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                    <!-- Botão para alternar para a aba do mural de estudos -->
                    <button type="button"
                            wire:click="switchTab('feed')"
                            class="btn"
                            style="gap: 6px; font-size: 12.5px; font-weight: {{ $activeTab === 'feed' ? 600 : 500 }}; background-color: {{ $activeTab === 'feed' ? 'var(--color-primary)' : 'var(--bg-surface-subtle)' }}; color: {{ $activeTab === 'feed' ? '#ffffff' : 'var(--text-secondary)' }}; border-color: {{ $activeTab === 'feed' ? 'transparent' : 'var(--border-subtle)' }};">
                        <x-icon name="book-open" size="14" />
                        <span>Mural de Estudos</span>
                    </button>
                    <!-- Botão para alternar para a aba de classificação e ranking -->
                    <button type="button"
                            wire:click="switchTab('leaderboard')"
                            class="btn"
                            style="gap: 6px; font-size: 12.5px; font-weight: {{ $activeTab === 'leaderboard' ? 600 : 500 }}; background-color: {{ $activeTab === 'leaderboard' ? 'var(--color-primary)' : 'var(--bg-surface-subtle)' }}; color: {{ $activeTab === 'leaderboard' ? '#ffffff' : 'var(--text-secondary)' }}; border-color: {{ $activeTab === 'leaderboard' ? 'transparent' : 'var(--border-subtle)' }};">
                        <x-icon name="trophy" size="14" />
                        <span>Classificação e Ranking</span>
                    </button>
                </div>
            </div>

            <!-- Conteúdo da Aba Ativa -->
            @if($activeTab === 'feed')
                <!-- MURAL DE ESTUDOS (FEED) -->
                <div>
                    @if($feedSessions->isEmpty())
                        <div class="card" style="text-align: center; padding: 40px 20px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); margin-bottom: 12px;">
                                <x-icon name="book-open" size="20" />
                            </div>
                            <h3 style="font-size: 14.5px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                Nenhum estudo publicado neste grupo ainda.
                            </h3>
                            <p style="font-size: 12.5px; color: var(--text-muted); max-width: 340px; margin: 0 auto 16px auto; line-height: 1.5;">
                                Seja o primeiro membro a registrar uma sessão de estudo e compartilhar sua consistência com o grupo!
                            </p>
                            <button type="button"
                                    x-data
                                    @click="$dispatch('abrir-modal-sessao')"
                                    class="btn btn-primary"
                                    style="font-size: 13px;">
                                Registrar Primeiro Estudo
                            </button>
                        </div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            @foreach($feedSessions as $sessao)
                                @livewire('sessoes-estudo.card-sessao', ['session' => $sessao, 'groupId' => $currentGroup->id], key('group-session-' . $currentGroup->id . '-card-' . $sessao->id))
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <!-- TABELA DE CLASSIFICAÇÃO E RANKING -->
                <div class="card">
                    <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <h3 style="font-size: 14px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <x-icon name="trophy" size="16" style="color: var(--text-secondary);" />
                                <span>Ranking da Comunidade</span>
                            </h3>

                            <!-- Seletor de Período -->
                            <div style="display: flex; gap: 2px; background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); padding: 2px; border-radius: var(--radius-md);">
                                @foreach(['weekly' => 'Semana', 'monthly' => 'Mês', 'all_time' => 'Geral'] as $pKey => $pLabel)
                                    @php $isPeriodActive = $period === $pKey; @endphp
                                    <button type="button"
                                            wire:click="setPeriod('{{ $pKey }}')"
                                            style="border: 1px solid; border-color: {{ $isPeriodActive ? 'var(--border-subtle)' : 'transparent' }}; background-color: {{ $isPeriodActive ? 'var(--bg-surface-elevated)' : 'transparent' }}; color: {{ $isPeriodActive ? 'var(--text-primary)' : 'var(--text-muted)' }}; font-weight: {{ $isPeriodActive ? 600 : 400 }}; font-size: 11.5px; padding: 3px 9px; border-radius: var(--radius-sm); cursor: pointer; transition: all 120ms ease;">
                                        {{ $pLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Seletor de Modalidade -->
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            @php
                                $metricOptions = [
                                    ['key' => 'dias_ativos', 'label' => 'Dias Ativos', 'icon' => 'calendar', 'color' => 'var(--metric-active)'],
                                    ['key' => 'horas_questoes', 'label' => 'Horas Líquidas + Questões', 'icon' => 'award', 'color' => 'var(--color-primary)'],
                                ];
                            @endphp
                            @foreach($metricOptions as $m)
                                @php
                                    $isMetricActive = ($metric === $m['key'])
                                        || ($m['key'] === 'dias_ativos' && in_array($metric, ['active_days', 'dias_ativos'], true))
                                        || ($m['key'] === 'horas_questoes' && in_array($metric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points', 'hours', 'questions'], true));
                                @endphp
                                <button type="button"
                                        wire:click="setMetric('{{ $m['key'] }}')"
                                        class="btn btn-secondary"
                                        style="font-size: 12px; padding: 5px 12px; background-color: {{ $isMetricActive ? 'var(--bg-surface-elevated)' : 'var(--bg-surface-subtle)' }}; border-color: {{ $isMetricActive ? 'var(--border-hover)' : 'var(--border-subtle)' }}; color: {{ $isMetricActive ? 'var(--text-primary)' : 'var(--text-secondary)' }}; font-weight: {{ $isMetricActive ? 600 : 400 }}; gap: 6px;">
                                    <x-icon :name="$m['icon']" size="13" style="color: {{ $m['color'] }};" />
                                    <span>{{ $m['label'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        @php
                            $isHorasQuestoesVisualizacao = in_array($metric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points', 'hours', 'questions'], true);
                        @endphp
                        @if($isHorasQuestoesVisualizacao)
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 8px 12px; border-radius: var(--radius-sm); background: var(--color-primary-subtle); border: 1px solid var(--color-primary-border); font-size: 12px; color: #93c5fd;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <x-icon name="info" size="14" />
                                    <span><strong>Fórmula:</strong> (Horas Líquidas × {{ (float) ($currentGroup->pontos_hora ?? 10) }}) + (Questões × {{ (float) ($currentGroup->pontos_questao ?? 1) }})</span>
                                </div>
                                <span style="font-size: 11px; opacity: 0.9;">Pesos imutáveis da comunidade</span>
                            </div>
                        @endif
                    </div>

                    @if($leaderboard->isEmpty())
                        <div style="padding: 32px 0; text-align: center; color: var(--text-muted); font-size: 13px;">
                            Nenhum registro para o período e métrica selecionados.
                        </div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            @foreach($leaderboard as $item)
                                @php
                                    $isCurrentUser = $item['user_id'] === $user->id;
                                    $medalColor = match($item['position']) {
                                        1 => '#f59e0b',
                                        2 => '#9da7b3',
                                        3 => '#d97706',
                                        default => 'var(--text-muted)',
                                    };
                                    $isHorasLinha = in_array($metric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points', 'hours', 'questions'], true);
                                    $metricColor = $isHorasLinha ? 'var(--color-primary)' : 'var(--metric-active)';
                                    $metricBg = $isHorasLinha ? 'var(--color-primary-subtle)' : 'var(--metric-active-subtle)';
                                    $metricBorder = $isHorasLinha ? 'var(--color-primary-border)' : 'var(--metric-active-border)';
                                @endphp
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: var(--radius-md); background-color: {{ $item['position'] <= 3 ? 'var(--bg-surface-subtle)' : 'transparent' }}; border: 1px solid; border-color: {{ $isCurrentUser ? 'var(--color-primary-border)' : ($item['position'] <= 3 ? 'var(--border-subtle)' : 'transparent') }}; transition: background-color 100ms ease;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 24px; text-align: center; font-size: 12px; font-weight: 600; color: {{ $medalColor }};">
                                            {{ $item['position'] }}º
                                        </div>

                                        <x-avatar :src="$item['avatar_url']" :name="$item['name']" size="sm" style="border-radius: 50%; width: 30px; height: 30px;" />

                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="font-size: 13px; font-weight: 600; color: var(--text-primary);">
                                                {{ $item['name'] }}
                                            </span>
                                            @if($isCurrentUser)
                                                <span style="font-size: 10px; font-weight: 600; background-color: var(--color-primary-subtle); color: var(--color-primary); border: 1px solid var(--color-primary-border); padding: 0 5px; border-radius: 3px;">
                                                    Você
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="text-align: right;">
                                        <span style="font-size: 12.5px; font-weight: 600; color: {{ $metricColor }}; background-color: {{ $metricBg }}; padding: 3px 8px; border-radius: var(--radius-sm); border: 1px solid {{ $metricBorder }};">
                                            {{ $item['metric_label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endif
    @endif
    </div> <!-- Fim animacao-surgir -->

    <!-- Modais Modularizados em Parciais Reutilizáveis -->
    @include('livewire.grupos.partials.modal-criar-grupo')
    @include('livewire.grupos.partials.modal-ingressar-grupo')
    @include('livewire.grupos.partials.modal-sair-grupo')
    @include('livewire.grupos.partials.modal-configuracoes-grupo')
</main>

