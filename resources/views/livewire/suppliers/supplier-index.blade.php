<div x-data="supplierIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $suppliers->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Red de suministro" title="Proveedores" icon="truck">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Proveedor
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-[10px] md:shadow-sm">
        @php
            $activeCount = $categoryFilter ? 1 : 0;
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($suppliers->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-[10px] md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    {{-- Search: compact width --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o RFC..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Categoría">
                            <x-custom-select x-model="filterCategory" :options="$categories" placeholder="Todas las categorías" />
                        </x-form-field>

                        <x-slot name="footer">
                            <button type="button" @click="clearFilters()" class="btn-link-muted">
                                Limpiar todo
                            </button>
                            <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                Aplicar Filtros
                            </x-button>
                        </x-slot>
                    </x-filters-popover>
                </div>

                {{-- Active Chips Row --}}
                @if($activeCount > 0)
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
                    @if($categoryFilter)
                        <x-filter-chip label="Categoría" :value="$categoryFilter" wire:click="$set('categoryFilter', '')" />
                    @endif
                </div>
                @endif
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($suppliers->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="building-2" title="No se encontraron proveedores" message="No hay registros que coincidan con tu búsqueda." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1100px] {{ $suppliers->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($suppliers->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage"
                    @endif
                >
                    <colgroup>
                        <col class="w-14">           {{-- Checkbox --}}
                        <col class="w-[30%]">        {{-- Proveedor --}}
                        <col class="w-[15%]">        {{-- RFC --}}
                        <col class="w-[15%]">        {{-- Categoría --}}
                        <col class="w-[10%]">        {{-- Vendedores --}}
                        <col class="w-[18%]">        {{-- Notas --}}
                        <col class="w-24">           {{-- Acciones --}}
                    </colgroup>
                    <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $suppliers->pluck('id')->join(',') }}])" />
                                </th>
                                <x-sortable-header field="trade_name" label="Proveedor" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="rfc" label="RFC" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Vendedores</th>
                                <th>Notas</th>
                                <th class="actions text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage">
                            @if($suppliers->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="7" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron proveedores" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($suppliers as $supplier)
                                    <tr wire:key="supplier-row-{{ $supplier->id }}"
                                        class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $supplier->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-4 pr-2 text-center" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $supplier->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="font-semibold text-text-primary truncate"
                                                title="{{ $supplier->trade_name }}">{{ $supplier->trade_name }}</p>
                                        </td>
                                        <td>
                                            @if($supplier->rfc)
                                                <span class="text-xs text-text-muted font-mono">{{ $supplier->rfc }}</span>
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
                                            <span class="inline-flex items-center gap-1.5 text-xs text-text-secondary">
                                                <x-lucide-users class="w-3.5 h-3.5 text-text-muted" />
                                                {{ $supplier->vendors_count }}
                                                vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}
                                            </span>
                                        </td>
                                        <td class="max-w-0">
                                            @if($supplier->notes)
                                                <div class="flex items-center gap-1" title="{{ $supplier->notes }}">
                                                    <x-lucide-sticky-note
                                                        class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                                    <span
                                                        class="text-xs text-text-secondary truncate">{{ $supplier->notes }}</span>
                                                </div>
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="actions pr-4 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link as="button" wire:click="viewVendors({{ $supplier->id }})" icon="users">
                                                            Ver vendedores
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button" wire:click="openEditSupplierModal({{ $supplier->id }})" icon="pencil">
                                                            Editar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteSupplier', params: [{{ $supplier->id }}] })" danger="true" icon="trash-2">
                                                            Eliminar
                                                        </x-dropdown-link>
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 6; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-4 pr-2 text-center">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-32" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-40" />
                                    </td>
                                    <td class="actions pr-4 py-3">
                                        <div class="flex items-center justify-end">
                                            <x-skeleton class="w-8 h-8 rounded-md" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
        </x-card.table>

        <div class="md:hidden flex flex-col gap-4 mt-2">
            {{-- Tarjetas Móviles (Mobile View) --}}
            <div class="flex flex-col">
                <div wire:loading.class="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($suppliers->isNotEmpty())
                        @foreach($suppliers as $supplier)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                 :class="selectedRows.includes('{{ $supplier->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="supplier-mobile-card-{{ $supplier->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $supplier->id }}" />
                                        <span class="font-bold text-text-primary text-base truncate">{{ $supplier->trade_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <button class="p-1 rounded-md text-text-muted hover:bg-surface-hover hover:text-text-primary transition-colors focus:outline-none">
                                                    <x-lucide-more-vertical class="w-5 h-5" />
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <x-dropdown-link as="button" wire:click="viewVendors({{ $supplier->id }})" icon="users">Ver vendedores</x-dropdown-link>
                                                <x-dropdown-link as="button" wire:click="openEditSupplierModal({{ $supplier->id }})" icon="pencil">Editar</x-dropdown-link>
                                                <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteSupplier', params: [{{ $supplier->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="pl-8 flex flex-col gap-3">
                                    {{-- Subtítulo --}}
                                    <div class="text-xs text-text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <x-lucide-building-2 class="w-3.5 h-3.5 shrink-0" />
                                            <span class="truncate font-mono">{{ $supplier->rfc ?? 'Sin RFC' }}</span>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <x-lucide-users class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ $supplier->vendors_count }} vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}</span>
                                        </span>
                                    </div>

                                    {{-- Datos y Detalles --}}
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                        <div>
                                            <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Categoría</p>
                                            @if($supplier->category)
                                                <x-dynamic-badge :value="$supplier->category" />
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </div>
                                        @if($supplier->notes)
                                            <div class="col-span-2 mt-1">
                                                <div class="flex items-start gap-1.5">
                                                    <x-lucide-sticky-note class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0" />
                                                    <span class="text-xs text-text-secondary line-clamp-2">{{ $supplier->notes }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron proveedores" message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="building-2" title="No se encontraron proveedores" message="No hay registros que coincidan con tu búsqueda." />
                        </div>
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                    <x-skeleton class="h-5 w-40 rounded" />
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-skeleton class="w-7 h-7 rounded-md" />
                                </div>
                            </div>
                            <div class="pl-8 flex flex-col gap-3">
                                <div class="flex gap-3">
                                    <x-skeleton class="h-3 w-28 rounded" />
                                    <x-skeleton class="h-3 w-20 rounded" />
                                </div>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                    <div>
                                        <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                        <x-skeleton class="h-5 w-24 rounded-full" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        </div>

        {{-- Bulk Actions Bar --}}
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Proveedores',
                    description: 'Se eliminarán permanentemente los proveedores seleccionados que no estén en uso.',
                    confirmLabel: 'Eliminar',
                    variant: 'danger',
                    action: 'bulkDelete',
                    params: []
                })"
                variant="danger"
                icon="trash-2">
                Eliminar
            </x-button>
        </x-bulk-actions-bar>
        {{-- Pagination Footer --}}
        @if($suppliers->hasPages())
            <x-card.footer>
                {{ $suppliers->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
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
                                <p class="text-xs text-text-muted">
                                    {{ $vendor->phone ?? '' }}{{ $vendor->phone && $vendor->email ? ' · ' : '' }}{{ $vendor->email ?? '' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-button wire:click="openEditVendor({{ $vendor->id }})" variant="icon-primary" icon="edit-2" title="Editar" />
                                <x-button type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este vendedor? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteVendor', params: [{{ $vendor->id }}] })"
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
                            <x-button type="submit" variant="primary" target="saveVendor" class="text-xs">
                                {{ $editingVendorId ? 'Guardar Cambios' : 'Agregar' }}
                            </x-button>
                            <x-button wire:click="$set('showAddVendor', false)" variant="secondary" class="text-xs">Cancelar</x-button>
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
    <x-confirm-modal />
</div>