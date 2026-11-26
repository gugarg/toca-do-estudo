<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Toca do Estudo' }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column; background-color: var(--bg-app); color: var(--text-primary);">
    @include('layouts.navigation')

    <div style="flex: 1; display: flex; flex-direction: column; width: 100%; min-width: 0; box-sizing: border-box;">
        {{ $slot }}
    </div>

    @livewire('componentes.visualizador-foto')
    @livewire('sessoes-estudo.modal-criar-sessao')

    @livewireScripts
</body>
</html>
