<x-modal :show="$showEditModal"
         title="Configurações da Comunidade"
         subtitle="Gerencie as informações e o nome do seu grupo de estudos."
         onClose="closeEditModal"
         maxWidth="md">
    <form wire:submit.prevent="updateGroupSettings">
        <div class="modal-body" style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
            <x-alert type="danger" :message="$modalError" />

            <!-- Nome da Comunidade -->
            <div>
                <label for="editGroupName" class="label">
                    Nome da Comunidade <span style="color: var(--color-danger);">*</span>
                </label>
                <input type="text"
                       id="editGroupName"
                       wire:model="editGroupName"
                       placeholder="Ex: Concurso Auditor Fiscal 2026"
                       class="input @error('editGroupName') input-error @enderror"
                       required
                       maxlength="100">
                @error('editGroupName')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Descrição da Comunidade -->
            <div>
                <label for="editGroupDescription" class="label">Descrição (Opcional)</label>
                <textarea id="editGroupDescription"
                          wire:model="editGroupDescription"
                          rows="3"
                          placeholder="Descreva o propósito, foco ou cronograma do grupo..."
                          class="input @error('editGroupDescription') input-error @enderror"
                          style="resize: vertical; font-family: inherit; font-size: 13px;"
                          maxlength="500"></textarea>
                @error('editGroupDescription')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Modalidade e Regras (Imutáveis) -->
            <div style="background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 12px 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-primary);">
                        Modalidade de Pontuação
                    </span>
                    <span class="badge badge-hours" style="font-size: 11px;">
                        @if($currentGroup && in_array($currentGroup->metrica_padrao, [\App\Models\Grupo::MODALIDADE_HORAS_QUESTOES, 'hours_and_questions', 'pontos', 'points', 'hours', 'questions'], true))
                            Horas Líquidas + Questões
                        @else
                            Dias Ativos
                        @endif
                    </span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin: 0; line-height: 1.4;">
                    🔒 Os pesos e a modalidade são imutáveis após a criação para resguardar a integridade histórica dos rankings.
                </p>
            </div>
        </div>

        <div class="modal-footer" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border-subtle); background-color: var(--bg-surface);">
            <button type="button" wire:click="closeEditModal" class="btn btn-secondary">
                Cancelar
            </button>
            <x-button-loading text="Salvar Alterações"
                              loadingText="Salvando..."
                              variant="primary"
                              type="submit"
                              target="updateGroupSettings" />
        </div>
    </form>
</x-modal>
