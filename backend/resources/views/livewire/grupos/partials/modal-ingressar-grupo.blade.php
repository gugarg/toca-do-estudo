<x-modal :show="$showJoinModal"
         title="Ingressar em Comunidade"
         subtitle="Digite o código de convite fornecido pelos membros do grupo."
         onClose="closeJoinModal"
         maxWidth="sm">
    <form wire:submit="joinGroup" style="display: flex; flex-direction: column; overflow: hidden; margin: 0; flex: 1;">
        <div class="modal-body">
            <x-alert type="danger" :message="$modalError" />

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="inviteCode">Código de Convite</label>
                <input wire:model="inviteCode"
                       type="text"
                       id="inviteCode"
                       class="form-input"
                       placeholder="Ex: TOCA-FISC26"
                       style="font-family: monospace; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;"
                       required />
                @error('inviteCode')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" wire:click="closeJoinModal" class="btn btn-secondary">
                Cancelar
            </button>
            <x-button-loading text="Ingressar"
                              loadingText="Ingressando..."
                              icon="key-round" />
        </div>
    </form>
</x-modal>
