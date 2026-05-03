<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Catálogo de Productos</h1>
            <p class="text-sm text-text-muted">Catálogo maestro de productos</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nuevo Producto
        </button>
    </div>

    @if(session('success'))
        <div x-data x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' })"></div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar producto..." class="input pl-10">
        </div>
        <x-custom-select 
            wire:model.live="categoryFilter" 
            :options="$categories" 
            placeholder="Todas las categorías" 
            class="w-auto min-w-[180px]"
        />
    </div>

    {{-- Products table --}}
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Unidad</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
                                    <i data-lucide="package" class="w-4 h-4 text-primary-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-text-primary">{{ $product->canonical_name }}</p>
                                    @if($product->description)
                                        <p class="text-xs text-text-muted truncate max-w-xs">{{ $product->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $categories[$product->category] ?? $product->category }}</span>
                        </td>
                        <td class="text-sm text-text-secondary">{{ $units[$product->unit] ?? $product->unit }}</td>
                        <td class="text-center">
                            <button
                                wire:click="deleteProduct({{ $product->id }})"
                                wire:confirm="¿Eliminar este producto?"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12">
                            <i data-lucide="package" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                            <p class="text-text-muted">No hay productos en el catálogo</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    {{-- Create Product Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-md">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">Nuevo Producto</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="createProduct" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre canónico *</label>
                        <input wire:model="canonicalName" type="text" class="input" placeholder="Ej. Cemento Portland CPC 30R">
                        <p class="mt-1 text-xs text-text-muted">Nombre estándar del producto en el catálogo interno</p>
                        @error('canonicalName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Unidad *</label>
                            <x-custom-select 
                                wire:model="unit" 
                                :options="$units" 
                                placeholder="Seleccionar..." 
                            />
                            @error('unit') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Categoría *</label>
                            <x-custom-select 
                                wire:model="category" 
                                :options="$categories" 
                                placeholder="Seleccionar..." 
                            />
                            @error('category') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Descripción</label>
                        <textarea wire:model="description" class="input" rows="2" placeholder="Descripción técnica opcional..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Crear Producto</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
