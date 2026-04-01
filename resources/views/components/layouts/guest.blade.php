<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Iniciar sesión — Sistema de Gestión Muulsinik ERP">

    <title>{{ $title ?? 'Iniciar Sesión' }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased min-h-screen bg-surface-main flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
