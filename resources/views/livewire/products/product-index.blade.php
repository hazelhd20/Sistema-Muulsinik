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
                class="input pl-10 pr-10 w-full"
                @focus="focused = true"
                @blur="focused = false">
            <button
                x-show="$wire.search"
                x-transition
                @click="$wire.search = ''"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button"
            class="btn-secondary shrink-0"
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
            <button wire:click="$set('search', ''); $set('categoryFilter', '');" 
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
    </div>

    {{-- Expandable Filters Panel --}}
    <div x-show="showFilters" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6">
        <div class="card !bg-surface-hover/50 !p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <div class="flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="categoryFilter" :options="$categories" placeholder="Todas las categorías" class="w-full sm:w-56" />
                <p class="text-xs-fluid text-text-muted">Selecciona una categoría para filtrar el catálogo</p>
            </div>
        </div>
    </div>

    {{-- Products table --}}
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
                @forelse($products as $product)
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
                            <x-dynamic-badge :value="$product->category->name ?? 'Sin categoría'" />
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
                                <button wire:click="openEditModal({{ $product->id }})" class="btn-icon-primary" title="Editar producto">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="deleteProduct({{ $product->id }})" wire:confirm="¿Eliminar este producto?" class="btn-icon-danger" title="Eliminar producto">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="package" title="No hay productos en el catálogo" message="Agrega productos para gestionar tu inventario." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    {{-- Create Product Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Producto' : 'Nuevo Producto'" maxWidth="md">
                <form wire:submit="saveProduct" class="p-5 space-y-4">
                    <div>
                        <label class="label">Nombre canónico *</label>
                        <input wire:model="canonicalName" type="text" class="input"
                            placeholder="Ej. Cemento Portland CPC 30R">
                        <p class="mt-1 text-xs-fluid text-text-muted">Nombre estándar del producto en el catálogo interno</p>
                        @error('canonicalName') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Unidad *</label>
                            <x-custom-select wire:model="measureId" :options="$measures" placeholder="Seleccionar..." />
                            @error('measureId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Categoría *</label>
                            <x-custom-select wire:model="categoryId" :options="$categories" placeholder="Seleccionar..." />
                            @error('categoryId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Descripción</label>
                        <textarea wire:model="description" class="input" rows="2"
                            placeholder="Descripción técnica opcional..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary relative" wire:loading.attr="disabled" wire:target="saveProduct">
                            <span wire:loading.class="opacity-0" wire:target="saveProduct"
                                class="inline-flex items-center gap-1.5 transition-opacity">
                                {{ $editingId ? 'Guardar Cambios' : 'Crear Producto' }}
                            </span>
                            <span wire:loading wire:target="saveProduct"
                                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif

</div>