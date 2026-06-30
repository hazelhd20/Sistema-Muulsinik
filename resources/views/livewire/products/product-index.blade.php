<div x-data="productIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $products->count() }}; init()" data-total-on-page="{{ $products->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Productos">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('productos.crear') || auth()->user()->hasPermission('*'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                    Nuevo Producto
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg">
        @php
            $activeCount = ($categoryFilter ? 1 : 0) + ($measureFilter ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($products->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    {{-- Search --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar producto..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Categoría">
                            <x-custom-select x-model="filterCategory" :options="$categories"
                                placeholder="Todas las categorías" />
                        </x-form-field>

                        <x-form-field label="Unidad de Medida">
                            <x-custom-select x-model="filterMeasure" :options="$measures"
                                placeholder="Todas las unidades" />
                        </x-form-field>

                        <x-slot name="footer">
                            <x-button type="button" @click="clearFilters()" variant="link-muted">
                                Limpiar filtros
                            </x-button>
                            <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                Aplicar Filtros
                            </x-button>
                        </x-slot>
                    </x-filters-popover>
                </div>

                {{-- Active Chips Row --}}
                @if($activeCount > 0)
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
                    @if($categoryFilter)
                        <x-filter-chip label="Categoría" :value="$categories[$categoryFilter] ?? $categoryFilter" wire:click="$set('categoryFilter', '')" />
                    @endif
                    @if($measureFilter)
                        <x-filter-chip label="Unidad" :value="$measures[$measureFilter] ?? $measureFilter" wire:click="$set('measureFilter', '')" />
                    @endif
                </div>
                @endif
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($products->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="box" title="No se encontraron productos" message="No hay registros que coincidan con tu búsqueda." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1100px] {{ $products->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($products->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage"
                    @endif
                >
                    <colgroup>
                        <col class="w-14">           {{-- Checkbox --}}
                        <col class="w-4/12">         {{-- Producto --}}
                        <col class="w-2/12">         {{-- Categoría --}}
                        <col class="w-2/12">         {{-- Tipo --}}
                        <col class="w-1/12">         {{-- Medida --}}
                        <col class="w-2/12">         {{-- Fecha de Registro --}}
                        <col class="w-28">           {{-- Acciones --}}
                    </colgroup>
                    <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($products->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="canonical_name" label="Producto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Categoría</th>
                                <x-sortable-header field="item_type" label="Tipo" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th>Medida</th>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="actions text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage">
                            @if($products->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="7" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron productos" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($products as $product)
                                    <tr wire:key="product-row-{{ $product->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $product->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-4 pr-2 text-center" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $product->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="font-semibold text-text-primary truncate" title="{{ $product->canonical_name }}">{{ $product->canonical_name }}</p>
                                            @if($product->description)
                                                <p class="text-xs text-text-muted truncate" title="{{ $product->description }}">{{ $product->description }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->category)
                                                <x-dynamic-badge :value="$product->category->name" />
                                            @else
                                                <span class="text-sm font-medium text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-sm font-medium text-text-secondary">
                                            @php
                                                $typeLabel = match($product->item_type) {
                                                    'labor' => 'Mano de Obra',
                                                    'service' => 'Servicio',
                                                    default => 'Material',
                                                };
                                            @endphp
                                            {{ $typeLabel }}
                                        </td>
                                        <td class="text-sm font-medium text-text-secondary">
                                            @if($product->measure && $product->measure->abbreviation)
                                                {{ strtolower($product->measure->abbreviation) }}
                                            @else
                                                <span class="text-sm font-medium text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-sm font-medium text-text-muted">
                                            {{ $product->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="actions pr-4 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link as="button" @click="$dispatch('open-product-detail', { id: {{ $product->id }} })" icon="eye">
                                                            Ver detalles
                                                        </x-dropdown-link>
                                                        @if(auth()->user()->hasPermission('productos.editar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" wire:click="openEditModal({{ $product->id }})" icon="pencil">
                                                                Editar
                                                            </x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('productos.eliminar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este producto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteProduct', params: [{{ $product->id }}] })" danger="true" icon="trash-2">
                                                                Eliminar
                                                            </x-dropdown-link>
                                                        @endif
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-4 pr-2 text-center">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-48 mb-1.5" />
                                        <x-skeleton class="h-3 rounded w-32" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded w-24 rounded-full" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded w-16 rounded-full" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td class="actions pr-4 py-3">
                                        <div class="flex items-center justify-end">
                                            <x-skeleton class="w-8 h-8 rounded-md" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
        </x-card.table>

        <div class="md:hidden flex flex-col gap-4 mt-2">
            {{-- Tarjetas Móviles (Mobile View) --}}
            <div class="flex flex-col">
                <div wire:loading.class="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($products->isNotEmpty())
                        @foreach($products as $product)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                 :class="selectedRows.includes('{{ $product->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="product-mobile-card-{{ $product->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $product->id }}" />
                                        <span class="font-bold text-text-primary text-base truncate">{{ $product->canonical_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                            </x-slot>
                                            <x-slot name="content">
                                                <x-dropdown-link as="button" @click="$dispatch('open-product-detail', { id: {{ $product->id }} })" icon="eye">Ver detalles</x-dropdown-link>
                                                @if(auth()->user()->hasPermission('productos.editar') || auth()->user()->hasPermission('*'))
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $product->id }})" icon="pencil">Editar</x-dropdown-link>
                                                @endif
                                                @if(auth()->user()->hasPermission('productos.eliminar') || auth()->user()->hasPermission('*'))
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este producto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteProduct', params: [{{ $product->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                @endif
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="pl-8 flex flex-col gap-3">
                                    {{-- Subtítulo --}}
                                    @if($product->description)
                                    <div class="text-xs text-text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <x-lucide-align-left class="w-3.5 h-3.5 shrink-0" />
                                            <span class="truncate">{{ $product->description }}</span>
                                        </span>
                                    </div>
                                    @endif

                                    {{-- Datos y Detalles --}}
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                        <div>
                                            <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">Categoría</p>
                                            @if($product->category)
                                                <x-dynamic-badge :value="$product->category->name" />
                                            @else
                                                <span class="text-xs font-medium text-text-muted">—</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">Tipo</p>
                                            @php
                                                $typeLabel = match($product->item_type) {
                                                    'labor' => 'Mano de Obra',
                                                    'service' => 'Servicio',
                                                    default => 'Material',
                                                };
                                            @endphp
                                            <p class="text-xs font-medium text-text-secondary">{{ $typeLabel }}</p>
                                        </div>
                                        <div>
                                            <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">Unidad</p>
                                            @if($product->measure && $product->measure->abbreviation)
                                                <p class="text-xs font-medium text-text-secondary">{{ strtolower($product->measure->abbreviation) }}</p>
                                            @else
                                                <span class="text-xs font-medium text-text-muted">—</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">Registro</p>
                                            <div class="flex items-center gap-1 text-xs text-text-secondary mt-0.5">
                                                <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                                <span>{{ $product->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron productos" message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="box" title="No se encontraron productos" message="No hay registros que coincidan con tu búsqueda." />
                        </div>
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                    <x-skeleton class="h-5 w-48 rounded" />
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-skeleton class="w-7 h-7 rounded-md" />
                                </div>
                            </div>
                            <div class="pl-8 flex flex-col gap-3">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                    <div>
                                        <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                        <x-skeleton class="h-5 w-24 rounded-full" />
                                    </div>
                                    <div>
                                        <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                        <x-skeleton class="h-5 w-16 rounded-full" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-skeleton class="h-3 w-32 rounded" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        </div>

        {{-- Bulk Actions Bar --}}
        @if(auth()->user()->hasPermission('productos.eliminar') || auth()->user()->hasPermission('*'))
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Productos',
                    description: 'Se eliminarán permanentemente los productos seleccionados que no estén en requisiciones.',
                    confirmLabel: 'Eliminar',
                    variant: 'danger',
                    action: 'bulkDelete',
                    params: []
                })"
                variant="danger"
                icon="trash-2">
                Eliminar
            </x-button>
        </x-bulk-actions-bar>
        @endif
        {{-- Pagination Footer --}}
        @if($products->total() > 0)
            <x-card.footer>
                {{ $products->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
{{-- Create Product Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Producto' : 'Nuevo Producto'" maxWidth="md">
            <form wire:submit="saveProduct" class="p-5 space-y-4">
                <x-form-field label="Nombre canónico" required hint="Nombre estándar del producto en el catálogo interno"
                    error="{{ $errors->first('canonicalName') }}">
                    <input wire:model="canonicalName" type="text" class="input" placeholder="Ej. Cemento Portland CPC 30R">
                </x-form-field>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form-field label="Tipo de Concepto" required error="{{ $errors->first('itemType') }}">
                        <x-custom-select wire:model="itemType" :options="$itemTypes" placeholder="Seleccionar..." />
                    </x-form-field>
                    <x-form-field label="Categoría" required error="{{ $errors->first('categoryId') }}">
                        <x-custom-select wire:model="categoryId" :options="$categories" placeholder="Seleccionar..." />
                    </x-form-field>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <x-form-field label="Unidad" required error="{{ $errors->first('measureId') }}">
                        <x-custom-select wire:model="measureId" :options="$measures" placeholder="Seleccionar..." />
                    </x-form-field>
                </div>
                <x-form-field label="Descripción" error="{{ $errors->first('description') }}">
                    <textarea wire:model="description" class="input" rows="2"
                        placeholder="Descripción técnica opcional..."></textarea>
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveProduct">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Producto' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <livewire:products.product-detail-drawer />

    <x-confirm-modal />
</div>