<x-modal :show="$showLeaveModal"
         title="Deixar Comunidade de Estudo"
         onClose="closeLeaveModal"
         maxWidth="sm">
    <div class="modal-body">
        <x-alert type="danger" :message="$modalError" />

        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
            Tem certeza de que deseja sair de <strong style="color: var(--text-primary);">{{ $currentGroup?->nome }}</strong>?
            Você deixará de visualizar o mural social e não participará da tabela de classificação desta comunidade.
        </p>
    </div>

    <div class="modal-footer">
        <button type="button" wire:click="closeLeaveModal" class="btn btn-secondary">
            Cancelar
        </button>
        <x-button-loading text="Sim, Sair do Grupo"
                          loadingText="Processando..."
                          variant="danger"
                          type="button"
                          wire:click="leaveGroup"
                          target="leaveGroup" />
    </div>
</x-modal>
