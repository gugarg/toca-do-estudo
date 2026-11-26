@props([
    'src' => null,
    'name' => null,
    'user' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $usuario = $user;
    $rawSrc = $src ?? ($usuario?->url_avatar ?? $usuario?->avatar_url ?? null);
    $nome = $name ?? ($usuario?->nome ?? $usuario?->name ?? 'Estudante');

    $dimensoes = match($size) {
        'xs' => ['size' => '24px', 'font' => '11px', 'radius' => '50%'],
        'sm' => ['size' => '30px', 'font' => '13px', 'radius' => '50%'],
        'lg' => ['size' => '48px', 'font' => '19px', 'radius' => '50%'],
        'xl' => ['size' => '80px', 'font' => '32px', 'radius' => '50%'],
        default => ['size' => '38px', 'font' => '15px', 'radius' => '50%'],
    };

    $imageUrl = null;
    if ($rawSrc && is_string($rawSrc) && trim($rawSrc) !== '') {
        $imageUrl = (str_starts_with($rawSrc, 'http://') || str_starts_with($rawSrc, 'https://'))
            ? $rawSrc
            : asset('storage/' . ltrim($rawSrc, '/'));
    }

    $inicial = mb_strtoupper(mb_substr(trim($nome ?: 'A'), 0, 1));

    // Paleta de cores com excelente contraste (WCAG AAA) para exibição da inicial
    $paletaCores = [
        ['bg' => '#2563eb', 'color' => '#ffffff'], // Azul Royal
        ['bg' => '#059669', 'color' => '#ffffff'], // Esmeralda
        ['bg' => '#7c3aed', 'color' => '#ffffff'], // Violeta
        ['bg' => '#ea580c', 'color' => '#ffffff'], // Laranja
        ['bg' => '#db2777', 'color' => '#ffffff'], // Magenta
        ['bg' => '#0891b2', 'color' => '#ffffff'], // Ciano
        ['bg' => '#4f46e5', 'color' => '#ffffff'], // Índigo
        ['bg' => '#0d9488', 'color' => '#ffffff'], // Teal
    ];
    $indice = abs(crc32($nome)) % count($paletaCores);
    $tema = $paletaCores[$indice];
@endphp

@if($imageUrl)
    <div style="position: relative; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <img src="{{ $imageUrl }}"
             alt="{{ $nome }}"
             onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-flex';"
             {{ $attributes->merge([
                 'class' => 'avatar-img ' . $class,
                 'style' => "width: {$dimensoes['size']}; height: {$dimensoes['size']}; border-radius: {$dimensoes['radius']}; object-fit: cover; border: 1px solid var(--border-subtle); flex-shrink: 0;"
             ]) }}>

        <!-- Fallback automático com a inicial se a imagem falhar ao carregar -->
        <div class="avatar-placeholder {{ $class }}"
             style="display: none; width: {{ $dimensoes['size'] }}; height: {{ $dimensoes['size'] }}; border-radius: {{ $dimensoes['radius'] }}; background-color: {{ $tema['bg'] }}; color: {{ $tema['color'] }}; font-size: {{ $dimensoes['font'] }}; font-weight: 700; align-items: center; justify-content: center; flex-shrink: 0; user-select: none; border: 1px solid rgba(255,255,255,0.15); line-height: 1;">
            {{ $inicial }}
        </div>
    </div>
@else
    <div {{ $attributes->merge([
        'class' => 'avatar-placeholder ' . $class,
        'style' => "width: {$dimensoes['size']}; height: {$dimensoes['size']}; border-radius: {$dimensoes['radius']}; background-color: {$tema['bg']}; color: {$tema['color']}; font-size: {$dimensoes['font']}; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; user-select: none; border: 1px solid rgba(255,255,255,0.15); line-height: 1;"
    ]) }}>
        {{ $inicial }}
    </div>
@endif
