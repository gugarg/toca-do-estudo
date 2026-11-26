@props([
    'icon' => 'inbox',
    'title' => 'Nenhum registro encontrado',
    'description' => null,
])

<div {{ $attributes->merge([
    'class' => 'card empty-state-container',
    'style' => 'text-align: center; padding: 48px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center;'
]) }}>
    <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); margin-bottom: 14px;">
        <x-icon name="{{ $icon }}" size="22" />
    </div>

    <h2 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; letter-spacing: -0.01em;">
        {{ $title }}
    </h2>

    @if($description)
        <p style="font-size: 13px; color: var(--text-muted); max-width: 420px; margin: 0 auto 20px auto; line-height: 1.5;">
            {{ $description }}
        </p>
    @endif

    {{ $slot }}

    @if(isset($actions))
        <div style="display: inline-flex; align-items: center; gap: 10px; margin-top: 14px; flex-wrap: wrap; justify-content: center;">
            {{ $actions }}
        </div>
    @endif
</div>
