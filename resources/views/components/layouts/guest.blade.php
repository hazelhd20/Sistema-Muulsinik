<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>


    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen bg-surface-main flex items-center justify-center p-6">

    <div class="w-full max-w-[22rem]">

        {{-- Brand --}}
        <div class="flex flex-col items-center mb-7">
            <img src="{{ asset('images/logo_muulsinik.svg') }}"
                 alt="Muulsinik ERP"
                 class="object-contain mb-1"
                 style="height: clamp(2.5rem, 4vw + 1rem, 3.5rem);">
            <p class="text-xs-fluid text-text-muted font-medium tracking-wider uppercase mt-2">
                Sistema de Gestión Operativa
            </p>
        </div>

        {{ $slot }}

        <p class="text-center text-xs-fluid text-text-muted opacity-75 tracking-wider mt-5">
            MUULSINIK ERP &nbsp;·&nbsp; {{ date('Y') }} &nbsp;·&nbsp; v1.0
        </p>
    </div>

    @livewireScripts
</body>

</html>
