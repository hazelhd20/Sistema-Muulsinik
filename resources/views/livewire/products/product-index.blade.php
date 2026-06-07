<div x-data="{ showFilters: false }">
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
        {{-- Search: compact width --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar producto..." />

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            {{-- Clear button: only when filters active --}}
            @if($search || $categoryFilter)
                <button wire:click="$set('search', ''); $set('categoryFilter', '');" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors cursor-pointer">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    Limpiar
                </button>
            @endif

            {{-- Filters Toggle Button with counter badge --}}
            <x-button @click="showFilters = !showFilters" variant="secondary" icon="sliders-horizontal" class="shrink-0"
                x-bind:class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.categoryFilter }">
                Filtros
                @if($categoryFilter)
                    <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
                @endif
            </x-button>
        </div>
    </div>

    {{-- Expandable Filters Panel --}}
    <div x-show="showFilters" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6">
        <div class="card !p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <div class="flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="categoryFilter" :options="$categories"
                    placeholder="Todas las categorías" class="w-full sm:w-56" />
                <p class="text-xs-fluid text-text-muted">Selecciona una categoría para filtrar el catálogo</p>
            </div>
        </div>
    </div>

    {{-- Products table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container">
                @if($products->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="canonical_name" label="Producto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="category_id" label="Categoría" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="measure_id" label="Unidad" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
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
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-button @click="$dispatch('open-product-detail', { id: {{ $product->id }} })" variant="icon" icon="eye" title="Ver detalles" />
                                            <x-button wire:click="openEditModal({{ $product->id }})" variant="icon-primary" icon="pencil" title="Editar producto" />
                                            <x-button wire:click="deleteProduct({{ $product->id }})"
                                                wire:confirm="¿Eliminar este producto?" variant="icon-danger" icon="trash-2"
                                                title="Eliminar producto" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="package" title="No hay productos en el catálogo"
                        message="Agrega productos para gestionar tu inventario." />
                @endif
            </div>
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
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
                                    <x-skeleton class="h-5  rounded-full w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded-full w-16" />
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
        </div>
    </div>

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