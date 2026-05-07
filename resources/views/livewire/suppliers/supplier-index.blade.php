<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Proveedores</h1>
            <p class="text-sm text-text-muted">Administra proveedores y sus vendedores</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nuevo Proveedor
        </button>
    </div>

    @if(session('success'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' }); $el.remove()"
            wire:key="toast-success-{{ microtime(true) }}">
        </div>
    @endif
    @if(session('error'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, icon: 'error', title: '{{ session('error') }}' }); $el.remove()"
            wire:key="toast-error-{{ microtime(true) }}">
        </div>
    @endif

    {{-- Search --}}
    <div class="relative mb-6">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre o RFC..."
            class="input pl-10 max-w-md">
    </div>

    {{-- Suppliers Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($suppliers as $supplier)
            <div class="card hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center shrink-0">
                            <i data-lucide="building-2" class="w-5 h-5 text-sky-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-text-primary truncate">{{ $supplier->trade_name }}</h3>
                            @if($supplier->rfc)
                                <p class="text-xs text-text-muted font-mono">{{ $supplier->rfc }}</p>
                            @endif
                        </div>
                    </div>
                    @if($supplier->category)
                        <span class="badge badge-primary shrink-0">{{ $supplier->category }}</span>
                    @endif
                </div>

                {{-- Notas --}}
                <div class="space-y-1.5 mb-4 text-sm text-text-secondary">
                    @if($supplier->notes)
                        <div class="flex items-start gap-2">
                            <i data-lucide="sticky-note" class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0"></i>
                            <span class="line-clamp-2">{{ $supplier->notes }}</span>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <button wire:click="viewVendors({{ $supplier->id }})"
                        class="text-xs font-medium text-primary-600 hover:text-primary-700 flex items-center gap-1">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        {{ $supplier->vendors_count }} vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}
                    </button>
                    <div class="flex items-center gap-1">
                        <button wire:click="openEditSupplierModal({{ $supplier->id }})"
                            class="p-1.5 rounded-lg hover:bg-gray-100 text-text-muted hover:text-primary-600 transition" title="Editar">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="deleteSupplier({{ $supplier->id }})"
                            wire:confirm="¿Eliminar este proveedor y todos sus vendedores?"
                            class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card text-center py-12">
                <i data-lucide="truck" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                <p class="text-text-muted">No hay proveedores registrados</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $suppliers->links() }}</div>

    {{-- Create/Edit Supplier Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">{{ $editingSupplierId ? 'Editar Proveedor' : 'Nuevo Proveedor' }}</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="saveSupplier" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre comercial *</label>
                            <input wire:model="tradeName" type="text" class="input"
                                placeholder="Ej. Materiales del Sureste">
                            @error('tradeName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Razón social</label>
                            <input wire:model="legalName" type="text" class="input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">RFC</label>
                            <input wire:model="rfc" type="text" class="input" maxlength="13" placeholder="XAXX010101000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Categoría</label>
                            <input wire:model="category" type="text" class="input" placeholder="Ej. Materiales">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Notas</label>
                            <textarea wire:model="notes" class="input min-h-[80px]"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">{{ $editingSupplierId ? 'Guardar Cambios' : 'Registrar Proveedor' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Vendors Modal --}}
    @if($showVendorsModal && $viewingSupplier)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showVendorsModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-text-primary">Vendedores</h2>
                        <p class="text-xs text-text-muted">{{ $viewingSupplier->trade_name }}</p>
                    </div>
                    <button wire:click="$set('showVendorsModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
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
                                    <p class="text-sm font-medium text-text-primary">{{ $vendor->name }}</p>
                                    <p class="text-xs text-text-muted">
                                        {{ $vendor->phone ?? '' }}{{ $vendor->phone && $vendor->email ? ' · ' : '' }}{{ $vendor->email ?? '' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button wire:click="openEditVendor({{ $vendor->id }})" class="p-1 rounded hover:bg-gray-200 text-text-muted hover:text-primary-600 transition" title="Editar">
                                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button wire:click="deleteVendor({{ $vendor->id }})" wire:confirm="¿Eliminar vendedor?"
                                        class="p-1 rounded hover:bg-red-100 text-text-muted hover:text-danger">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-text-muted text-center py-4">Sin vendedores registrados</p>
                        @endforelse
                    </div>

                    {{-- Add vendor form --}}
                    @if($showAddVendor)
                        <form wire:submit="saveVendor" class="space-y-3 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                            <input wire:model="vendorName" type="text" class="input" placeholder="Nombre del vendedor *">
                            @error('vendorName') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            <div class="grid grid-cols-2 gap-3">
                                <input wire:model="vendorPhone" type="tel" class="input" placeholder="Teléfono">
                                <input wire:model="vendorEmail" type="email" class="input" placeholder="Correo">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary text-xs">{{ $editingVendorId ? 'Guardar Cambios' : 'Agregar' }}</button>
                                <button type="button" wire:click="$set('showAddVendor', false)"
                                    class="btn-secondary text-xs">Cancelar</button>
                            </div>
                        </form>
                    @else
                        <button wire:click="$set('showAddVendor', true)" class="btn-secondary w-full">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>Agregar Vendedor
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>