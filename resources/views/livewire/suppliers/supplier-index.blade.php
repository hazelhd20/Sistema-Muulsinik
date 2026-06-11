<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Red de suministro" title="Proveedores">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Proveedor
            </x-button>
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
            <div class="table-container hidden md:block">
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
                                    <td class="font-medium whitespace-nowrap text-text-primary">
                                        <span class="max-w-[200px] truncate"
                                            title="{{ $supplier->trade_name }}">{{ $supplier->trade_name }}</span>
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
                                            <x-button wire:click="viewVendors({{ $supplier->id }})" variant="icon" icon="users"
                                                title="Ver vendedores" aria-label="Ver vendedores" />
                                            <x-button wire:click="openEditSupplierModal({{ $supplier->id }})"
                                                variant="icon-primary" icon="pencil" title="Editar proveedor" aria-label="Editar proveedor" />
                                            <x-button wire:click="deleteSupplier({{ $supplier->id }})"
                                                wire:confirm="¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse."
                                                variant="icon-danger" icon="trash-2" title="Eliminar proveedor"
                                                aria-label="Eliminar proveedor" />
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

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($suppliers->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($suppliers as $supplier)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors group">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="font-bold text-text-primary text-body truncate block">{{ $supplier->trade_name }}</span>
                                @if($supplier->rfc)
                                    <span class="text-xs-fluid text-text-muted font-mono mt-0.5 block">{{ $supplier->rfc }}</span>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                @if($supplier->category)
                                    <x-dynamic-badge :value="$supplier->category" />
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs-fluid text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                            <div class="flex items-center gap-1.5 col-span-2">
                                <i data-lucide="users" class="w-3.5 h-3.5 shrink-0"></i>
                                <span>{{ $supplier->vendors_count }} vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}</span>
                            </div>
                            
                            @if($supplier->notes)
                                <div class="col-span-2 flex items-start gap-1.5 mt-1">
                                    <i data-lucide="sticky-note" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                                    <span class="line-clamp-2">{{ $supplier->notes }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-button wire:click="viewVendors({{ $supplier->id }})" variant="icon" icon="users" class="text-xs-fluid w-8 h-8" />
                            <x-button wire:click="openEditSupplierModal({{ $supplier->id }})" variant="icon-primary" icon="pencil" class="text-xs-fluid w-8 h-8" />
                            <x-button wire:click="deleteSupplier({{ $supplier->id }})" wire:confirm="¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2" class="text-xs-fluid w-8 h-8" />
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
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
                                    <x-skeleton class="h-4  rounded w-32" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-40" />
                                </td>
                                <td class="text-right flex justify-end gap-1">
                                    <x-skeleton class="w-8 h-8  rounded" />
                                    <x-skeleton class="w-8 h-8  rounded" />
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
                        <div class="bg-surface-hover/50 p-3 rounded-xl border border-border/50 flex flex-col gap-2">
                            <x-skeleton class="h-3 w-28 rounded" />
                            <x-skeleton class="h-3 w-full rounded mt-1" />
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
                                class="input"
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
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveSupplier">
                        {{ $editingSupplierId ? 'Guardar Cambios' : 'Registrar Proveedor' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Vendors Modal --}}
    @if($showVendorsModal && $viewingSupplier)
        <x-modal show="showVendorsModal" title="Vendedores" :subtitle="$viewingSupplier->trade_name" maxWidth="md">
            <div class="p-6">

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
                                <x-button wire:click="openEditVendor({{ $vendor->id }})" variant="icon-primary" icon="edit-2" title="Editar" />
                                <x-button wire:click="deleteVendor({{ $vendor->id }})" wire:confirm="¿Eliminar este vendedor? Esta acción no puede deshacerse."
                                    variant="icon-danger" icon="trash-2" title="Eliminar" />
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
                            <input wire:model="vendorName" type="text" class="input"
                                placeholder="Nombre del vendedor *">
                        </x-form-field>
                        <div class="grid grid-cols-2 gap-3">
                            <x-form-field error="{{ $errors->first('vendorPhone') }}">
                                <input wire:model="vendorPhone" type="tel" class="input" placeholder="Teléfono">
                            </x-form-field>
                            <x-form-field error="{{ $errors->first('vendorEmail') }}">
                                <input wire:model="vendorEmail" type="email" class="input" placeholder="Correo">
                            </x-form-field>
                        </div>
                        <div class="flex gap-2">
                            <x-button type="submit" variant="primary" target="saveVendor" class="text-xs-fluid">
                                {{ $editingVendorId ? 'Guardar Cambios' : 'Agregar' }}
                            </x-button>
                            <x-button wire:click="$set('showAddVendor', false)" variant="secondary" class="text-xs-fluid">Cancelar</x-button>
                        </div>
                    </form>
                @else
                    <x-button wire:click="$set('showAddVendor', true)" variant="secondary" icon="user-plus" class="w-full">
                        Agregar Vendedor
                    </x-button>
                @endif
            </div>
        </x-modal>
    @endif
</div>