<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistema de Gestión para Constructora Muulsinik — ERP v1">

    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

    {{-- Lucide Icons CDN --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen" x-data="{ mobileSidebarOpen: false }">

    <div class="flex min-h-screen">

        {{-- ══════════════════════════════════════
             SIDEBAR — Industrial Premium Minimal
        ══════════════════════════════════════ --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-[15rem] bg-surface-sidebar flex flex-col
                   border-r border-border
                   transition-transform duration-200 ease-out
                   lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
            :class="mobileSidebarOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full lg:translate-x-0'">

            {{-- Brand ──────────────────────────────── --}}
            <div class="flex items-center justify-center h-14 border-b border-border shrink-0 px-5">
                <img src="{{ asset('images/logo_muulsinik.svg') }}"
                     alt="Muulsinik ERP"
                     class="object-contain"
                     style="height: var(--logo-size);">
            </div>

            {{-- Navigation ──────────────────────────── --}}
            <nav class="flex-1 px-3 pt-4 pb-2 overflow-y-auto space-y-0.5">

                <p class="nav-section-label">Principal</p>

                <a href="{{ url('/dashboard') }}"
                   class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->hasPermission('proyectos.ver'))
                <a href="{{ url('/proyectos') }}"
                   class="nav-link {{ request()->is('proyectos*') ? 'active' : '' }}">
                    <i data-lucide="hard-hat" class="w-4 h-4 shrink-0"></i>
                    <span>Proyectos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('gastos.ver'))
                <a href="{{ url('/gastos') }}"
                   class="nav-link {{ request()->is('gastos*') ? 'active' : '' }}">
                    <i data-lucide="wallet" class="w-4 h-4 shrink-0"></i>
                    <span>Gastos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('requisiciones.ver'))
                <a href="{{ url('/requisiciones') }}"
                   class="nav-link {{ request()->is('requisiciones*') ? 'active' : '' }}">
                    <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                    <span>Requisiciones</span>
                </a>
                @endif

                <p class="nav-section-label mt-4">Administración</p>

                @if(auth()->user()->hasPermission('proveedores.ver'))
                <a href="{{ url('/proveedores') }}"
                   class="nav-link {{ request()->is('proveedores*') ? 'active' : '' }}">
                    <i data-lucide="truck" class="w-4 h-4 shrink-0"></i>
                    <span>Proveedores</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('reportes.ver'))
                <a href="{{ url('/reportes') }}"
                   class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 shrink-0"></i>
                    <span>Reportes</span>
                </a>
                @endif

                <p class="nav-section-label mt-4">Catálogos</p>

                @if(auth()->user()->hasPermission('productos.ver'))
                <a href="{{ url('/productos') }}"
                   class="nav-link {{ request()->is('productos*') ? 'active' : '' }}">
                    <i data-lucide="package" class="w-4 h-4 shrink-0"></i>
                    <span>Productos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('catalogos.ver'))
                <a href="{{ url('/medidas') }}"
                   class="nav-link {{ request()->is('medidas*') ? 'active' : '' }}">
                    <i data-lucide="ruler" class="w-4 h-4 shrink-0"></i>
                    <span>Medidas</span>
                </a>
                <a href="{{ url('/categorias') }}"
                   class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                    <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                    <span>Categorías</span>
                </a>
                @endif
            </nav>

            {{-- Bottom ──────────────────────────────── --}}
            <div class="px-3 py-3 border-t border-border space-y-0.5 shrink-0">

                {{-- User profile row --}}
                <div class="flex items-center gap-2.5 px-2.5 py-2 mb-1">
                    <div class="w-7 h-7 rounded-md bg-primary-600 flex items-center justify-center shrink-0">
                        <span class="text-xs-fluid font-bold text-white leading-none">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs-fluid font-semibold text-text-primary truncate leading-tight">
                            {{ auth()->user()->name ?? 'Usuario' }}
                        </p>
                        <p class="text-xs-fluid text-text-muted truncate leading-tight" style="font-size: 0.625rem;">
                            {{ auth()->user()->role->name ?? 'Sin rol' }}
                        </p>
                    </div>
                </div>

                <a href="{{ url('/configuracion') }}" class="nav-link">
                    <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                    <span>Configuración</span>
                </a>

                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit"
                        class="nav-link w-full text-left group hover:bg-red-50 hover:!text-red-600">
                        <i data-lucide="log-out" class="w-4 h-4 shrink-0 group-hover:text-red-500"></i>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="mobileSidebarOpen"
             x-transition:enter="transition-opacity duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileSidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/20 backdrop-blur-[2px] lg:hidden"
             style="display: none;"></div>

        {{-- ══════════════════════════════════════
             MAIN CONTENT AREA
        ══════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Top Bar ──────────────────────────── --}}
            <header class="sticky top-0 z-20 bg-surface-main border-b border-border">
                <div class="flex items-center justify-between h-14 px-5 lg:px-6 max-w-screen-2xl mx-auto w-full">

                    {{-- Left: Hamburger (mobile) + Global Search --}}
                    <div class="flex items-center gap-3">
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen"
                            class="lg:hidden p-1.5 rounded-md text-text-secondary hover:bg-surface-hover transition">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>

                        <livewire:global-search />
                    </div>

                    {{-- Right: notifications --}}
                    <div class="flex items-center gap-1">
                        <livewire:notification-dropdown />
                    </div>

                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-5 lg:p-6 max-w-screen-2xl mx-auto w-full">
                {{-- Global flash toast handler --}}
                @if(session('success'))
                    <div x-data x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' }); $el.remove()" wire:key="global-toast-success-{{ microtime(true) }}"></div>
                @endif
                @if(session('error'))
                    <div x-data x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, icon: 'error', title: '{{ session('error') }}' }); $el.remove()" wire:key="global-toast-error-{{ microtime(true) }}"></div>
                @endif
                @if(session('budget_alert'))
                    <div x-data x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 5000, timerProgressBar: true, icon: 'warning', title: '{{ session('budget_alert') }}' }); $el.remove()" wire:key="global-toast-warning-{{ microtime(true) }}"></div>
                @endif

                {{ $slot }}
            </main>

        </div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
        document.addEventListener('livewire:navigated', () => lucide.createIcons());
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => lucide.createIcons());
        });

        window.addEventListener('toast', event => {
            const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: data.icon || 'success',
                title: data.title || data.message
            });
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('[wire\\:confirm]');
            if (el && !el.hasAttribute('data-confirmed')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: el.getAttribute('wire:confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0230c8',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-xl shadow-xl',
                        confirmButton: '!rounded-md !text-sm !font-semibold',
                        cancelButton: '!rounded-md !text-sm !font-semibold'
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        el.setAttribute('data-confirmed', 'true');
                        const orig = window.confirm;
                        window.confirm = () => true;
                        el.click();
                        window.confirm = orig;
                        el.removeAttribute('data-confirmed');
                    }
                });
            }
        }, true);
    </script>
</body>

</html>