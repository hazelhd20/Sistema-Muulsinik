<div x-data="supplierIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $suppliers->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Red de suministro" title="Proveedores">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Proveedor
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <x-card class="mt-4 mb-6">
        @php
            $activeCount = $categoryFilter ? 1 : 0;
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($suppliers->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="md:rounded-t-lg md:bg-surface-card">
                {{-- Filters Bar --}}
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between w-full p-4 md:px-6 md:py-4">
                    {{-- Search: compact width --}}
                    <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o RFC..." />

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Categoría">
                            <x-custom-select x-model="filterCategory" :options="$categories" placeholder="Todas las categorías" />
                        </x-form-field>

                        <x-slot name="footer">
                            <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
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
                                                        <x-dropdown-link as="button" wire:click="deleteSupplier({{ $supplier->id }})"
                                                            wire:confirm="¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse." danger="true" icon="trash-2">
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

        <div class="md:hidden p-4 flex flex-col gap-4">
            {{-- Tarjetas Móviles (Mobile View) --}}
            <div class="flex flex-col">
                <div wire:loading.class="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($suppliers->isNotEmpty())
                        @foreach($suppliers as $supplier)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors"
                                 :class="selectedRows.includes('{{ $supplier->id }}') ? 'bg-primary-50/50' : ''"
                                 wire:key="supplier-mobile-card-{{ $supplier->id }}">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex items-start gap-3">
                                        <div class="pt-0.5">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $supplier->id }}" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-text-primary text-body">{{ $supplier->trade_name }}</span>
                                            </div>
                                            @if($supplier->rfc)
                                                <p class="text-xs text-text-secondary mt-0.5 font-mono">{{ $supplier->rfc }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                                    <div>
                                        <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Categoría</p>
                                        @if($supplier->category)
                                            <x-dynamic-badge :value="$supplier->category" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Vendedores</p>
                                        <span class="inline-flex items-center gap-1.5 text-xs text-text-secondary justify-end">
                                            <x-lucide-users class="w-3.5 h-3.5 text-text-muted" />
                                            {{ $supplier->vendors_count }}
                                        </span>
                                    </div>
                                    @if($supplier->notes)
                                        <div class="col-span-2 flex items-start gap-1.5 mt-1 pt-2 border-t border-border/50">
                                            <x-lucide-sticky-note class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0" />
                                            <span class="text-text-secondary line-clamp-2">{{ $supplier->notes }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                                    <x-dropdown align="right" width="48">
                                        <x-slot name="trigger">
                                            <x-button variant="secondary" class="w-full justify-center">
                                                <x-lucide-more-horizontal class="w-4 h-4" />
                                                <span class="ml-2">Opciones</span>
                                            </x-button>
                                        </x-slot>

                                        <x-slot name="content">
                                            <x-dropdown-link as="button" wire:click="viewVendors({{ $supplier->id }})" icon="users">
                                                Ver vendedores
                                            </x-dropdown-link>
                                            <x-dropdown-link as="button" wire:click="openEditSupplierModal({{ $supplier->id }})" icon="pencil">
                                                Editar
                                            </x-dropdown-link>
                                            <x-dropdown-link as="button" wire:click="deleteSupplier({{ $supplier->id }})"
                                                wire:confirm="¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                Eliminar
                                            </x-dropdown-link>
                                        </x-slot>
                                    </x-dropdown>
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
                <div wire:loading.class.remove="hidden" wire:target="search, categoryFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative transition-colors opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-start gap-3">
                                    <div class="pt-0.5"><x-skeleton class="w-4 h-4 rounded-sm" /></div>
                                    <div class="min-w-0">
                                        <x-skeleton class="h-5 w-32 rounded mb-1.5" />
                                        <x-skeleton class="h-3 w-24 rounded" />
                                    </div>
                                </div>
                            </div>
                            <div class="bg-surface-hover/50 p-3 rounded-xl border border-border/50 flex flex-col gap-2">
                                <div class="flex justify-between">
                                    <x-skeleton class="h-3 w-16 rounded" />
                                    <x-skeleton class="h-3 w-16 rounded" />
                                </div>
                                <div class="flex justify-between">
                                    <x-skeleton class="h-4 w-20 rounded" />
                                    <x-skeleton class="h-4 w-8 rounded" />
                                </div>
                                <div class="pt-2 border-t border-border/50 mt-1">
                                    <x-skeleton class="h-3 w-full rounded mb-1" />
                                    <x-skeleton class="h-3 w-2/3 rounded" />
                                </div>
                            </div>
                            <div class="flex justify-end pt-2 border-t border-border mt-1">
                                <x-skeleton class="h-9 w-full rounded-md" />
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
        </div>

        {{-- Pagination Footer --}}
        @if($suppliers->hasPages())
            <x-card.footer>
                {{ $suppliers->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </x-card>

    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

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
</div>