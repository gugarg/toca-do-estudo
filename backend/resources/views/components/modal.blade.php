@props([
    'show' => false,
    'title' => '',
    'subtitle' => null,
    'onClose' => null,
    'maxWidth' => 'md',
])

@php
    $maxW = match($maxWidth) {
        'sm' => '420px',
        'lg' => '720px',
        default => '560px',
    };
@endphp

@if($show)
    <div class="modal-overlay"
         @if($onClose) wire:click="{{ $onClose }}" @endif
         x-data
         @if($onClose) @keydown.window.escape="$wire.{{ $onClose }}()" @endif>
        <div class="modal-content"
             style="max-width: {{ $maxW }}; width: 100%; display: flex; flex-direction: column; overflow: hidden;"
             @click.stop>
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em; margin: 0;">
                        {{ $title }}
                    </h3>
                    @if($subtitle)
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px; margin-bottom: 0;">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>

                @if($onClose)
                    <button type="button"
                            wire:click="{{ $onClose }}"
                            class="btn btn-ghost"
                            style="padding: 4px;"
                            title="Fechar">
                        <x-icon name="x" size="18" />
                    </button>
                @endif
            </div>

            <!-- Corpo e Rodapé -->
            {{ $slot }}

            @if(isset($footer))
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
@endif
