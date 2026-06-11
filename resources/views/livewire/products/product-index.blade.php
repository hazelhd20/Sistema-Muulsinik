<div x-data="{ showFilters: false, selectedRows: @entangle('selectedRows') }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Productos">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Producto
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        {{-- Search --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar producto..." />

        {{-- Filters Popover --}}
        @php
            $activeCount = ($categoryFilter ? 1 : 0) + ($measureFilter ? 1 : 0);
        @endphp
        <div x-data="{
            filterCategory: '{{ $categoryFilter }}',
            filterMeasure: '{{ $measureFilter }}',
            initFilters() {
                this.filterCategory = '{{ $categoryFilter }}';
                this.filterMeasure = '{{ $measureFilter }}';
            },
            applyFilters() {
                if ($wire.categoryFilter !== this.filterCategory) $wire.set('categoryFilter', this.filterCategory);
                if ($wire.measureFilter !== this.filterMeasure) $wire.set('measureFilter', this.filterMeasure);
            },
            clearFilters() {
                this.filterCategory = '';
                this.filterMeasure = '';
                this.applyFilters();
                open = false;
            }
        }">
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
                    <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                        Limpiar filtros
                    </button>
                    <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                        Aplicar Filtros
                    </x-button>
                </x-slot>
            </x-filters-popover>
        </div>
    </div>

    {{-- Active Chips Row --}}
    @if($activeCount > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if($categoryFilter)
            <x-filter-chip label="Categoría" :value="$categories[$categoryFilter] ?? $categoryFilter" wire:click="$set('categoryFilter', '')" />
        @endif
        @if($measureFilter)
            <x-filter-chip label="Unidad" :value="$measures[$measureFilter] ?? $measureFilter" wire:click="$set('measureFilter', '')" />
        @endif
    </div>
    @endif

    {{-- Products table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, categoryFilter, measureFilter, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($products->isNotEmpty())
                    <table>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-on:change="$el.checked ? selectedRows = [...new Set([...(selectedRows || []), ...[{{ $products->pluck('id')->join(',') }}].map(String)])] : selectedRows = (selectedRows || []).filter(id => ![{{ $products->pluck('id')->join(',') }}].map(String).includes(id))"
                                        :checked="[{{ $products->pluck('id')->join(',') }}].length > 0 && [{{ $products->pluck('id')->join(',') }}].map(String).every(id => (selectedRows || []).includes(id))" />
                                </th>
                                <x-sortable-header field="canonical_name" label="Producto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="category_id" label="Categoría" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="measure_id" label="Unidad" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr wire:key="product-row-{{ $product->id }}"
                                    class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                    :class="(selectedRows || []).map(String).includes('{{ $product->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $product->id }}" />
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-semibold text-text-primary">{{ $product->canonical_name }}</p>
                                            @if($product->description)
                                                <p class="text-xs-fluid text-text-muted truncate max-w-xs">{{ $product->description }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($product->category)
                                            <x-dynamic-badge :value="$product->category->name" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-body text-text-secondary">
                                        @if($product->measure && $product->measure->abbreviation)
                                            <x-badge variant="secondary">{{ $product->measure->abbreviation }}</x-badge>
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-text-muted text-small">
                                        {{ $product->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" @click="$dispatch('open-product-detail', { id: {{ $product->id }} })" icon="eye">
                                                        Ver detalles
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $product->id }})" icon="pencil">
                                                        Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="deleteProduct({{ $product->id }})"
                                                        wire:confirm="¿Eliminar este producto? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                        Eliminar
                                                    </x-dropdown-link>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="box" title="No se encontraron productos"
                        message="No hay registros que coincidan con tu búsqueda." />
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($products->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($products as $product)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                         :class="(selectedRows || []).map(String).includes('{{ $product->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                         wire:key="product-mobile-card-{{ $product->id }}">
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <x-table-checkbox x-model="selectedRows" value="{{ $product->id }}" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-text-primary text-body">{{ $product->canonical_name }}</span>
                                    </div>
                                    @if($product->description)
                                        <p class="text-xs-fluid text-text-secondary mt-0.5 truncate max-w-[200px]">{{ $product->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                            <div>
                                <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Categoría</p>
                                @if($product->category)
                                    <x-dynamic-badge :value="$product->category->name" />
                                @else
                                    <span class="text-text-muted">—</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1 text-right">Unidad</p>
                                <div class="text-right">
                                    @if($product->measure && $product->measure->abbreviation)
                                        <x-badge variant="secondary">{{ $product->measure->abbreviation }}</x-badge>
                                    @else
                                        <span class="text-text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-span-2 flex items-center justify-between mt-1 pt-2 border-t border-border/50">
                                <div class="flex items-center gap-1.5 text-text-secondary">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-text-muted"></i>
                                    <span>Registro: {{ $product->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <x-button variant="secondary" class="w-full justify-center">
                                        <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                                        <span class="ml-2">Opciones</span>
                                    </x-button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link as="button" @click="$dispatch('open-product-detail', { id: {{ $product->id }} })" icon="eye">
                                        Ver detalles
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $product->id }})" icon="pencil">
                                        Editar
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="¿Eliminar este producto? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                        Eliminar
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Unidad</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <x-skeleton class="h-4  rounded w-48 mb-1" />
                                    <x-skeleton class="h-3  rounded w-32" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded w-16" />
                                </td>
                                <td class="actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-skeleton class="w-8 h-8  rounded" />
                                        <x-skeleton class="w-8 h-8  rounded" />
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- Skeletons Móviles --}}
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <x-skeleton class="h-5 w-32 rounded" />
                                <x-skeleton class="h-3 w-24 rounded mt-1.5" />
                            </div>
                            <x-skeleton class="h-5 w-20 rounded-full" />
                        </div>
                        <div class="flex justify-between items-center bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                            <x-skeleton class="h-4 w-24 rounded" />
                            <x-skeleton class="h-5 w-16 rounded" />
                        </div>
                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        
        {{-- Bulk Actions Bar --}}
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

    </div>
    
    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

    <div class="mt-4">{{ $products->links() }}</div>

    {{-- Create Product Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Producto' : 'Nuevo Producto'" maxWidth="md">
            <form wire:submit="saveProduct" class="p-5 space-y-4">
                <x-form-field label="Nombre canónico" required hint="Nombre estándar del producto en el catálogo interno"
                    error="{{ $errors->first('canonicalName') }}">
                    <input wire:model="canonicalName" type="text" class="input" placeholder="Ej. Cemento Portland CPC 30R">
                </x-form-field>
                <div class="grid grid-cols-2 gap-4">
                    <x-form-field label="Unidad" required error="{{ $errors->first('measureId') }}">
                        <x-custom-select wire:model="measureId" :options="$measures" placeholder="Seleccionar..." />
                    </x-form-field>
                    <x-form-field label="Categoría" required error="{{ $errors->first('categoryId') }}">
                        <x-custom-select wire:model="categoryId" :options="$categories" placeholder="Seleccionar..." />
                    </x-form-field>
                </div>
                <x-form-field label="Descripción" error="{{ $errors->first('description') }}">
                    <textarea wire:model="description" class="input" rows="2"
                        placeholder="Descripción técnica opcional..."></textarea>
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveProduct">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Producto' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <livewire:products.product-detail-drawer />

</div>