<div x-data="supplierIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $suppliers->count() }}; init()"
    data-total-on-page="{{ $suppliers->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Red de suministro" title="Proveedores" icon="truck">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus" class="w-full sm:w-auto justify-center">
                Nuevo Proveedor
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
        @php
            $activeCount = ($categoryFilter ? 1 : 0) + ($statusFilter ? 1 : 0) + ($trashedFilter ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($suppliers->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div
                class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    {{-- Search: compact width --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o RFC..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                        <x-form-field label="Categoría">
                            <x-custom-select x-model="filterCategory" :options="$categories"
                                placeholder="Todas las categorías" />
                        </x-form-field>

                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterStatus" :options="$statusOptions"
                                placeholder="Cualquiera (todos)" />
                        </x-form-field>

                        <x-form-field label="Estado / papelera">
                            <x-custom-select x-model="filterTrashed" :options="$trashedOptions"
                                placeholder="Activos (por defecto)" />
                        </x-form-field>

                        <x-slot name="footer">
                            <x-button type="button" @click="clearFilters()" variant="link-muted">
                                Limpiar filtros
                            </x-button>
                            <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                Aplicar filtros
                            </x-button>
                        </x-slot>
                    </x-filters-popover>
                </div>

                {{-- Active Chips Row --}}
                @if($activeCount > 0)
                    <div class="flex flex-wrap items-center gap-2 pb-3 md:px-6 md:pb-4 pt-1">
                        @if($categoryFilter)
                            <x-filter-chip label="Categoría" :value="$categoryFilter" wire:click="$set('categoryFilter', '')" />
                        @endif
                        @if($statusFilter)
                            <x-filter-chip label="Estado" :value="$statusOptions[$statusFilter] ?? $statusFilter" wire:click="$set('statusFilter', '')" />
                        @endif
                        @if($trashedFilter)
                            <x-filter-chip label="Papelera" :value="$trashedOptions[$trashedFilter] ?? $trashedFilter" wire:click="$set('trashedFilter', '')" />
                        @endif
                    </div>
                @endif
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                    @if($suppliers->isEmpty() && !$hasActiveFilters)
                        <div wire:loading.class="hidden"
                            wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="p-8">
                            <x-empty-state icon="building-2" title="No se encontraron proveedores"
                                message="No hay registros que coincidan con tu búsqueda." />
                        </div>
                    @endif
                    <table
                        class="w-full table-fixed min-w-[1100px] {{ $suppliers->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                        @if($suppliers->isEmpty()) wire:loading.class.remove="hidden"
                        wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" @endif>
                        <colgroup>
                            <col class="w-14"> {{-- Checkbox --}}
                            <col class="w-[26%]"> {{-- Proveedor --}}
                            <col class="w-[14%]"> {{-- RFC --}}
                            <col class="w-[14%]"> {{-- Categoría --}}
                            <col class="w-[11%]"> {{-- Vendedores --}}
                            <col class="w-[11%]"> {{-- Estado --}}
                            <col class="w-[14%]"> {{-- Notas --}}
                            <col class="w-28"> {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions pl-6 pr-2 text-left">
                                    <x-table-checkbox x-bind:checked="allSelected"
                                        @change="toggleAll({{ json_encode($suppliers->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="trade_name" label="Proveedor" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="rfc" label="RFC" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-table-header>Vendedores</x-table-header>
                                <x-table-header>Estado</x-table-header>
                                <x-table-header>Notas</x-table-header>
                                <x-table-header align="right" class="actions pr-6">Acciones</x-table-header>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden"
                            wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage">
                            @if($suppliers->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="8" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron proveedores"
                                            message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($suppliers as $supplier)
                                    <tr wire:key="supplier-row-{{ $supplier->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150 {{ $supplier->trashed() ? 'opacity-70 bg-danger-50/10' : '' }}"
                                        :class="selectedRows.includes('{{ $supplier->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-6 pr-2 text-left" @click.stop="$event.stopPropagation()">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $supplier->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-body font-bold text-text-primary truncate"
                                                    title="{{ $supplier->trade_name }}">{{ $supplier->trade_name }}</p>
                                                @if($supplier->trashed())
                                                    <x-badge variant="danger" size="sm">Eliminado</x-badge>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($supplier->rfc)
                                                <span
                                                    class="text-small font-mono text-text-secondary uppercase tracking-wider">{{ $supplier->rfc }}</span>
                                            @else
                                                <span class="text-small font-medium text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($supplier->category)
                                                <x-dynamic-badge :value="$supplier->category" />
                                            @else
                                                <span class="text-small font-medium text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-body font-medium text-text-secondary">
                                                {{ $supplier->vendors_count }}
                                                vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($supplier->active)
                                                <x-badge variant="success">Activo</x-badge>
                                            @else
                                                <x-badge variant="danger">Inactivo</x-badge>
                                            @endif
                                        </td>
                                        <td class="max-w-0">
                                            @if($supplier->notes)
                                                <div title="{{ $supplier->notes }}">
                                                    <span
                                                        class="text-small font-normal text-text-secondary truncate">{{ $supplier->notes }}</span>
                                                </div>
                                            @else
                                                <span class="text-small font-medium text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="actions pr-6 py-3" @click.stop="$event.stopPropagation()">
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones"
                                                            title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        @if($supplier->trashed())
                                                            @if(auth()->user()->hasPermission('proveedores.editar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" wire:click="restore({{ $supplier->id }})" icon="rotate-ccw">
                                                                    Restaurar
                                                                </x-dropdown-link>
                                                            @endif
                                                            @if(auth()->user()->hasPermission('proveedores.eliminar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" type="button"
                                                                    @click="$dispatch('confirm-action', { title: 'Eliminar Definitivamente', description: '¿Eliminar permanentemente este proveedor? Esta acción destruirá el registro.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $supplier->id }}] })"
                                                                    danger="true" icon="trash-2">
                                                                    Eliminar Definitivamente
                                                                </x-dropdown-link>
                                                            @endif
                                                        @else
                                                            <x-dropdown-link as="button"
                                                                wire:click="viewVendors({{ $supplier->id }})" icon="users">
                                                                Ver vendedores
                                                            </x-dropdown-link>
                                                            <x-dropdown-link as="button"
                                                                wire:click="openEditSupplierModal({{ $supplier->id }})"
                                                                icon="pencil">
                                                                Editar
                                                            </x-dropdown-link>
                                                            <x-dropdown-link as="button"
                                                                wire:click="toggleActive({{ $supplier->id }})" icon="power">
                                                                {{ $supplier->active ? 'Desactivar' : 'Activar' }}
                                                            </x-dropdown-link>
                                                            <x-dropdown-link as="button" type="button"
                                                                @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteSupplier', params: [{{ $supplier->id }}] })"
                                                                danger="true" icon="trash-2">
                                                                Eliminar
                                                            </x-dropdown-link>
                                                        @endif
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden"
                            wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 6; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-6 pr-2 text-left">
                                        <x-skeleton class="w-4 h-4 rounded-sm" />
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
                                        <x-skeleton class="h-5 rounded w-16 rounded-full" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-40" />
                                    </td>
                                    <td class="actions pr-6 py-3">
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
                    <div wire:loading.class="hidden"
                        wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage"
                        class="flex flex-col gap-4">
                        @if($suppliers->isNotEmpty())
                            @foreach($suppliers as $supplier)
                                <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden {{ $supplier->trashed() ? 'opacity-75 bg-danger-50/10' : '' }}"
                                    x-bind:class="selectedRows.includes('{{ $supplier->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                    wire:key="supplier-mobile-card-{{ $supplier->id }}">

                                    {{-- Cabecera de la Fila --}}
                                    <div
                                        class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $supplier->id }}" />
                                            <span
                                                class="font-bold text-text-primary text-h3 truncate">{{ $supplier->trade_name }}</span>
                                            @if($supplier->trashed())
                                                <x-badge variant="danger" size="sm">Eliminado</x-badge>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            @if($supplier->active)
                                                <x-badge variant="success">Activo</x-badge>
                                            @else
                                                <x-badge variant="danger">Inactivo</x-badge>
                                            @endif

                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones"
                                                        title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    @if($supplier->trashed())
                                                        @if(auth()->user()->hasPermission('proveedores.editar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" wire:click="restore({{ $supplier->id }})" icon="rotate-ccw">Restaurar</x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('proveedores.eliminar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Eliminar Definitivamente', description: '¿Eliminar permanentemente este proveedor? Esta acción destruirá el registro.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $supplier->id }}] })" danger="true" icon="trash-2">Eliminar Definitivamente</x-dropdown-link>
                                                        @endif
                                                    @else
                                                        <x-dropdown-link as="button" wire:click="viewVendors({{ $supplier->id }})"
                                                            icon="users">Ver vendedores</x-dropdown-link>
                                                        <x-dropdown-link as="button"
                                                            wire:click="openEditSupplierModal({{ $supplier->id }})"
                                                            icon="pencil">Editar</x-dropdown-link>
                                                        <x-dropdown-link as="button" wire:click="toggleActive({{ $supplier->id }})"
                                                            icon="power">{{ $supplier->active ? 'Desactivar' : 'Activar' }}</x-dropdown-link>
                                                        <x-dropdown-link as="button" type="button"
                                                            @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proveedor y sus vendedores? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteSupplier', params: [{{ $supplier->id }}] })"
                                                            danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>

                                    {{-- Contenido Principal --}}
                                    <div class="p-4 flex flex-col gap-4">
                                        {{-- Subtítulo --}}
                                        <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <span class="flex items-center gap-1.5 truncate font-mono uppercase">
                                                <x-lucide-building-2 class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="truncate">{{ $supplier->rfc ?? 'Sin RFC' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5 font-medium">
                                                <x-lucide-users class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span>{{ $supplier->vendors_count }}
                                                    vendedor{{ $supplier->vendors_count !== 1 ? 'es' : '' }}</span>
                                            </span>
                                        </div>

                                        {{-- Datos y Detalles --}}
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-3 border-t border-border/40">
                                            <div>
                                                <p
                                                    class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">
                                                    Categoría</p>
                                                @if($supplier->category)
                                                    <x-dynamic-badge :value="$supplier->category" />
                                                @else
                                                    <span class="text-body font-medium text-text-muted">—</span>
                                                @endif
                                            </div>
                                            @if($supplier->notes)
                                                <div class="col-span-2 pt-2 border-t border-border/40">
                                                    <p
                                                        class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">
                                                        Notas</p>
                                                    <p class="text-body font-medium text-text-secondary line-clamp-2">
                                                        {{ $supplier->notes }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="search" title="No se encontraron proveedores"
                                    message="Intenta ajustar tus filtros de búsqueda." />
                            </x-card>
                        @else
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="building-2" title="No se encontraron proveedores"
                                    message="No hay registros que coincidan con tu búsqueda." />
                            </x-card>
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden"
                        wire:target="search, categoryFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage"
                        class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <x-card
                                class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
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
                            </x-card>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        @if(auth()->user()->hasPermission('proveedores.eliminar') || auth()->user()->hasPermission('*'))
            <x-bulk-actions-bar>
                @if($trashedFilter === 'trashed')
                    <x-button @click="$dispatch('confirm-action', {
                                title: 'Eliminar Definitivamente',
                                description: 'Se eliminarán permanentemente los proveedores seleccionados de la base de datos.',
                                confirmLabel: 'Destruir Registros',
                                variant: 'danger',
                                action: 'bulkDelete',
                                params: []
                            })" variant="danger" icon="trash-2">
                        Eliminar Definitivamente
                    </x-button>
                @else
                    <x-button @click="$dispatch('confirm-action', {
                                title: 'Eliminar Proveedores',
                                description: 'Se eliminarán los proveedores seleccionados que no estén en uso.',
                                confirmLabel: 'Eliminar',
                                variant: 'danger',
                                action: 'bulkDelete',
                                params: []
                            })" variant="danger" icon="trash-2">
                        Eliminar
                    </x-button>
                @endif
            </x-bulk-actions-bar>
        @endif
        {{-- Pagination Footer --}}
        @if($suppliers->total() > 0)
            <x-card.footer>
                {{ $suppliers->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
    {{-- Create/Edit Supplier Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingSupplierId ? 'Editar proveedor' : 'Nuevo proveedor'">
            <form wire:submit="saveSupplier" class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-form-field label="Nombre comercial" required error="{{ $errors->first('tradeName') }}">
                            <input wire:model="tradeName" type="text" class="input"
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
                        <x-custom-combobox wire:model="category" :options="$categories"
                            placeholder="Seleccionar o escribir rubro..." />
                    </x-form-field>
                    <div class="col-span-2">
                        <x-form-field label="Notas">
                            <textarea wire:model="notes" class="input min-h-[80px]"></textarea>
                        </x-form-field>
                    </div>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveSupplier">
                        {{ $editingSupplierId ? 'Guardar cambios' : 'Registrar proveedor' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Vendors Drawer --}}
    @if($showVendorsModal && $viewingSupplier)
        <x-drawer show="showVendorsModal" title="Vendedores" :subtitle="$viewingSupplier->trade_name" maxWidth="md">
            {{-- Existing vendors list --}}
            <div class="space-y-3">
                @forelse($viewingSupplier->vendors as $vendor)
                    <div
                        class="flex items-start justify-between p-3.5 rounded-xl border border-border/60 bg-surface-main/50 transition-colors hover:bg-surface-main">
                        <div class="min-w-0 pr-2">
                            <p class="text-small font-bold text-text-primary truncate">{{ $vendor->name }}</p>
                            <div class="flex flex-col gap-1 mt-1.5 text-xs-fluid text-text-secondary">
                                @if($vendor->phone)
                                    <span class="flex items-center gap-1.5 truncate">
                                        <x-lucide-phone class="w-3.5 h-3.5 shrink-0 text-text-muted" />
                                        <span>{{ $vendor->phone }}</span>
                                    </span>
                                @endif
                                @if($vendor->email)
                                    <span class="flex items-center gap-1.5 truncate">
                                        <x-lucide-mail class="w-3.5 h-3.5 shrink-0 text-text-muted" />
                                        <span>{{ $vendor->email }}</span>
                                    </span>
                                @endif
                                @if(!$vendor->phone && !$vendor->email)
                                    <span class="text-text-muted italic">Sin datos de contacto</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <x-button wire:click="openEditVendor({{ $vendor->id }})" variant="icon-primary" icon="edit-2"
                                title="Editar" />
                            <x-button type="button"
                                @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este vendedor? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteVendor', params: [{{ $vendor->id }}] })"
                                variant="icon-danger" icon="trash-2" title="Eliminar" />
                        </div>
                    </div>
                @empty
                    <div class="py-12">
                        <x-empty-state icon="users" title="Sin vendedores"
                            message="Aún no hay vendedores registrados para este proveedor." />
                    </div>
                @endforelse
            </div>

            <x-slot name="footer">
                @if($showAddVendor)
                    <form wire:submit="saveVendor" class="space-y-3">
                        <x-form-field error="{{ $errors->first('vendorName') }}">
                            <input wire:model="vendorName" type="text" class="input" placeholder="Nombre del vendedor *">
                        </x-form-field>
                        <div class="grid grid-cols-2 gap-3">
                            <x-form-field error="{{ $errors->first('vendorPhone') }}">
                                <input wire:model="vendorPhone" type="tel" class="input" placeholder="Teléfono">
                            </x-form-field>
                            <x-form-field error="{{ $errors->first('vendorEmail') }}">
                                <input wire:model="vendorEmail" type="email" class="input" placeholder="Correo">
                            </x-form-field>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                            <x-button wire:click="$set('showAddVendor', false)" variant="soft">Cancelar</x-button>
                            <x-button type="submit" variant="primary" target="saveVendor">
                                {{ $editingVendorId ? 'Guardar cambios' : 'Agregar vendedor' }}
                            </x-button>
                        </div>
                    </form>
                @else
                    <x-button wire:click="$set('showAddVendor', true)" variant="primary" icon="user-plus" class="w-full">
                        Agregar vendedor
                    </x-button>
                @endif
            </x-slot>
        </x-drawer>
    @endif
    <x-confirm-modal />
</div>