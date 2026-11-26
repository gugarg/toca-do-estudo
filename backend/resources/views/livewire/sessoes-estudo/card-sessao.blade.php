<article class="card feed-card" style="position: relative;">
    <!-- ========================================================================= -->
    <!-- 1. CABEÇALHO DO CARD: Autor, Contexto da Comunidade e Data                 -->
    <!-- ========================================================================= -->
    <div class="feed-header">
        <div class="feed-user-info">
            <x-avatar :user="$session->usuario ?? $session->user" size="md" style="border-radius: 50%;" />

            <div>
                <div class="feed-name-row">
                    <span class="feed-user-name">
                        {{ $session->usuario?->nome ?? $session->user?->name ?? 'Estudante' }}
                    </span>
                </div>

                <div class="feed-date-row">
                    <x-icon name="calendar" size="11" />
                    <span>{{ $session->study_date ? \Carbon\Carbon::parse($session->study_date)->format('d/m/Y') : '' }}</span>

                    @if(!$isToday)
                        <span class="feed-retro-tag" title="Sessão registrada retroativamente em até 7 dias">
                            <x-icon name="history" size="10" />
                            <span>Retroativo</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Ações do Autor (Excluir) -->
        @if(auth()->id() === $session->user_id)
            <div x-data="{ confirmando: false }" style="position: relative;">
                <button type="button" @click="confirmando = !confirmando" class="btn-icon"
                    style="color: var(--text-muted); padding: 5px; border-radius: var(--radius-sm);"
                    title="Opções da sessão">
                    <x-icon name="trash" size="14" />
                </button>

                <div x-show="confirmando" @click.outside="confirmando = false" x-cloak
                    style="position: absolute; right: 0; top: 100%; margin-top: 4px; width: 175px; background-color: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); padding: 10px; z-index: 20;">
                    <p style="font-size: 11.5px; color: var(--text-primary); margin: 0 0 8px 0; font-weight: 500;">
                        Excluir este estudo?
                    </p>
                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                        <button type="button" @click="confirmando = false" class="btn btn-ghost btn-sm"
                            style="padding: 4px 8px; font-size: 11px;">
                            Não
                        </button>
                        <button type="button" wire:click="deleteSession" class="btn btn-danger btn-sm"
                            style="padding: 4px 8px; font-size: 11px;">
                            Sim, excluir
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- 2. MATÉRIA / CONTEÚDO PRINCIPAL (Destaque Editorial com Overline)          -->
    <!-- ========================================================================= -->
    @php
        $materiaNome = $session->subject ?? $session->disciplina ?? 'Estudo Registrado';
    @endphp

    <div class="feed-subject-block">
        <div class="feed-subject-overline">
            <span>Foco do Estudo</span>
        </div>
        <h3 class="feed-subject-title">
            {{ $materiaNome }}
        </h3>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. BARRA DE MÉTRICAS DE ESFORÇO (Chips Limpos e Harmonizados)              -->
    <!-- ========================================================================= -->
    <div class="feed-metrics-row">
        <span class="badge badge-active" title="Registrado e computado como dia ativo">
            <x-icon name="sparkles" size="11" />
            <span>Dia Ativo</span>
        </span>

        <span class="badge badge-hours" title="Tempo total de estudo líquido">
            <x-icon name="clock" size="11" />
            <span>{{ $session->hours_formatted }}</span>
        </span>

        <span class="badge badge-questions" title="Volume de questões resolvidas">
            <x-icon name="target" size="11" />
            <span>{{ $session->questions }} {{ $session->questions === 1 ? 'questão' : 'questões' }}</span>
        </span>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. COMPROVAÇÃO VISUAL (Proof of Study - Foto em Proporção Otimizada)       -->
    <!-- ========================================================================= -->
    @php
        $fotoPath = $session->photo_path ?? $session->caminho_foto ?? null;
        $fotoUrl = $fotoPath ? asset('storage/' . ltrim($fotoPath, '/')) : null;
    @endphp

    @if($fotoUrl)
        <div wire:click="$dispatch('open-photo-lightbox', { url: '{{ $fotoUrl }}', subject: '{{ addslashes($materiaNome) }}' })"
            class="feed-photo-container" title="Clique para ampliar a foto comprobatória em alta resolução">
            <img src="{{ $fotoUrl }}" alt="Comprovação de estudo: {{ $materiaNome }}" class="feed-photo" loading="lazy">

            <div class="feed-photo-badge">
                <x-icon name="camera" size="12" />
                <span>Ampliar</span>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 5. LEGENDA / NOTAS DA SESSÃO                                              -->
    <!-- ========================================================================= -->
    @if($session->notes)
        <p class="feed-notes">
            {{ $session->notes }}
        </p>
    @endif

    <!-- ========================================================================= -->
    <!-- 6. BARRA DE AÇÕES SOCIAIS: Reações e Alternância de Comentários            -->
    <!-- ========================================================================= -->
    <div class="feed-actions-bar">
        <div class="feed-reactions-group">
            @if($canInteract)
                <!-- Seletor Flutuante de Reações -->
                @if($showReactionPicker)
                    <div class="reaction-picker" @click.outside="$wire.toggleReactionPicker()">
                        @foreach($predefinedReactions as $tipo => $reacao)
                            <button type="button" wire:click="toggleReaction('{{ $tipo }}')"
                                class="reaction-picker-btn {{ $userReaction === $tipo ? 'selected' : '' }}"
                                title="{{ $reacao['emoji'] }} {{ $reacao['label'] }} — {{ $reacao['desc'] }}">
                                <span class="reaction-emoji">{{ $reacao['emoji'] }}</span>
                                <span class="reaction-label">{{ $reacao['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Botão Gatilho do Seletor (Apoiar / Reagir) -->
                <button type="button" wire:click="toggleReactionPicker"
                    class="btn btn-secondary reaction-trigger-btn {{ $userReaction ? 'active' : '' }}"
                    title="Adicionar ou alterar reação de incentivo">
                    @if($userReaction && isset($predefinedReactions[$userReaction]))
                        <span>{{ $predefinedReactions[$userReaction]['emoji'] }}</span>
                        <span>{{ $predefinedReactions[$userReaction]['label'] }}</span>
                    @else
                        <x-icon name="sparkles" size="13" />
                        <span>Apoiar</span>
                    @endif
                </button>

                <!-- Chips com Contagem por Emoji (Estilo GitHub/Slack) -->
                @foreach($reactionsBreakdown as $tipo => $count)
                    @if($count > 0 && isset($predefinedReactions[$tipo]))
                        <button type="button" wire:click="toggleReaction('{{ $tipo }}')"
                            class="reaction-chip {{ $userReaction === $tipo ? 'selected' : '' }}"
                            title="{{ $userReaction === $tipo ? 'Você reagiu com ' . $predefinedReactions[$tipo]['label'] . ' (clique para remover)' : 'Reagir com ' . $predefinedReactions[$tipo]['label'] }}">
                            <span>{{ $predefinedReactions[$tipo]['emoji'] }}</span>
                            <span>{{ $count }}</span>
                        </button>
                    @endif
                @endforeach
            @else
                <!-- Visão Consolidada (Painel Pessoal): exibe reações recebidas nas comunidades em modo leitura -->
                @forelse($reactionsBreakdown as $tipo => $count)
                    @if($count > 0 && isset($predefinedReactions[$tipo]))
                        <span class="reaction-chip" style="cursor: default;"
                            title="Total de reações '{{ $predefinedReactions[$tipo]['label'] }}' recebidas nas comunidades">
                            <span>{{ $predefinedReactions[$tipo]['emoji'] }}</span>
                            <span>{{ $count }}</span>
                        </span>
                    @endif
                @empty
                    <span
                        style="font-size: 11.5px; color: var(--text-muted); display: inline-flex; align-items: center; gap: 5px;">
                        <x-icon name="sparkles" size="12" />
                        <span>Interaja pelos murais das comunidades</span>
                    </span>
                @endforelse
            @endif
        </div>

        <!-- Botão Toggle de Comentários -->
        <button type="button" wire:click="toggleComments"
            class="btn btn-ghost comments-toggle-btn {{ $showComments ? 'active' : '' }}">
            <x-icon name="message-square" size="13" />
            <span>
                @if($commentsCount === 0)
                    {{ $canInteract ? 'Comentar' : '0 comentários' }}
                @else
                    {{ $commentsCount }} {{ $commentsCount === 1 ? 'comentário' : 'comentários' }}
                @endif
            </span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- 7. SEÇÃO EXPANSÍVEL DE COMENTÁRIOS                                        -->
    <!-- ========================================================================= -->
    @if($showComments)
        <div class="comment-section"
            style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-subtle);">

            @if($comments->isEmpty())
                <div class="comment-empty"
                    style="font-size: 12.5px; color: var(--text-muted); text-align: center; padding: 14px 0;">
                    {{ $canInteract ? 'Nenhum comentário ainda. Deixe um incentivo para o colega de estudos!' : 'Nenhum comentário recebido nas comunidades para esta sessão ainda.' }}
                </div>
            @else
                <div class="comment-list" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                    @foreach($comments as $comment)
                        @php
                            $ehAutorComentario = auth()->id() === $comment->user_id;
                            $emEdicao = $editingCommentId === $comment->id;
                            $emExclusao = $deletingCommentId === $comment->id;
                        @endphp

                        <div class="comment-item"
                            style="display: flex; gap: 10px; padding: 10px 12px; border-radius: var(--radius-md); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle);">
                            <x-avatar :user="$comment->usuario ?? $comment->user" size="xs" style="border-radius: 50%;" />

                            <div class="comment-body" style="flex: 1; min-width: 0;">
                                <div class="comment-header"
                                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="comment-author"
                                            style="font-size: 12.5px; font-weight: 600; color: var(--text-primary);">
                                            {{ $comment->usuario?->nome ?? $comment->user?->name ?? 'Estudante' }}
                                        </span>
                                        @if(!$canInteract && $comment->grupo)
                                            <a href="{{ route('comunidades.show', ['grupo' => $comment->grupo->id]) }}"
                                                style="font-size: 10.5px; font-weight: 500; color: var(--color-primary); background-color: var(--color-primary-subtle); border: 1px solid var(--color-primary-border); padding: 1px 6px; border-radius: var(--radius-sm); text-decoration: none;">
                                                {{ $comment->grupo->nome }}
                                            </a>
                                        @endif
                                        <span class="comment-time" style="font-size: 11px; color: var(--text-muted);">
                                            {{ $comment->created_at ? $comment->created_at->diffForHumans() : '' }}
                                        </span>
                                    </div>

                                    @if($ehAutorComentario && !$emEdicao && !$emExclusao)
                                        <div class="comment-actions" style="display: flex; align-items: center; gap: 4px;">
                                            <button type="button" wire:click="startEditComment({{ $comment->id }})" class="btn-icon"
                                                style="padding: 2px; color: var(--text-muted);" title="Editar comentário">
                                                <x-icon name="edit" size="12" />
                                            </button>
                                            <button type="button" wire:click="confirmDeleteComment({{ $comment->id }})"
                                                class="btn-icon btn-icon-danger" style="padding: 2px;" title="Excluir comentário">
                                                <x-icon name="trash" size="12" />
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                @if($emEdicao)
                                    <div class="comment-edit-box" style="margin-top: 6px;">
                                        <textarea wire:model="editingCommentText" rows="2" class="input"
                                            style="font-size: 12px; padding: 6px 8px; resize: vertical;"></textarea>

                                        @if($editCommentError)
                                            <span style="font-size: 11px; color: var(--color-danger); display: block; margin-top: 2px;">
                                                {{ $editCommentError }}
                                            </span>
                                        @endif

                                        <div class="comment-edit-actions"
                                            style="display: flex; justify-content: flex-end; gap: 6px; margin-top: 6px;">
                                            <button type="button" wire:click="cancelEditComment" class="btn btn-ghost btn-sm"
                                                style="font-size: 11px; padding: 3px 8px;">
                                                Cancelar
                                            </button>
                                            <button type="button" wire:click="saveEditComment" class="btn btn-primary btn-sm"
                                                style="font-size: 11px; padding: 3px 8px;">
                                                Salvar
                                            </button>
                                        </div>
                                    </div>
                                @elseif($emExclusao)
                                    <div class="comment-delete-confirm"
                                        style="margin-top: 4px; padding: 6px 8px; background-color: var(--color-danger-subtle); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between;">
                                        <span style="font-size: 11.5px; color: var(--color-danger);">Excluir este comentário?</span>
                                        <div style="display: flex; gap: 4px;">
                                            <button type="button" wire:click="cancelDeleteComment" class="btn btn-ghost btn-sm"
                                                style="font-size: 10.5px; padding: 2px 6px;">
                                                Cancelar
                                            </button>
                                            <button type="button" wire:click="deleteComment({{ $comment->id }})"
                                                class="btn btn-danger btn-sm" style="font-size: 10.5px; padding: 2px 6px;">
                                                Excluir
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <p class="comment-text"
                                        style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.4; margin: 3px 0 0 0; word-break: break-word;">
                                        {{ trim($comment->conteudo ?? $comment->content) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Formulário de Envio de Novo Comentário (exclusivo dentro de uma comunidade) -->
            @if($canInteract)
                <form wire:submit.prevent="addComment" class="comment-form"
                    style="display: flex; flex-direction: column; gap: 6px; margin-top: 6px;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <x-avatar :user="auth()->user()" size="sm" style="border-radius: 50%;" />

                        <input type="text" wire:model="newCommentText" placeholder="Deixe uma mensagem de incentivo..."
                            class="input comment-input"
                            style="flex: 1; font-size: 12.5px; padding: 8px 12px; border-radius: var(--radius-full);" required>

                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm"
                            style="padding: 8px 16px; border-radius: var(--radius-full); font-weight: 500;">
                            <span wire:loading.remove wire:target="addComment" class="btn-content">Enviar</span>
                            <span wire:loading wire:target="addComment" class="btn-content" style="gap: 4px;">
                                <x-icon name="loader" size="12" class="spin" />
                                <span>...</span>
                            </span>
                        </button>
                    </div>

                    @if($commentError)
                        <div
                            style="display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--color-danger); margin-left: 38px;">
                            <x-icon name="alert-circle" size="12" />
                            <span>{{ $commentError }}</span>
                        </div>
                    @endif
                </form>
            @else
                <div style="font-size: 11.5px; color: var(--text-muted); text-align: center; padding: 6px 0 2px 0;">
                    Para comentar ou reagir, acesse o mural da comunidade correspondente.
                </div>
            @endif

        </div>
    @endif

</article>