@props([
    'type' => 'danger',
    'message' => null,
    'dismissible' => false,
    'wireDismiss' => null,
    'icon' => null,
])

@php
    $tipo = match($type) {
        'error', 'danger' => 'danger',
        'success' => 'success',
        'warning' => 'warning',
        default => 'info',
    };

    $config = match($tipo) {
        'danger' => [
            'icon' => $icon ?? 'alert-circle',
            'bg' => 'var(--color-danger-subtle)',
            'border' => 'var(--color-danger-border)',
            'color' => '#fca5a5',
        ],
        'success' => [
            'icon' => $icon ?? 'check-circle-2',
            'bg' => 'var(--color-primary-subtle)',
            'border' => 'var(--color-primary-border)',
            'color' => '#93c5fd',
        ],
        'warning' => [
            'icon' => $icon ?? 'alert-triangle',
            'bg' => 'rgba(245, 158, 11, 0.12)',
            'border' => 'rgba(245, 158, 11, 0.30)',
            'color' => '#fcd34d',
        ],
        default => [
            'icon' => $icon ?? 'info',
            'bg' => 'var(--bg-surface-subtle)',
            'border' => 'var(--border-subtle)',
            'color' => 'var(--text-secondary)',
        ],
    };

    $texto = $message ?? $slot->toHtml();
@endphp

@if(trim($texto) !== '')
    <div {{ $attributes->merge([
        'style' => "background-color: {$config['bg']}; border: 1px solid {$config['border']}; color: {$config['color']}; border-radius: var(--radius-md); padding: 10px 14px; font-size: 12.5px; display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 16px;"
    ]) }}>
        <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
            <x-icon name="{{ $config['icon'] }}" size="16" style="flex-shrink: 0;" />
            <div style="word-break: break-word;">
                {{ $message ?? $slot }}
            </div>
        </div>

        @if($dismissible || $wireDismiss)
            <button type="button"
                    @if($wireDismiss) wire:click="{{ $wireDismiss }}" @endif
                    @if(!$wireDismiss) @click="$el.parentElement.remove()" @endif
                    class="btn btn-ghost"
                    style="padding: 2px; color: inherit; opacity: 0.8; flex-shrink: 0;"
                    title="Fechar">
                <x-icon name="x" size="14" />
            </button>
        @endif
    </div>
@endif
