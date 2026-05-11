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

        {{-- ═══════════════════════════════════════════════
             SIDEBAR
        ═══════════════════════════════════════════════ --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-60 bg-surface-card flex flex-col
                   border-r border-gray-100
                   transition-transform duration-300 ease-in-out
                   lg:translate-x-0 lg:static lg:inset-auto"
            :class="mobileSidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'">

            {{-- Brand --}}
            <div class="flex items-center justify-center h-[4.25rem] border-b border-gray-100 shrink-0 px-4">
                <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="object-contain" style="height: var(--logo-size);">
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">

                <p class="px-3 pt-1 pb-2 text-xs-fluid font-700 text-text-muted uppercase tracking-[0.08em]">Principal</p>

                <a href="{{ url('/dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->hasPermission('proyectos.ver'))
                <a href="{{ url('/proyectos') }}" class="nav-link {{ request()->is('proyectos*') ? 'active' : '' }}">
                    <i data-lucide="hard-hat" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Proyectos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('gastos.ver'))
                <a href="{{ url('/gastos') }}" class="nav-link {{ request()->is('gastos*') ? 'active' : '' }}">
                    <i data-lucide="wallet" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Gastos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('requisiciones.ver'))
                <a href="{{ url('/requisiciones') }}" class="nav-link {{ request()->is('requisiciones*') ? 'active' : '' }}">
                    <i data-lucide="clipboard-list" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Requisiciones</span>
                </a>
                @endif

                <p class="px-3 pt-5 pb-2 text-xs-fluid font-700 text-text-muted uppercase tracking-[0.08em]">Administración</p>

                @if(auth()->user()->hasPermission('proveedores.ver'))
                <a href="{{ url('/proveedores') }}" class="nav-link {{ request()->is('proveedores*') ? 'active' : '' }}">
                    <i data-lucide="truck" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Proveedores</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('reportes.ver'))
                <a href="{{ url('/reportes') }}" class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-3" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Reportes</span>
                </a>
                @endif

                <p class="px-3 pt-5 pb-2 text-xs-fluid font-700 text-text-muted uppercase tracking-[0.08em]">Catálogos</p>

                @if(auth()->user()->hasPermission('productos.ver'))
                <a href="{{ url('/productos') }}" class="nav-link {{ request()->is('productos*') ? 'active' : '' }}">
                    <i data-lucide="package" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Productos</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('catalogos.ver'))
                <a href="{{ url('/medidas') }}" class="nav-link {{ request()->is('medidas*') ? 'active' : '' }}">
                    <i data-lucide="ruler" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Medidas</span>
                </a>

                <a href="{{ url('/categorias') }}" class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                    <i data-lucide="layers" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Categorías</span>
                </a>
                @endif
            </nav>

            {{-- Bottom: Settings + Logout --}}
            <div class="px-3 py-4 border-t border-gray-100 space-y-0.5 shrink-0">
                <a href="{{ url('/configuracion') }}" class="nav-link">
                    <i data-lucide="settings" class="w-[18px] h-[18px] shrink-0"></i>
                    <span>Ajustes</span>
                </a>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit"
                        class="nav-link w-full text-left hover:bg-red-50 hover:text-red-600 group">
                        <i data-lucide="log-out" class="w-[18px] h-[18px] shrink-0 text-text-muted group-hover:text-red-500"></i>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-show="mobileSidebarOpen"
             x-transition:enter="transition-opacity duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileSidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/25 backdrop-blur-[2px] lg:hidden"
             style="display: none;"></div>

        {{-- ═══════════════════════════════════════════════
             MAIN CONTENT AREA
        ═══════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-surface-main/90 backdrop-blur-md border-b border-gray-200/60">
                <div class="flex items-center justify-between h-[4.25rem] px-5 lg:px-6">

                    {{-- Left: Hamburger (mobile) + Search --}}
                    <div class="flex items-center gap-3">
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen"
                            class="lg:hidden p-2 rounded-lg text-text-secondary hover:bg-white hover:text-text-primary transition">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>

                        <div class="relative hidden sm:flex items-center">
                            <i data-lucide="search"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none"></i>
                            <input type="search"
                                placeholder="Buscar en el sistema..."
                                class="input pl-10 w-72 bg-white/80 text-small h-9 py-0"
                                id="global-search">
                        </div>
                    </div>

                    {{-- Right: Actions + Profile --}}
                    <div class="flex items-center gap-1.5">
                        <button class="relative p-2 rounded-lg text-text-secondary hover:bg-white hover:text-text-primary transition"
                            title="Notificaciones">
                            <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
                            <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-danger rounded-full"></span>
                        </button>

                        <div class="w-px h-6 bg-gray-200 mx-1"></div>

                        <div class="flex items-center gap-2.5 pl-1">
                            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center shadow-sm">
                                <span class="text-xs-fluid font-bold text-white leading-none">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                            <div class="hidden md:block leading-tight">
                                <p class="text-small font-600 text-text-primary leading-none mb-0.5">
                                    {{ auth()->user()->name ?? 'Usuario' }}
                                </p>
                                <p class="text-xs-fluid text-text-muted leading-none">
                                    {{ auth()->user()->role->name ?? 'Sin rol' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-5 lg:p-6">
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
                        popup: 'rounded-2xl shadow-xl',
                        confirmButton: '!rounded-lg !text-sm !font-semibold',
                        cancelButton: '!rounded-lg !text-sm !font-semibold'
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