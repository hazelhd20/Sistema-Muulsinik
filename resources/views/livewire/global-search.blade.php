<div class="relative" x-data="{ open: @entangle('isOpen') }" @click.away="open = false">
    {{-- Input de búsqueda --}}
    <div class="relative hidden sm:flex items-center">
        <i data-lucide="search"
            class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-text-muted pointer-events-none"></i>
        <input
            type="search"
            wire:model.live.debounce.300ms="query"
            placeholder="Buscar requisiciones, proveedores, proyectos..."
            class="input pl-8 w-72 h-8 py-0 text-small bg-surface-card"
            x-ref="searchInput"
            @keydown.esc="open = false; $wire.clear()"
            @keydown.ctrl.k.prevent="$refs.searchInput.focus(); open = true"
            @keydown.meta.k.prevent="$refs.searchInput.focus(); open = true"
            @focus="if ($wire.query.length >= 2) open = true"
        >
        <kbd class="absolute right-2 top-1/2 -translate-y-1/2 px-1.5 py-0.5 text-[10px] font-medium text-text-muted bg-surface-hover rounded border border-border hidden lg:inline-block">
            Ctrl+K
        </kbd>
    </div>

    {{-- Dropdown de resultados --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-full left-0 mt-2 w-[28rem] bg-surface-card rounded-xl shadow-2xl border border-border overflow-hidden z-50"
        style="display: none;"
    >
        @php
            $hasAnyResults = !empty($results['requisitions']) || !empty($results['suppliers']) || !empty($results['projects']) || !empty($results['products']);
        @endphp

        @if(!$hasAnyResults && strlen($query) >= 2)
            <div class="p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-surface-hover flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="search-x" class="w-5 h-5 text-text-muted"></i>
                </div>
                <p class="text-small text-text-secondary">No se encontraron resultados</p>
                <p class="text-xs-fluid text-text-muted mt-1">Intenta con otros términos de búsqueda</p>
            </div>
        @endif

        <div class="max-h-[70vh] overflow-y-auto">
            {{-- Requisiciones --}}
            @if(!empty($results['requisitions']))
                <div class="py-2">
                    <div class="px-4 py-1.5 bg-surface-hover">
                        <span class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wide">Requisiciones</span>
                    </div>
                    @foreach($results['requisitions'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-primary-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{{ $item['title'] }}</p>
                                <p class="text-xs-fluid text-text-muted truncate">{{ $item['subtitle'] }}</p>
                            </div>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Proveedores --}}
            @if(!empty($results['suppliers']))
                <div class="py-2 {{ !empty($results['requisitions']) ? 'border-t border-border' : '' }}">
                    <div class="px-4 py-1.5 bg-surface-hover">
                        <span class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wide">Proveedores</span>
                    </div>
                    @foreach($results['suppliers'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-emerald-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{{ $item['title'] }}</p>
                                <p class="text-xs-fluid text-text-muted truncate">{{ $item['subtitle'] }}</p>
                            </div>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Proyectos --}}
            @if(!empty($results['projects']))
                <div class="py-2 {{ !empty($results['requisitions']) || !empty($results['suppliers']) ? 'border-t border-border' : '' }}">
                    <div class="px-4 py-1.5 bg-surface-hover">
                        <span class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wide">Proyectos</span>
                    </div>
                    @foreach($results['projects'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{{ $item['title'] }}</p>
                                <p class="text-xs-fluid text-text-muted truncate">{{ $item['subtitle'] }}</p>
                            </div>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Productos --}}
            @if(!empty($results['products']))
                <div class="py-2 {{ !empty($results['requisitions']) || !empty($results['suppliers']) || !empty($results['projects']) ? 'border-t border-border' : '' }}">
                    <div class="px-4 py-1.5 bg-surface-hover">
                        <span class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wide">Productos</span>
                    </div>
                    @foreach($results['products'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-sky-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{{ $item['title'] }}</p>
                                <p class="text-xs-fluid text-text-muted truncate">{{ $item['subtitle'] }}</p>
                            </div>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Footer --}}
        @if($hasAnyResults)
            <div class="px-4 py-2 bg-surface-hover border-t border-border flex items-center justify-between">
                <div class="flex items-center gap-3 text-xs-fluid text-text-muted">
                    <span><kbd class="px-1 py-0.5 bg-surface-card rounded border border-border">↑↓</kbd> navegar</span>
                    <span><kbd class="px-1 py-0.5 bg-surface-card rounded border border-border">↵</kbd> seleccionar</span>
                    <span><kbd class="px-1 py-0.5 bg-surface-card rounded border border-border">esc</kbd> cerrar</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Input móvil (visible solo en móvil) --}}
    <div class="sm:hidden relative flex items-center">
        <button
            @click="open = true; $nextTick(() => $refs.mobileInput.focus())"
            class="p-2 rounded-md text-text-secondary hover:bg-surface-hover transition"
        >
            <i data-lucide="search" class="w-5 h-5"></i>
        </button>

        <div
            x-show="open"
            x-transition
            class="fixed inset-0 z-50 bg-surface-main"
            style="display: none;"
        >
            <div class="flex items-center gap-3 px-4 py-3 border-b border-border">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
                    <input
                        type="search"
                        x-ref="mobileInput"
                        wire:model.live.debounce.300ms="query"
                        placeholder="Buscar..."
                        class="input pl-10 w-full"
                        @keydown.esc="open = false; $wire.clear()"
                    >
                </div>
                <button @click="open = false; $wire.clear()" class="text-small text-text-secondary">Cancelar</button>
            </div>

            <div class="overflow-y-auto h-[calc(100vh-4rem)]">
                @php
                    $hasAnyResultsMobile = !empty($results['requisitions']) || !empty($results['suppliers']) || !empty($results['projects']) || !empty($results['products']);
                @endphp

                @if(!$hasAnyResultsMobile && strlen($query) >= 2)
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-surface-hover flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="search-x" class="w-5 h-5 text-text-muted"></i>
                        </div>
                        <p class="text-small text-text-secondary">No se encontraron resultados</p>
                    </div>
                @endif

                @if(!empty($results['requisitions']))
                    <div class="py-2">
                        <div class="px-4 py-2 bg-surface-hover">
                            <span class="text-xs-fluid font-semibold text-text-muted uppercase">Requisiciones</span>
                        </div>
                        @foreach($results['requisitions'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate @click="open = false" class="flex items-center gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-primary-600"></i>
                                <div>
                                    <p class="text-small font-medium text-text-primary">{{ $item['title'] }}</p>
                                    <p class="text-xs-fluid text-text-muted">{{ $item['subtitle'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($results['suppliers']))
                    <div class="py-2">
                        <div class="px-4 py-2 bg-surface-hover">
                            <span class="text-xs-fluid font-semibold text-text-muted uppercase">Proveedores</span>
                        </div>
                        @foreach($results['suppliers'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate @click="open = false" class="flex items-center gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-emerald-600"></i>
                                <div>
                                    <p class="text-small font-medium text-text-primary">{{ $item['title'] }}</p>
                                    <p class="text-xs-fluid text-text-muted">{{ $item['subtitle'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($results['projects']))
                    <div class="py-2">
                        <div class="px-4 py-2 bg-surface-hover">
                            <span class="text-xs-fluid font-semibold text-text-muted uppercase">Proyectos</span>
                        </div>
                        @foreach($results['projects'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate @click="open = false" class="flex items-center gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-amber-600"></i>
                                <div>
                                    <p class="text-small font-medium text-text-primary">{{ $item['title'] }}</p>
                                    <p class="text-xs-fluid text-text-muted">{{ $item['subtitle'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($results['products']))
                    <div class="py-2">
                        <div class="px-4 py-2 bg-surface-hover">
                            <span class="text-xs-fluid font-semibold text-text-muted uppercase">Productos</span>
                        </div>
                        @foreach($results['products'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate @click="open = false" class="flex items-center gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-sky-600"></i>
                                <div>
                                    <p class="text-small font-medium text-text-primary">{{ $item['title'] }}</p>
                                    <p class="text-xs-fluid text-text-muted">{{ $item['subtitle'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
