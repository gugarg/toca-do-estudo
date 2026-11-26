<div>
    @if($isOpen && $photoUrl)
        <div class="modal-overlay animacao-surgir"
             style="z-index: 60; background-color: rgba(12, 14, 18, 0.92);"
             wire:click="close"
             x-data
             @keydown.window.escape="$wire.close()">
            <div style="position: relative; max-width: 900px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; align-items: center;"
                 @click.stop>
                <div style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-hours">{{ $subject }}</span>
                        <span style="font-size: 12.5px; color: var(--text-secondary);">Comprovação de Estudo (WebP)</span>
                    </div>
                    <button type="button" wire:click="close" class="btn btn-ghost" style="padding: 4px;">
                        <x-icon name="x" size="20" />
                    </button>
                </div>
                <img src="{{ $photoUrl }}"
                     alt="{{ $subject }}"
                     style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); box-shadow: var(--shadow-modal);">
            </div>
        </div>
    @endif
</div>
