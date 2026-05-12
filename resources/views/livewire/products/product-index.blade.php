<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-widest mb-0.5">Catálogos</p>
            <h1 class="text-h1 text-text-primary">Productos</h1>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Nuevo Producto
        </button>
    </div>

    @if(session('success'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' }); $el.remove()"
            wire:key="toast-success-{{ microtime(true) }}"></div>
    @endif
    @if(session('error'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, icon: 'error', title: '{{ session('error') }}' }); $el.remove()"
            wire:key="toast-error-{{ microtime(true) }}"></div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar producto..."
                class="input pl-10">
        </div>
        <x-custom-select wire:model.live="categoryFilter" :options="$categories" placeholder="Todas las categorías"
            class="w-auto min-w-[180px]" />
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
                            <div>
                                <p class="font-semibold text-text-primary">{{ $product->canonical_name }}</p>
                                @if($product->description)
                                    <p class="text-xs-fluid text-text-muted truncate max-w-xs">{{ $product->description }}</p>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $product->category->name ?? 'Sin categoría' }}</span>
                        </td>
                        <td class="text-body text-text-secondary">{{ $measures[$product->measure_id] ?? '' }}</td>
                        <td class="text-center">
                            <button wire:click="openEditModal({{ $product->id }})" class="btn-icon-primary" title="Editar">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="deleteProduct({{ $product->id }})" wire:confirm="¿Eliminar este producto?" class="btn-icon-danger">
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
            <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-md">
                <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-h2 text-text-primary">
                        {{ $editingId ? 'Editar Producto' : 'Nuevo Producto' }}</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-md hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
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
                        <button type="submit" class="btn-primary relative" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="saveProduct" class="transition-opacity">
                                {{ $editingId ? 'Guardar Cambios' : 'Crear Producto' }}
                            </span>
                            <span wire:loading wire:target="saveProduct" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>