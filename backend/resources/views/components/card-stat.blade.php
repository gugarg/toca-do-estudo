@props([
    'titulo' => null,
    'title' => null,
    'valor' => null,
    'value' => null,
    'descricao' => null,
    'helper' => null,
    'icone' => null,
    'icon' => null,
    'corIcone' => null,
    'accent' => null,
    'unit' => null,
])

@php
    $tituloEfetivo = $titulo ?? $title ?? '';
    $valorEfetivo = $valor ?? $value ?? '0';
    $descricaoEfetiva = $descricao ?? $helper ?? null;
    $iconeEfetivo = $icone ?? $icon ?? null;
    $corIconeEfetiva = $corIcone ?? match($accent ?? '') {
        'active' => 'var(--metric-active)',
        'hours' => 'var(--metric-hours)',
        'questions' => 'var(--metric-questions)',
        default => 'var(--text-secondary)',
    };
@endphp

<div class="card" style="padding: 16px 18px; display: flex; flex-direction: column; justify-content: space-between;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: 12.5px; font-weight: 500; color: var(--text-secondary); letter-spacing: 0.01em;">
            {{ $tituloEfetivo }}
        </span>
        @if($iconeEfetivo)
            <div style="color: {{ $corIconeEfetiva }}; display: flex; align-items: center;">
                <x-icon :name="$iconeEfetivo" size="18" />
            </div>
        @endif
    </div>

    <div>
        <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em; line-height: 1.1; display: flex; align-items: baseline; gap: 4px;">
            <span>{{ $valorEfetivo }}</span>
            @if($unit)
                <span style="font-size: 13px; font-weight: 500; color: var(--text-muted);">{{ $unit }}</span>
            @endif
        </div>
        @if($descricaoEfetiva)
            <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">
                {{ $descricaoEfetiva }}
            </p>
        @endif
    </div>
</div>
