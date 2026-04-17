<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Catálogo de Productos</h1>
            <p class="text-sm text-text-muted">Catálogo maestro y homologación de nombres por proveedor</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nuevo Producto
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>{{ session('success') }}
        </div>
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
                    <th class="text-center">Aliases</th>
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
                            <button wire:click="viewAliases({{ $product->id }})" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700">
                                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                {{ $product->aliases_count }}
                            </button>
                        </td>
                        <td class="text-center">
                            <button
                                wire:click="deleteProduct({{ $product->id }})"
                                wire:confirm="¿Eliminar este producto y todos sus aliases?"
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
                        <p class="mt-1 text-xs text-text-muted">Nombre estándar que homologa todos los aliases de proveedores</p>
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

    {{-- Aliases Modal --}}
    @if($showAliasModal && $viewingProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showAliasModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-text-primary">Aliases de Homologación</h2>
                        <p class="text-xs text-text-muted">{{ $viewingProduct->canonical_name }} · {{ $units[$viewingProduct->unit] ?? $viewingProduct->unit }}</p>
                    </div>
                    <button wire:click="$set('showAliasModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <div class="p-6">
                    @if(session('alias_success'))
                        <div class="mb-4 p-2 rounded-lg bg-green-50 text-green-700 text-xs">{{ session('alias_success') }}</div>
                    @endif

                    {{-- Info card --}}
                    <div class="p-3 rounded-xl bg-primary-50 border border-primary-100 text-sm text-primary-800 mb-4">
                        <p class="font-medium">¿Qué son los aliases?</p>
                        <p class="text-xs mt-1 text-primary-600">Los aliases mapean cómo cada proveedor llama al mismo producto, permitiendo comparar cotizaciones automáticamente.</p>
                    </div>

                    {{-- Existing aliases --}}
                    <div class="space-y-2 mb-4">
                        @forelse($viewingProduct->aliases as $alias)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-main">
                                <div>
                                    <p class="text-sm font-medium text-text-primary">{{ $alias->alias_name }}</p>
                                    <p class="text-xs text-text-muted">{{ $alias->supplier?->trade_name ?? 'Sin proveedor' }}</p>
                                </div>
                                <button wire:click="deleteAlias({{ $alias->id }})" wire:confirm="¿Eliminar alias?" class="p-1 rounded hover:bg-red-100 text-text-muted hover:text-danger">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-text-muted text-center py-4">Sin aliases registrados</p>
                        @endforelse
                    </div>

                    {{-- Add alias form --}}
                    <form wire:submit="addAlias" class="space-y-3 p-4 rounded-xl border border-gray-200">
                        <p class="text-xs font-semibold text-text-primary uppercase tracking-wider">Agregar alias</p>
                        <input wire:model="aliasName" type="text" class="input" placeholder="Nombre como lo llama el proveedor *">
                        @error('aliasName') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        <x-custom-select 
                            wire:model="aliasSupplierId" 
                            :options="$suppliers->pluck('trade_name', 'id')->toArray()" 
                            placeholder="Proveedor (opcional)" 
                        />
                        <button type="submit" class="btn-primary w-full text-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>Agregar Alias
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
