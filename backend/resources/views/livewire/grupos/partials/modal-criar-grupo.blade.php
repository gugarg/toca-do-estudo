<x-modal :show="$showCreateModal"
         title="Criar Comunidade de Estudo"
         subtitle="Fundar um novo grupo com código de convite mnemônico único."
         onClose="closeCreateModal"
         maxWidth="md">
    <form wire:submit="createGroup" style="display: flex; flex-direction: column; overflow: hidden; margin: 0; flex: 1;">
        <!-- Corpo Rolável do Modal -->
        <div class="modal-body">
            <x-alert type="danger" :message="$modalError" />

            <!-- Campo de texto para informar o nome da comunidade de estudo -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="newGroupName">Nome da Comunidade</label>
                <input wire:model="newGroupName"
                       type="text"
                       id="newGroupName"
                       class="form-input"
                       placeholder="Ex: Toca dos Concursos 2026"
                       required />
                @error('newGroupName')
                    <span style="font-size: 11px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Campo de texto opcional para descrever os objetivos do grupo -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="newGroupDescription">Descrição / Metas (Opcional)</label>
                <textarea wire:model="newGroupDescription"
                          id="newGroupDescription"
                          class="form-input"
                          rows="2"
                          placeholder="Ex: Foco total no concurso da Receita Federal. Meta diária: 6h líquidas."
                          style="resize: vertical;"></textarea>
                @error('newGroupDescription')
                    <span style="font-size: 11px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Modalidade de Competição Acadêmica -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Modalidade de Competição</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                    @foreach([
                        'dias_ativos' => ['label' => 'Dias Ativos (Consistência)', 'desc' => 'Avalia dias de estudo cumpridos (independente de horas ou questões).'],
                        'horas_questoes' => ['label' => 'Horas Líquidas + Questões', 'desc' => 'Pontuação calculada por fórmula com pesos customizados.'],
                    ] as $mKey => $mInfo)
                        @php
                            $isMetricSelected = ($newGroupMetric === $mKey)
                                || ($mKey === 'dias_ativos' && in_array($newGroupMetric, ['active_days', 'dias_ativos'], true))
                                || ($mKey === 'horas_questoes' && in_array($newGroupMetric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points'], true));
                        @endphp
                        <button type="button"
                                wire:click="$set('newGroupMetric', '{{ $mKey }}')"
                                style="padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid; border-color: {{ $isMetricSelected ? 'var(--border-focus)' : 'var(--border-subtle)' }}; background-color: {{ $isMetricSelected ? 'var(--color-primary-subtle)' : 'var(--bg-surface-subtle)' }}; cursor: pointer; text-align: left;">
                            <div style="font-size: 13px; font-weight: 600; color: {{ $isMetricSelected ? 'var(--color-primary)' : 'var(--text-primary)' }};">
                                {{ $mInfo['label'] }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                {{ $mInfo['desc'] }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            @php
                $isConfigurandoPontos = in_array($newGroupMetric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points'], true);
            @endphp
            @if($isConfigurandoPontos)
                <div style="background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 12px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="font-size: 11.5px; color: #93c5fd; display: flex; align-items: center; gap: 6px;">
                        <x-icon name="alert-circle" size="14" />
                        <span>Defina os pesos da pontuação. Eles serão <strong>imutáveis</strong> após a criação.</span>
                    </div>

                    <div class="modal-grid-2">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="newGroupPointsPerHour" style="font-size: 12px;">Pontos por Hora Líquida</label>
                            <input wire:model="newGroupPointsPerHour"
                                   type="number"
                                   step="0.01"
                                   min="0.01"
                                   id="newGroupPointsPerHour"
                                   class="form-input"
                                   placeholder="Ex: 10.0"
                                   required />
                            @error('newGroupPointsPerHour')
                                <span style="font-size: 11px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="newGroupPointsPerQuestion" style="font-size: 12px;">Pontos por Questão</label>
                            <input wire:model="newGroupPointsPerQuestion"
                                   type="number"
                                   step="0.01"
                                   min="0.01"
                                   id="newGroupPointsPerQuestion"
                                   class="form-input"
                                   placeholder="Ex: 1.0"
                                   required />
                            @error('newGroupPointsPerQuestion')
                                <span style="font-size: 11px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">
                        Fórmula: Pontuação = (horas × {{ (float) ($newGroupPointsPerHour ?: 0) }}) + (questões × {{ (float) ($newGroupPointsPerQuestion ?: 0) }})
                    </div>
                </div>
            @endif
        </div>

        <!-- Rodapé Fixo do Modal -->
        <div class="modal-footer">
            <button type="button" wire:click="closeCreateModal" class="btn btn-secondary">
                Cancelar
            </button>
            <x-button-loading text="Fundar Grupo"
                              loadingText="Criando..."
                              icon="plus" />
        </div>
    </form>
</x-modal>
