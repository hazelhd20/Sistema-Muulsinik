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
        <x-search-input wire:model.live.debounce.50ms="search" placeholder="Buscar por nombre o RFC..." />

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

    {{-- Suppliers Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container">
                @if($suppliers->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="trade_name" label="Proveedor" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="rfc" label="RFC" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Vendedores</th>
                                <th>Notas</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppliers as $supplier)
                                <tr class="group">
                                    <td class="font-medium whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-surface-hover flex items-center justify-center shrink-0">
                                                <i data-lucide="building-2" class="w-4 h-4 text-text-muted"></i>
                                            </div>
                                            <span class="max-w-[200px] truncate"
                                                title="{{ $supplier->trade_name }}">{{ $supplier->trade_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($supplier->rfc)
                                            <span class="text-xs-fluid text-text-muted font-mono">{{ $supplier->rfc }}</span>
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($supplier->category)
                                            <x-dynamic-badge :value="$supplier->category" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 text-xs-fluid text-text-secondary">
                                            <i data-lucide="users" class="w-3.5 h-3.5 text-text-muted"></i>
                                            {{ $supplier->vendors_count }}
                                            vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($supplier->notes)
                                            <div class="flex items-start gap-1 max-w-[250px]" title="{{ $supplier->notes }}">
                                                <i data-lucide="sticky-note"
                                                    class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0"></i>
                                                <span
                                                    class="text-xs-fluid text-text-secondary truncate">{{ $supplier->notes }}</span>
                                            </div>
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="viewVendors({{ $supplier->id }})" class="btn-icon"
                                                title="Ver vendedores" aria-label="Ver vendedores">
                                                <i data-lucide="users" class="w-4 h-4"></i>
                                            </button>
                                            <button wire:click="openEditSupplierModal({{ $supplier->id }})"
                                                class="btn-icon-primary" title="Editar proveedor" aria-label="Editar proveedor">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </button>
                                            <button wire:click="deleteSupplier({{ $supplier->id }})"
                                                wire:confirm="¿Eliminar este proveedor y todos sus vendedores?"
                                                class="btn-icon-danger" title="Eliminar proveedor"
                                                aria-label="Eliminar proveedor">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="truck" title="No hay proveedores registrados"
                        message="Agrega un proveedor para comenzar." />
                @endif
            </div>
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>RFC</th>
                            <th>Categoría</th>
                            <th>Vendedores</th>
                            <th>Notas</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg skeleton shrink-0"></div>
                                        <div class="h-4 skeleton rounded w-32"></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-20"></div>
                                </td>
                                <td>
                                    <div class="h-5 skeleton rounded-full w-24"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-20"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-40"></div>
                                </td>
                                <td class="text-right flex justify-end gap-1">
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $suppliers->links() }}</div>

    {{-- Create/Edit Supplier Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingSupplierId ? 'Editar Proveedor' : 'Nuevo Proveedor'">
            <form wire:submit="saveSupplier" class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-form-field label="Nombre comercial" required error="{{ $errors->first('tradeName') }}">
                            <input wire:model="tradeName" type="text"
                                class="input @error('tradeName') input-error @enderror"
                                placeholder="Ej. Materiales del Sureste">
                        </x-form-field>
                    </div>
                    <x-form-field label="Razón social">
                        <input wire:model="legalName" type="text" class="input">
                    </x-form-field>
                    <x-form-field label="RFC">
                        <input wire:model="rfc" type="text" class="input" maxlength="13" placeholder="XAXX010101000">
                    </x-form-field>
                    <x-form-field label="Categoría">
                        <input wire:model="category" type="text" class="input" placeholder="Ej. Materiales">
                    </x-form-field>
                    <div class="col-span-2">
                        <x-form-field label="Notas">
                            <textarea wire:model="notes" class="input min-h-[80px]"></textarea>
                        </x-form-field>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showCreateModal', false)"
                        class="btn-secondary">Cancelar</button>
                    <x-submit-button target="saveSupplier">
                        {{ $editingSupplierId ? 'Guardar Cambios' : 'Registrar Proveedor' }}
                    </x-submit-button>
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
                        <x-form-field error="{{ $errors->first('vendorName') }}">
                            <input wire:model="vendorName" type="text" class="input @error('vendorName') input-error @enderror"
                                placeholder="Nombre del vendedor *">
                        </x-form-field>
                        <div class="grid grid-cols-2 gap-3">
                            <input wire:model="vendorPhone" type="tel" class="input" placeholder="Teléfono">
                            <input wire:model="vendorEmail" type="email" class="input" placeholder="Correo">
                        </div>
                        <div class="flex gap-2">
                            <x-submit-button target="saveVendor" class="text-xs-fluid">
                                {{ $editingVendorId ? 'Guardar Cambios' : 'Agregar' }}
                            </x-submit-button>
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