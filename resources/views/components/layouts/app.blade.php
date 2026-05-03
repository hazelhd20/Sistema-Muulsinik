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

<body class="antialiased min-h-screen" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    <div class="flex min-h-screen">
        {{-- ===== SIDEBAR ===== --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 bg-surface-card flex flex-col transition-transform duration-300 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            {{-- Brand --}}
            <div class="flex items-center justify-center px-6 h-24 border-b border-gray-100 shrink-0">
                <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="h-14 object-contain">
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <p class="px-3 mb-2 text-xs font-semibold text-text-muted uppercase tracking-wider">Principal</p>

                <a href="{{ url('/dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ url('/proyectos') }}" class="nav-link {{ request()->is('proyectos*') ? 'active' : '' }}">
                    <i data-lucide="hard-hat" class="w-5 h-5"></i>
                    <span>Proyectos</span>
                </a>

                <a href="{{ url('/gastos') }}" class="nav-link {{ request()->is('gastos*') ? 'active' : '' }}">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span>Gastos</span>
                </a>

                <a href="{{ url('/requisiciones') }}"
                    class="nav-link {{ request()->is('requisiciones*') ? 'active' : '' }}">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span>Requisiciones</span>
                </a>

                <p class="px-3 mt-6 mb-2 text-xs font-semibold text-text-muted uppercase tracking-wider">Administración
                </p>

                <a href="{{ url('/proveedores') }}"
                    class="nav-link {{ request()->is('proveedores*') ? 'active' : '' }}">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                    <span>Proveedores</span>
                </a>

                <a href="{{ url('/documentos') }}" class="nav-link {{ request()->is('documentos*') ? 'active' : '' }}">
                    <i data-lucide="folder-open" class="w-5 h-5"></i>
                    <span>Documentos</span>
                </a>

                <a href="{{ url('/reportes') }}" class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span>Reportes</span>
                </a>

                <a href="{{ url('/productos') }}" class="nav-link {{ request()->is('productos*') ? 'active' : '' }}">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>Productos</span>
                </a>

                <a href="{{ url('/medidas') }}" class="nav-link {{ request()->is('medidas*') ? 'active' : '' }}">
                    <i data-lucide="ruler" class="w-5 h-5"></i>
                    <span>Medidas</span>
                </a>
            </nav>

            {{-- User Section --}}
            <div class="px-4 py-4 border-t border-gray-100 shrink-0">
                <p class="px-3 mb-2 text-xs font-semibold text-text-muted uppercase tracking-wider">Configuración</p>
                <a href="{{ url('/configuracion') }}" class="nav-link">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Ajustes</span>
                </a>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="nav-link w-full text-left text-danger hover:text-danger">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-show="mobileSidebarOpen" @click="mobileSidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/30 lg:hidden" x-transition:enter="transition-opacity duration-300"
            x-transition:leave="transition-opacity duration-300"></div>

        {{-- ===== MAIN CONTENT AREA ===== --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-surface-main/80 backdrop-blur-lg">
                <div class="flex items-center justify-between h-16 px-6">
                    {{-- Left: Hamburger + Search --}}
                    <div class="flex items-center gap-4">
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen"
                            class="lg:hidden p-2 rounded-lg hover:bg-surface-hover">
                            <i data-lucide="menu" class="w-5 h-5 text-text-secondary"></i>
                        </button>

                        <div class="relative hidden sm:block">
                            <i data-lucide="search"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
                            <input type="search" placeholder="Buscar proyectos, gastos, requisiciones..."
                                class="input pl-10 w-80" id="global-search">
                        </div>
                    </div>

                    {{-- Right: Notifications + Profile --}}
                    <div class="flex items-center gap-3">
                        <button class="relative p-2 rounded-xl hover:bg-surface-hover transition"
                            id="btn-notifications">
                            <i data-lucide="bell" class="w-5 h-5 text-text-secondary"></i>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-danger rounded-full"></span>
                        </button>

                        <button class="p-2 rounded-xl hover:bg-surface-hover transition" id="btn-messages">
                            <i data-lucide="mail" class="w-5 h-5 text-text-secondary"></i>
                        </button>

                        <div class="flex items-center gap-3 pl-3 border-l border-gray-200">
                            <div class="w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-primary-700">
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-semibold text-text-primary">
                                    {{ auth()->user()->name ?? 'Usuario' }}
                                </p>
                                <p class="text-xs text-text-muted">{{ auth()->user()->role->name ?? 'Sin rol' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        // Inicializar Lucide Icons después de cada actualización de Livewire
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
        document.addEventListener('livewire:navigated', () => lucide.createIcons());
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', () => lucide.createIcons());
        }

        // Interceptar Livewire wire:confirm para usar SweetAlert2
        document.addEventListener('click', e => {
            let el = e.target.closest('[wire\\:confirm]');
            if (el && !el.hasAttribute('data-confirmed')) {
                e.preventDefault();
                e.stopImmediatePropagation(); // Detiene el evento antes de que Livewire lo vea

                let content = el.getAttribute('wire:confirm');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: content,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0ea5e9',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'rounded-lg',
                        cancelButton: 'rounded-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        el.setAttribute('data-confirmed', 'true');
                        // Bypassear el window.confirm nativo temporalmente
                        let originalConfirm = window.confirm;
                        window.confirm = () => true;

                        el.click(); // Disparar el clic de nuevo, esta vez pasará a Livewire

                        window.confirm = originalConfirm;
                        el.removeAttribute('data-confirmed');
                    }
                });
            }
        }, true); // Capturing phase para interceptar antes que Livewire
    </script>
</body>

</html>