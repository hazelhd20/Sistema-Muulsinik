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
        class="relative p-1.5 rounded-md text-text-muted hover:text-text-primary hover:bg-black/5 transition-colors"
        title="Buscar (Ctrl+K)"
        aria-label="Buscar"
    >
        <i data-lucide="search" class="w-4 h-4"></i>
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
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
            ></div>

            {{-- Modal Panel --}}
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20">
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="mx-auto max-w-2xl transform divide-y divide-border overflow-hidden rounded-xl bg-surface-card shadow-2xl ring-1 ring-border transition-all flex flex-col"
                >
                    {{-- Input Header --}}
                    <div class="relative flex items-center px-4 py-3">
                        <i data-lucide="search" class="w-5 h-5 text-text-muted absolute left-4"></i>
                        <input
                            type="text"
                            x-ref="searchInput"
                            wire:model.live.debounce.300ms="query"
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
                            class="absolute right-4 text-text-muted hover:text-text-primary p-1 rounded-md transition"
                        >
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    {{-- Results Body --}}
                    @php
                        $hasAnyResults = !empty($results['requisitions']) || !empty($results['suppliers']) || !empty($results['projects']) || !empty($results['products']);
                    @endphp

                    <div class="max-h-[60vh] overflow-y-auto p-2" x-ref="results">
                        {{-- Skeletons de Carga --}}
                        <div wire:loading wire:target="query" class="w-full">
                            <div class="space-y-5 p-2">
                                @for($i = 0; $i < 2; $i++)
                                <div>
                                    {{-- Título de categoría --}}
                                    <x-skeleton class="h-3 w-24 rounded mb-3" />
                                    {{-- Filas de resultado --}}
                                    <div class="space-y-2">
                                        @for($j = 0; $j < 2; $j++)
                                        <div class="flex items-center gap-3 px-3 py-2">
                                            <x-skeleton class="w-8 h-8 rounded-lg shrink-0" />
                                            <div class="flex-1 space-y-2">
                                                <x-skeleton class="h-3 w-2/5 rounded" />
                                                <x-skeleton class="h-2.5 w-1/4 rounded" />
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
                                    <p class="text-xs-fluid text-text-muted mt-2">
                                        No pudimos encontrar nada para "<span class="font-semibold text-text-primary" x-text="$wire.query"></span>". Revisa la ortografía o intenta con otro término.
                                    </p>
                                </x-empty-state>
                            @elseif(!$hasAnyResults && strlen($query) < 2)
                                <x-empty-state icon="search" title="¿Qué estás buscando?" message="Busca requisiciones, proyectos, proveedores y productos escribiendo arriba." class="py-14" />
                            @endif

                            @if($hasAnyResults)
                                @php
                                    $globalIndex = 0;
                                    $colorMap = [
                                        'requisitions' => 'primary',
                                        'projects' => 'warning',
                                        'suppliers' => 'success',
                                        'products' => 'info',
                                    ];
                                @endphp

                                @foreach(['requisitions' => 'Requisiciones', 'projects' => 'Proyectos', 'suppliers' => 'Proveedores', 'products' => 'Productos'] as $key => $label)
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
                    <div class="flex flex-wrap items-center justify-between bg-surface-hover px-4 py-2.5 text-xs text-text-muted border-t border-border dropdown-footer">
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
