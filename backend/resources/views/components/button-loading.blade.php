@props([
    'text' => 'Salvar',
    'loadingText' => 'Processando...',
    'icon' => null,
    'type' => 'submit',
    'variant' => 'primary',
    'class' => '',
    'target' => null,
    'fullWidth' => false,
])

@php
    $btnClass = match($variant) {
        'secondary' => 'btn btn-secondary',
        'danger' => 'btn btn-danger',
        'ghost' => 'btn btn-ghost',
        default => 'btn btn-primary',
    };

    $widthStyle = $fullWidth ? 'width: 100%;' : '';
@endphp

<button type="{{ $type }}"
        {{ $attributes->merge([
            'class' => "{$btnClass} {$class}",
            'style' => "{$widthStyle} position: relative;",
            'wire:loading.attr' => 'disabled',
        ]) }}
        @if($target) wire:target="{{ $target }}" @endif>
    <span wire:loading.remove @if($target) wire:target="{{ $target }}" @endif class="btn-content" style="gap: 6px;">
        @if($icon)
            <x-icon name="{{ $icon }}" size="15" />
        @endif
        <span>{{ $text }}</span>
    </span>

    <span wire:loading @if($target) wire:target="{{ $target }}" @endif class="btn-content" style="gap: 6px;">
        <x-icon name="loader-2" size="15" class="spin" />
        <span>{{ $loadingText }}</span>
    </span>
</button>
