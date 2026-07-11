<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    
    {{-- PWA & Native App Meta Tags --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

    <title>@yield('title') - {{ config('app.name', 'Muulsinik ERP') }}</title>

    {{-- Dark Mode Resilient Init (Cero Parpadeo) --}}
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
        })();
    </script>

    @vite(['resources/css/app.css'])
</head>

<body class="antialiased min-h-screen bg-surface-main flex items-center justify-center p-6 relative overflow-hidden font-sans">

    {{-- Efecto de resplandor sutil de fondo (Glassmorphism / Vibe Premium) --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl opacity-60 dark:opacity-40"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-danger-500/10 rounded-full blur-3xl opacity-60 dark:opacity-40"></div>
    </div>

    {{-- Dark Mode Toggle --}}
    <button type="button"
        onclick="window.toggleTheme()"
        class="group absolute top-4 right-4 sm:top-6 sm:right-6 inline-flex items-center justify-center w-9 h-9 p-2 rounded-lg text-text-muted transition-all duration-200 ease-out active:scale-95 shadow-sm bg-surface-card border border-border/60 cursor-pointer hover:border-border hover:bg-surface-hover"
        title="Cambiar tema visual"
        aria-label="Cambiar tema visual">
        <svg class="w-5 h-5 dark:hidden text-text-muted transition-transform duration-200 group-hover:-rotate-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        <svg class="w-5 h-5 hidden dark:block text-amber-400 transition-transform duration-200 group-hover:rotate-45" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
    </button>

    <div class="w-full max-w-md text-center">

        {{-- Logotipo Corporativo --}}
        <div class="flex flex-col items-center mb-8">
            <a href="{{ url('/') }}" class="inline-block transition-transform hover:scale-[1.02] active:scale-[0.98]">
                <img src="{{ asset('images/logo_muulsinik.svg') }}"
                     alt="Muulsinik ERP"
                     class="app-logo object-contain mx-auto"
                     style="height: clamp(2.25rem, 4vw + 1rem, 3.25rem);">
            </a>
            <p class="text-xs text-text-muted font-medium tracking-wider uppercase mt-2.5 opacity-80">
                Sistema de Gestión Operativa
            </p>
        </div>

        {{-- Tarjeta de Error Principal --}}
        <div class="bg-surface-card/95 backdrop-blur-md border border-border/80 rounded-2xl p-7 sm:p-8 shadow-xl relative overflow-hidden transition-all duration-300">
            
            {{-- Línea de resplandor superior del card según el tipo de error --}}
            <div class="absolute top-0 left-0 right-0 h-1 @yield('accent-color', 'bg-primary-600 dark:bg-primary-500')"></div>

            {{-- Código de Error & Badge --}}
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-5 @yield('badge-color', 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20')">
                <span>Código</span>
                <span class="w-1 h-1 rounded-full @yield('dot-color', 'bg-primary-500')"></span>
                <span>@yield('code')</span>
            </div>

            {{-- Título --}}
            <h1 class="text-2xl sm:text-3xl font-bold text-text-primary tracking-tight mb-3">
                @yield('title')
            </h1>

            {{-- Descripción --}}
            <p class="text-small text-text-secondary leading-relaxed mb-8 max-w-sm mx-auto">
                @yield('message')
            </p>

            {{-- Acciones --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.history.back()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 text-small font-medium text-text-primary bg-surface-main hover:bg-surface-hover border border-border rounded-xl transition-all duration-150 active:scale-95 cursor-pointer shadow-2xs">
                    <svg class="w-4 h-4 text-text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Regresar</span>
                </button>

                <a href="{{ url('/') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 text-small font-semibold text-white bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 rounded-xl transition-all duration-150 active:scale-95 cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Inicio</span>
                </a>
            </div>

        </div>

        {{-- Pie de página --}}
        <p class="text-center text-xs text-text-muted opacity-70 tracking-wider mt-6">
            MUULSINIK ERP &nbsp;·&nbsp; {{ date('Y') }} &nbsp;·&nbsp; SOPORTE TÉCNICO
        </p>

    </div>

</body>
</html>
