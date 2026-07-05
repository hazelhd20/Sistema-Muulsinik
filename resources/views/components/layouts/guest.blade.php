<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>


    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    {{-- Dark Mode Resilient Init & SPA Preservation --}}
    <script>
        (function() {
            window.toggleTheme = function() {
                document.documentElement.classList.add('theme-switching');
                var isDark = document.documentElement.classList.contains('dark');
                if (isDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                setTimeout(function() {
                    document.documentElement.classList.remove('theme-switching');
                }, 150);
                return !isDark;
            };

            function applyTheme() {
                var theme = localStorage.getItem('theme');
                var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
            new MutationObserver(function() {
                var theme = localStorage.getItem('theme');
                var shouldBeDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
                var isDark = document.documentElement.classList.contains('dark');
                if (shouldBeDark && !isDark) {
                    document.documentElement.classList.add('theme-switching');
                    document.documentElement.classList.add('dark');
                    setTimeout(function() { document.documentElement.classList.remove('theme-switching'); }, 150);
                } else if (!shouldBeDark && isDark) {
                    document.documentElement.classList.add('theme-switching');
                    document.documentElement.classList.remove('dark');
                    setTimeout(function() { document.documentElement.classList.remove('theme-switching'); }, 150);
                }
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen bg-surface-main flex items-center justify-center p-6 relative" x-data>

    {{-- Dark Mode Toggle --}}
    <button type="button"
        x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        @click="isDark = window.toggleTheme()"
        class="group absolute top-4 right-4 inline-flex items-center justify-center w-9 h-9 p-2 rounded-lg text-text-muted icon-btn-hover transition-all duration-200 ease-out active:scale-95 shadow-sm bg-surface-card border border-border/60 cursor-pointer"
        :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'">
        <x-lucide-sun x-show="isDark" x-cloak class="w-5 h-5 text-amber-400 transition-transform duration-200 group-hover:rotate-45" />
        <x-lucide-moon x-show="!isDark" x-cloak class="w-5 h-5 text-text-muted transition-transform duration-200 group-hover:-rotate-12" />
    </button>

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
