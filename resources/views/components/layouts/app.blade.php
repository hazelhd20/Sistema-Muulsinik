<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistema de Gestión para Constructora Muulsinik — ERP v1">

    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    {{-- Fonts --}}


    {{-- Lucide Icons were replaced by blade-lucide-icons --}}



    {{-- Alpine Focus Plugin (RF-REQ-10: Focus Trap) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>

    {{-- Alpine Anchor Plugin (Dropdowns & Floating UI) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/anchor@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen" x-data="{ mobileSidebarOpen: false }">
    <script>
        // Capturar Ctrl+K antes de que Chrome lo intercepte
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                e.stopPropagation();
                var el = document.getElementById('global-search-input');
                if (el) {
                    el.focus();
                    el.dispatchEvent(new Event('focus'));
                }
            }
        }, true);
    </script>

    <div wire:loading class="fixed top-0 inset-x-0 h-1 bg-primary-600 progress-indeterminate z-50"></div>

    <div class="flex min-h-screen">

        {{-- ══════════════════════════════════════
        SIDEBAR — Industrial Premium Minimal
        ══════════════════════════════════════ --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-[15rem] bg-surface-sidebar flex flex-col
                   transition-transform duration-200 ease-out
                   lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
            :class="mobileSidebarOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full lg:translate-x-0'">

            {{-- Brand ──────────────────────────────── --}}
            <div class="flex items-center justify-center h-14 shrink-0 px-5">
                <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="object-contain"
                    style="height: var(--logo-size);">
            </div>

            {{-- Navigation ──────────────────────────── --}}
            <nav class="flex-1 px-3 pt-4 pb-2 overflow-y-auto space-y-0.5">

                <p class="nav-section-label">Principal</p>

                <a href="{{ url('/dashboard') }}" wire:navigate class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <x-lucide-layout-dashboard class="w-4 h-4 shrink-0" aria-hidden="true" />
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->hasPermission('proyectos.ver'))
                    <a href="{{ url('/proyectos') }}" wire:navigate class="nav-link {{ request()->is('proyectos*') ? 'active' : '' }}">
                        <x-lucide-hard-hat class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Proyectos</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('gastos.ver'))
                    <a href="{{ url('/gastos') }}" wire:navigate class="nav-link {{ request()->is('gastos*') ? 'active' : '' }}">
                        <x-lucide-wallet class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Gastos</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('requisiciones.ver'))
                    <a href="{{ url('/requisiciones') }}" wire:navigate
                        class="nav-link {{ request()->is('requisiciones*') ? 'active' : '' }}">
                        <x-lucide-clipboard-list class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Requisiciones</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('proyectos.crear') || auth()->user()->hasPermission('requisiciones.crear'))
                    <a href="{{ route('cotizador.index') }}" wire:navigate
                        class="nav-link {{ request()->routeIs('cotizador.*') ? 'active' : '' }}">
                        <x-lucide-calculator class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Cotizador</span>
                    </a>
                @endif

                <p class="nav-section-label mt-4">Administración</p>

                @if(auth()->user()->hasPermission('usuarios.ver'))
                    <a href="{{ url('/usuarios') }}" wire:navigate
                        class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}">
                        <x-lucide-users class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Usuarios</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('proveedores.ver'))
                    <a href="{{ url('/proveedores') }}" wire:navigate
                        class="nav-link {{ request()->is('proveedores*') ? 'active' : '' }}">
                        <x-lucide-truck class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Proveedores</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('reportes.ver'))
                    <a href="{{ url('/reportes') }}" wire:navigate class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}">
                        <x-lucide-bar-chart-3 class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Reportes</span>
                    </a>
                @endif

                <p class="nav-section-label mt-4">Catálogos</p>

                @if(auth()->user()->hasPermission('productos.ver'))
                    <a href="{{ url('/productos') }}" wire:navigate class="nav-link {{ request()->is('productos*') ? 'active' : '' }}">
                        <x-lucide-package class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Productos</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('catalogos.ver'))
                    <a href="{{ url('/medidas') }}" wire:navigate class="nav-link {{ request()->is('medidas*') ? 'active' : '' }}">
                        <x-lucide-ruler class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Medidas</span>
                    </a>
                    <a href="{{ url('/categorias') }}" wire:navigate class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                        <x-lucide-layers class="w-4 h-4 shrink-0" aria-hidden="true" />
                        <span>Categorías</span>
                    </a>
                @endif
            </nav>

            {{-- Bottom ──────────────────────────────── --}}
            <div class="px-3 py-3 space-y-0.5 shrink-0">

                {{-- User profile row --}}
                <div class="flex items-center gap-2.5 px-2.5 py-2 mb-1">
                    <div class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-white leading-none">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-text-primary truncate leading-tight">
                            {{ auth()->user()->name ?? 'Usuario' }}
                        </p>
                        <p class="text-xs text-text-muted truncate leading-tight">
                            {{ auth()->user()->role->name ?? 'Sin rol' }}
                        </p>
                    </div>
                </div>

                <button x-data x-on:click="$dispatch('open-settings')" class="nav-link w-full text-left cursor-pointer">
                    <x-lucide-settings class="w-4 h-4 shrink-0" aria-hidden="true" />
                    <span>Configuración</span>
                </button>

                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="nav-link w-full text-left group hover:bg-danger-light hover:!text-danger">
                        <x-lucide-log-out class="w-4 h-4 shrink-0 group-hover:text-danger-hover" aria-hidden="true" />
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="mobileSidebarOpen = false"
            class="fixed inset-0 z-45 bg-black/40 backdrop-blur-[2px] lg:hidden" x-cloak></div>

        {{-- ══════════════════════════════════════
        MAIN CONTENT AREA
        ══════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Top Bar ──────────────────────────── --}}
            <header class="sticky top-0 z-35">
                <div class="flex items-center justify-between h-14 px-5 lg:px-6 max-w-screen-2xl mx-auto w-full">

                    {{-- Left: Hamburger (mobile) --}}
                    <div class="flex items-center gap-3">
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen"
                            class="lg:hidden p-1.5 rounded-md text-text-secondary hover:bg-surface-hover transition">
                            <x-lucide-menu class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Right: Global Search + Notifications --}}
                    <div class="flex items-center gap-3">
                        <livewire:global-search />
                        <livewire:notification-dropdown />
                    </div>

                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-5 lg:p-6 max-w-screen-2xl mx-auto w-full">
                {{-- Global flash toast handler --}}
                @if(session('success'))
                    <div x-data
                        x-init="$dispatch('toast', { icon: 'success', title: '{{ session('success') }}', duration: 3000 }); $el.remove()"
                        wire:key="global-toast-success-{{ microtime(true) }}"></div>
                @endif
                @if(session('error'))
                    <div x-data
                        x-init="$dispatch('toast', { icon: 'error', title: '{{ session('error') }}', duration: 4000 }); $el.remove()"
                        wire:key="global-toast-error-{{ microtime(true) }}"></div>
                @endif
                @if(session('budget_alert'))
                    <div x-data
                        x-init="$dispatch('toast', { icon: 'warning', title: '{{ session('budget_alert') }}', duration: 5000 }); $el.remove()"
                        wire:key="global-toast-warning-{{ microtime(true) }}"></div>
                @endif

                {{ $slot }}
            </main>

        </div>
    </div>

    <livewire:settings.settings-index />

    @if(session('open_settings') || request()->query('settings') === 'true')
        <div x-data x-init="$nextTick(() => $dispatch('open-settings'))"></div>
    @endif

    @livewireScripts
    <x-toast-container />
    
    <script>
        // No redundant createIcons here, it's handled in app.js
        document.addEventListener('livewire:init', () => {
            // Prevenir el modal de error de Livewire al expirar la sesión (419) o no estar autenticado (401)
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419 || status === 401) {
                        preventDefault();
                        window.location.reload(); // Recargar redirigirá al login gracias al middleware auth
                    }
                });
            });
        });


    </script>
</body>

</html>
