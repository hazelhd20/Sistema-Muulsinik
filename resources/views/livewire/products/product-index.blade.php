<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Productos">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Producto
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width instead of full flex --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar producto..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button" class="btn-secondary shrink-0"
            :class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.categoryFilter }">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Filtros
            @if($categoryFilter)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
            @endif
        </button>

        <div class="flex-1"></div>

        {{-- Clear button: only when filters active --}}
        @if($search || $categoryFilter)
            <button wire:click="$set('search', ''); $set('categoryFilter', '');" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
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
                                    <span class="badge badge-secondary">{{ $product->measure->abbreviation }}</span>
                                @else
                                    <span class="text-text-muted">—</span>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEditModal({{ $product->id }})" class="btn-icon-primary"
                                        title="Editar producto">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="¿Eliminar este producto?" class="btn-icon-danger"
                                        title="Eliminar producto">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
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
                    <button type="button" wire:click="$set('showCreateModal', false)"
                        class="btn-secondary">Cancelar</button>
                    <x-submit-button target="saveProduct">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Producto' }}
                    </x-submit-button>
                </div>
            </form>
        </x-modal>
    @endif

</div>