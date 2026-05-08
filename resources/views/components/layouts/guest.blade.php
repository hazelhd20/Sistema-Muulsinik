<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen bg-surface-main flex">

    {{-- Left panel: branding (hidden on small screens) --}}
    <div class="hidden lg:flex w-[420px] shrink-0 bg-primary-600 flex-col items-start justify-between p-10 relative overflow-hidden">
        {{-- circles --}}
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute bottom-10 -left-10 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10">
            <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="h-10 mb-12 brightness-[200] opacity-90">
            <h1 class="text-3xl font-bold text-white leading-snug mb-3">
                Gestión integral<br>de construcción
            </h1>
            <p class="text-primary-200 text-sm leading-relaxed max-w-xs">
                Controla proyectos, gastos y requisiciones de materiales desde una sola plataforma diseñada para constructoras.
            </p>
        </div>

        <div class="relative z-10 w-full">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-2xl font-bold text-white leading-none mb-1">100%</p>
                    <p class="text-primary-200 text-xs">Control de presupuestos</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-2xl font-bold text-white leading-none mb-1">IA</p>
                    <p class="text-primary-200 text-xs">Extracción de cotizaciones</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right panel: form --}}
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-sm">
            {{-- Mobile logo --}}
            <div class="flex justify-center mb-8 lg:hidden">
                <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="h-10">
            </div>

            {{ $slot }}
        </div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>

</html>
