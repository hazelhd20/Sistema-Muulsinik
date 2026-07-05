<div x-data="{
        open: @entangle('isOpen'),
        selectedIndex: 0,
        init() {
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                        this.selectedIndex = 0;
                    });
                } else {
                    $wire.clear();
                }
            });
            // Ctrl+K / Cmd+K
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = true;
                }
            });
            // Cerrar el modal automáticamente al navegar con wire:navigate
            document.addEventListener('livewire:navigated', () => {
                this.open = false;
            });
        },
        focusNext() {
            const items = this.getItems();
            if (items.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % items.length;
            this.scrollToItem(items[this.selectedIndex]);
        },
        focusPrev() {
            const items = this.getItems();
            if (items.length === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + items.length) % items.length;
            this.scrollToItem(items[this.selectedIndex]);
        },
        selectCurrent() {
            const items = this.getItems();
            if (items[this.selectedIndex]) {
                const link = items[this.selectedIndex].querySelector('a');
                if (link) link.click();
                else items[this.selectedIndex].click();
            }
        },
        getItems() {
            if (!this.$refs.results) return [];
            return Array.from(this.$refs.results.querySelectorAll('[data-search-item]'));
        },
        scrollToItem(item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    }"
    @keydown.escape.window="open = false"
>
    {{-- Unified Trigger Button (Icon Only) --}}
    <button
        type="button"
        @click="open = true"
        class="group relative inline-flex items-center justify-center w-9 h-9 p-2 rounded-lg text-text-muted icon-btn-hover transition-all duration-200 ease-out active:scale-95 cursor-pointer"
        title="Buscar (Ctrl+K)"
        aria-label="Buscar"
    >
        <x-lucide-search class="w-5 h-5 shrink-0" />
    </button>

    {{-- Command Palette Modal --}}
    <template x-teleport="body">
        <div
            x-show="open"
            class="relative z-[100]"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div
                x-show="open"
                x-transition:enter="transition-premium"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
            ></div>

            {{-- Modal Panel --}}
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20">
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition-premium"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                    class="mx-auto max-w-2xl transform divide-y divide-border overflow-hidden rounded-xl bg-surface-card shadow-2xl ring-1 ring-border transition-all flex flex-col"
                >
                    {{-- Input Header --}}
                    <div class="relative flex items-center px-4 py-3">
                        <x-lucide-search class="w-5 h-5 text-text-muted absolute left-4" />
                        <input
                            type="text"
                            x-ref="searchInput"
                            wire:model.live.debounce.400ms="query"
                            @input="selectedIndex = 0"
                            @keydown.arrow-down.prevent="focusNext()"
                            @keydown.arrow-up.prevent="focusPrev()"
                            @keydown.enter.prevent="selectCurrent()"
                            class="w-full bg-transparent pl-10 pr-10 text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-0 text-body border-0 h-10"
                            placeholder="Buscar requisiciones, proyectos, proveedores..."
                            autocomplete="off"
                        >
                        {{-- Clear Button --}}
                        <button
                            type="button"
                            wire:loading.remove
                            x-show="$wire.query"
                            @click="$wire.clear(); $refs.searchInput.focus()"
                            class="absolute right-3 btn-close"
                        >
                            <x-lucide-x class="w-4 h-4" />
                        </button>
                    </div>

                    {{-- Results Body --}}
                    @php
                        $hasAnyResults = !empty($results['requisitions']) || !empty($results['suppliers']) || !empty($results['projects']) || !empty($results['products']);
                    @endphp

                    <div class="max-h-[60vh] overflow-y-auto p-2" x-ref="results">
                        {{-- Skeletons de Carga --}}
                        <div wire:loading wire:target="query" class="w-full">
                            <div class="space-y-4 p-2">
                                @for($i = 0; $i < 2; $i++)
                                <div>
                                    {{-- Título de categoría --}}
                                    <div class="px-3 py-1 mb-2">
                                        <x-skeleton class="h-2.5 w-24 rounded" />
                                    </div>
                                    {{-- Filas de resultado --}}
                                    <div class="space-y-1">
                                        @for($j = 0; $j < 2; $j++)
                                        <div class="flex items-center gap-3 px-4 py-2.5 opacity-{{ 100 - ($j * 30) }}">
                                            <x-skeleton class="w-8 h-8 rounded-lg shrink-0" />
                                            <div class="flex-1 space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <x-skeleton class="h-3 w-1/3 rounded" />
                                                    <x-skeleton class="h-3 w-8 rounded-full" />
                                                </div>
                                                <x-skeleton class="h-2 w-1/4 rounded" />
                                            </div>
                                        </div>
                                        @endfor
                                    </div>
                                </div>
                                @endfor
                            </div>
                        </div>

                        {{-- Contenido de Resultados --}}
                        <div wire:loading.remove wire:target="query">
                            @if(!$hasAnyResults && strlen($query) >= 2)
                                <x-empty-state icon="search-x" title="No encontramos resultados" class="py-14">
                                    <x-slot:description>
                                        No pudimos encontrar nada para "<span class="font-semibold text-text-primary" x-text="$wire.query"></span>". Revisa la ortografía o intenta con otro término.
                                    </x-slot:description>
                                </x-empty-state>
                            @elseif(!$hasAnyResults && strlen($query) < 2)
                                <div class="py-6 px-4">
                                    <h3 class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider mb-3">Acciones Rápidas</h3>
                                    <div class="space-y-1">
                                        <a href="{{ route('requisiciones.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 hover:bg-surface-hover transition-colors group rounded-md">
                                            <div class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center shrink-0"><x-lucide-clipboard-list class="w-4 h-4 text-primary-600" /></div>
                                            <div class="min-w-0 flex-1"><p class="text-small font-medium text-text-primary group-hover:text-primary-600 transition-colors">Crear Requisición</p></div>
                                        </a>
                                        <a href="{{ route('proyectos.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 hover:bg-surface-hover transition-colors group rounded-md">
                                            <div class="w-8 h-8 rounded-lg bg-warning-light flex items-center justify-center shrink-0"><x-lucide-hard-hat class="w-4 h-4 text-warning" /></div>
                                            <div class="min-w-0 flex-1"><p class="text-small font-medium text-text-primary group-hover:text-primary-600 transition-colors">Ver Proyectos Activos</p></div>
                                        </a>
                                        <a href="{{ route('productos.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 hover:bg-surface-hover transition-colors group rounded-md">
                                            <div class="w-8 h-8 rounded-lg bg-info-light flex items-center justify-center shrink-0"><x-lucide-package class="w-4 h-4 text-info" /></div>
                                            <div class="min-w-0 flex-1"><p class="text-small font-medium text-text-primary group-hover:text-primary-600 transition-colors">Catálogo de Productos</p></div>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            @if($hasAnyResults)
                                @php
                                    $globalIndex = 0;
                                @endphp

                                @foreach($categories as $key => $label)
                                    @if(!empty($results[$key]))
                                        <div class="mb-4 last:mb-0">
                                            <h2 class="px-3 py-1 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">{{ $label }}</h2>
                                            <div class="mt-2 space-y-1">
                                                @foreach($results[$key] as $item)
                                                    <x-search-result-item 
                                                        data-search-item
                                                        :url="$item['url']" 
                                                        :icon="$item['icon']" 
                                                        :title="$item['title']" 
                                                        :subtitle="$item['subtitle']" 
                                                        :color="$colorMap[$key]"
                                                        :typeLabel="$item['typeLabel'] ?? null"
                                                        class="transition-colors block w-full outline-none"
                                                        x-bind:class="{ 'bg-surface-hover ring-1 ring-border': selectedIndex === {{ $globalIndex }} }"
                                                        @mouseenter="selectedIndex = {{ $globalIndex }}"
                                                        @keydown.enter.prevent="selectCurrent()"
                                                    />
                                                    @php $globalIndex++; @endphp
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Footer / Legend --}}
                    <div class="hidden sm:flex flex-wrap items-center justify-between text-xs-fluid text-text-muted dropdown-footer">
                        <div class="flex gap-4">
                            <span class="flex items-center gap-1"><kbd class="rounded border border-border bg-surface-main px-1.5 py-0.5 font-sans font-medium text-text-secondary">↵</kbd> para seleccionar</span>
                            <span class="flex items-center gap-1"><kbd class="rounded border border-border bg-surface-main px-1.5 py-0.5 font-sans font-medium text-text-secondary">↓</kbd><kbd class="rounded border border-border bg-surface-main px-1.5 py-0.5 font-sans font-medium text-text-secondary">↑</kbd> para navegar</span>
                        </div>
                        <span class="flex items-center gap-1"><kbd class="rounded border border-border bg-surface-main px-1.5 py-0.5 font-sans font-medium text-text-secondary">esc</kbd> para cerrar</span>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
