<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Red de suministro" title="Proveedores">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Proveedor
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar por nombre o RFC..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        <div class="flex-1"></div>

        {{-- Clear button: only when search active --}}
        @if($search)
            <button wire:click="$set('search', '');" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
    </div>

    {{-- Suppliers Grid --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 w-full">
            @forelse($suppliers as $supplier)
            <div class="card">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-surface-hover flex items-center justify-center shrink-0">
                            <i data-lucide="building-2" class="w-5 h-5 text-text-muted"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-small font-semibold text-text-primary truncate">{{ $supplier->trade_name }}</h3>
                            @if($supplier->rfc)
                                <p class="text-xs-fluid text-text-muted font-mono">{{ $supplier->rfc }}</p>
                            @endif
                        </div>
                    </div>
                    @if($supplier->category)
                        <x-dynamic-badge :value="$supplier->category" class="shrink-0" />
                    @endif
                </div>

                {{-- Notas --}}
                <div class="space-y-1.5 mb-4 text-body text-text-secondary">
                    @if($supplier->notes)
                        <div class="flex items-start gap-2">
                            <i data-lucide="sticky-note" class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0"></i>
                            <span class="line-clamp-2">{{ $supplier->notes }}</span>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-border">
                    <span class="inline-flex items-center gap-1.5 text-xs-fluid text-text-muted">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        {{ $supplier->vendors_count }} vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button wire:click="viewVendors({{ $supplier->id }})" class="btn-icon" title="Ver vendedores" aria-label="Ver vendedores">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="openEditSupplierModal({{ $supplier->id }})" class="btn-icon-primary"
                            title="Editar proveedor" aria-label="Editar proveedor">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="deleteSupplier({{ $supplier->id }})"
                            wire:confirm="¿Eliminar este proveedor y todos sus vendedores?" class="btn-icon-danger"
                            title="Eliminar proveedor" aria-label="Eliminar proveedor">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card">
                <x-empty-state icon="truck" title="No hay proveedores registrados"
                    message="Agrega un proveedor para comenzar." />
            </div>
        @endforelse
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 absolute inset-0 w-full z-10 bg-surface-main">
            @for($i=0; $i<6; $i++)
            <div class="card">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 min-w-0 w-full">
                        <div class="w-9 h-9 rounded-lg skeleton shrink-0"></div>
                        <div class="w-full">
                            <div class="h-4 skeleton rounded w-3/4 mb-1"></div>
                            <div class="h-3 skeleton rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5 mb-4">
                    <div class="h-3 skeleton rounded w-full"></div>
                    <div class="h-3 skeleton rounded w-5/6"></div>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-border">
                    <div class="h-3 skeleton rounded w-24"></div>
                    <div class="flex gap-1">
                        <div class="w-8 h-8 skeleton rounded"></div>
                        <div class="w-8 h-8 skeleton rounded"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
    </div>

    <div class="mt-4">{{ $suppliers->links() }}</div>

    {{-- Create/Edit Supplier Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingSupplierId ? 'Editar Proveedor' : 'Nuevo Proveedor'">
            <form wire:submit="saveSupplier" class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="label">Nombre comercial *</label>
                        <input wire:model="tradeName" type="text" class="input" placeholder="Ej. Materiales del Sureste">
                        @error('tradeName') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Razón social</label>
                        <input wire:model="legalName" type="text" class="input">
                    </div>
                    <div>
                        <label class="label">RFC</label>
                        <input wire:model="rfc" type="text" class="input" maxlength="13" placeholder="XAXX010101000">
                    </div>
                    <div>
                        <label class="label">Categoría</label>
                        <input wire:model="category" type="text" class="input" placeholder="Ej. Materiales">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Notas</label>
                        <textarea wire:model="notes" class="input min-h-[80px]"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showCreateModal', false)"
                        class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary relative" wire:loading.attr="disabled"
                        wire:target="saveSupplier">
                        <span wire:loading.class="opacity-0" wire:target="saveSupplier"
                            class="inline-flex items-center gap-1.5 transition-opacity">
                            {{ $editingSupplierId ? 'Guardar Cambios' : 'Registrar Proveedor' }}
                        </span>
                        <span wire:loading wire:target="saveSupplier"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Vendors Modal --}}
    @if($showVendorsModal && $viewingSupplier)
        <x-modal show="showVendorsModal" title="Vendedores" :subtitle="$viewingSupplier->trade_name" maxWidth="md">
            <div class="p-6">
                @if(session('vendor_success'))
                    <div x-data
                        x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('vendor_success') }}' }); $el.remove()"
                        wire:key="toast-vendor-{{ microtime(true) }}">
                    </div>
                @endif

                {{-- Existing vendors --}}
                <div class="space-y-3 mb-4">
                    @forelse($viewingSupplier->vendors as $vendor)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-main">
                            <div>
                                <p class="text-body font-medium text-text-primary">{{ $vendor->name }}</p>
                                <p class="text-xs-fluid text-text-muted">
                                    {{ $vendor->phone ?? '' }}{{ $vendor->phone && $vendor->email ? ' · ' : '' }}{{ $vendor->email ?? '' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="openEditVendor({{ $vendor->id }})" class="btn-icon-primary" title="Editar">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <button wire:click="deleteVendor({{ $vendor->id }})" wire:confirm="¿Eliminar vendedor?"
                                    class="btn-icon-danger" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-body text-text-muted text-center py-4">Sin vendedores registrados</p>
                    @endforelse
                </div>

                {{-- Add vendor form --}}
                @if($showAddVendor)
                    <form wire:submit="saveVendor" class="space-y-3 p-4 rounded-lg border border-border bg-surface-main">
                        <input wire:model="vendorName" type="text" class="input" placeholder="Nombre del vendedor *">
                        @error('vendorName') <p class="text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        <div class="grid grid-cols-2 gap-3">
                            <input wire:model="vendorPhone" type="tel" class="input" placeholder="Teléfono">
                            <input wire:model="vendorEmail" type="email" class="input" placeholder="Correo">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary relative text-xs-fluid" wire:loading.attr="disabled">
                                <span wire:loading.class="opacity-0" wire:target="saveVendor" class="transition-opacity">
                                    {{ $editingVendorId ? 'Guardar Cambios' : 'Agregar' }}
                                </span>
                                <span wire:loading wire:target="saveVendor"
                                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                            fill="none" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                </span>
                            </button>
                            <button type="button" wire:click="$set('showAddVendor', false)"
                                class="btn-secondary text-xs-fluid">Cancelar</button>
                        </div>
                    </form>
                @else
                    <button wire:click="$set('showAddVendor', true)" class="btn-secondary w-full">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>Agregar Vendedor
                    </button>
                @endif
            </div>
        </x-modal>
    @endif
</div>