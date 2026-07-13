<div x-data="measureIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $measures->count() }}; init()" data-total-on-page="{{ $measures->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus" class="w-full sm:w-auto justify-center">
                    Nueva Medida
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
        @php
            $activeCount = ($usageFilter ? 1 : 0) + ($trashedFilter ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($measures->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar medida..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Estado de uso">
                            <x-custom-select x-model="filterUsage" :options="$usageOptions"
                                placeholder="Cualquiera (todas)" />
                        </x-form-field>

                        <x-form-field label="Estado de papelera">
                            <x-custom-select x-model="filterTrashed" :options="$trashedOptions"
                                placeholder="Activas (por defecto)" />
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
                    @if($usageFilter)
                        <x-filter-chip label="Uso" :value="$usageOptions[$usageFilter] ?? $usageFilter" wire:click="$set('usageFilter', '')" />
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
                @if($measures->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="ruler" title="No se encontraron medidas." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1024px] {{ $measures->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($measures->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage"
                    @endif
                >
                    <colgroup>
                        <col class="w-14">           {{-- Checkbox --}}
                        <col class="w-[35%]">        {{-- Nombre --}}
                        <col class="w-[20%]">        {{-- Abreviación --}}
                        <col class="w-[15%]">        {{-- Productos --}}
                        <col class="w-[15%]">        {{-- Fecha de Registro --}}
                        <col class="w-28">           {{-- Acciones --}}
                    </colgroup>
                    <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions pl-6 pr-2 text-left">
                                    <x-table-checkbox x-bind:checked="allSelected"
                                        @change="toggleAll({{ json_encode($measures->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="abbreviation" label="Abreviación" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-table-header>Productos</x-table-header>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-table-header align="right" class="actions pr-6">Acciones</x-table-header>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage">
                            @if($measures->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="6" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron medidas" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach ($measures as $measure)
                                    <tr wire:key="measure-row-{{ $measure->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $measure->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-6 pr-2 text-left" @click.stop="$event.stopPropagation()">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <div class="flex items-center gap-2 truncate">
                                                <p class="text-body font-bold text-text-primary truncate" title="{{ $measure->name }}">{{ $measure->name }}</p>
                                                @if($measure->trashed())
                                                    <x-badge variant="danger" size="sm" class="shrink-0">Eliminada</x-badge>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-body font-medium text-text-secondary">
                                            @if($measure->abbreviation)
                                                {{ strtolower($measure->abbreviation) }}
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($measure->products_count > 0)
                                                <span class="text-body font-medium text-text-secondary">{{ $measure->products_count }} producto{{ $measure->products_count !== 1 ? 's' : '' }}</span>
                                            @else
                                                <span class="text-body font-normal text-text-muted">Sin productos</span>
                                            @endif
                                        </td>
                                        <td class="text-body font-medium text-text-muted">
                                            {{ $measure->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="actions pr-6 py-3" @click.stop="$event.stopPropagation()">
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        @if($measure->trashed())
                                                            @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" wire:click="restore({{ $measure->id }})" icon="rotate-ccw">
                                                                    Restaurar
                                                                </x-dropdown-link>
                                                            @endif
                                                            @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Eliminación', description: '¿Eliminar permanentemente esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">
                                                                    Eliminar Definitivamente
                                                                </x-dropdown-link>
                                                            @endif
                                                        @else
                                                            @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">
                                                                    Editar
                                                                </x-dropdown-link>
                                                            @endif
                                                            @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                                <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">
                                                                    Eliminar
                                                                </x-dropdown-link>
                                                            @endif
                                                        @endif
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-6 pr-2 text-left">
                                        <x-skeleton class="w-4 h-4 rounded-sm" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-48" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded-full w-16" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded-full w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
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
            <div class="flex flex-col">
                <div wire:loading.class="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($measures->isNotEmpty())
                        @foreach($measures as $measure)
                            <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden"
                                 x-bind:class="selectedRows.includes('{{ $measure->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                 wire:key="measure-mobile-card-{{ $measure->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                        <span class="font-bold text-text-primary text-h3 truncate">{{ $measure->name }}</span>
                                        @if($measure->abbreviation)
                                            <span class="text-body font-medium text-text-secondary">({{ strtolower($measure->abbreviation) }})</span>
                                        @endif
                                        @if($measure->trashed())
                                            <x-badge variant="danger" size="sm" class="shrink-0">Eliminada</x-badge>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                            </x-slot>
                                            <x-slot name="content">
                                                @if($measure->trashed())
                                                    @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" wire:click="restore({{ $measure->id }})" icon="rotate-ccw">Restaurar</x-dropdown-link>
                                                    @endif
                                                    @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Eliminación', description: '¿Eliminar permanentemente esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">Eliminar Definitivamente</x-dropdown-link>
                                                    @endif
                                                @else
                                                    @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">Editar</x-dropdown-link>
                                                    @endif
                                                    @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                    @endif
                                                @endif
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="p-4 flex flex-col gap-3">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider">Productos</p>
                                        <div>
                                            @if($measure->products_count > 0)
                                                <span class="text-body font-medium text-text-secondary">{{ $measure->products_count }} producto{{ $measure->products_count !== 1 ? 's' : '' }}</span>
                                            @else
                                                <span class="text-body font-normal text-text-muted">Sin productos</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-body text-text-secondary font-medium pt-2 border-t border-border/40">
                                        <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                        <span>Registro: {{ $measure->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </x-card>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <x-card class="p-8 sm:p-12 text-center">
                            <x-empty-state icon="search" title="No se encontraron medidas" message="Intenta ajustar tus filtros de búsqueda." />
                        </x-card>
                    @else
                        <x-card class="p-8 sm:p-12 text-center">
                            <x-empty-state icon="ruler" title="No se encontraron medidas." />
                        </x-card>
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, usageFilter, trashedFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                    @for($i = 0; $i < 4; $i++)
                        <x-card class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                    <x-skeleton class="h-5 w-24 rounded" />
                                    <x-skeleton class="h-5 w-12 rounded-full" />
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-skeleton class="w-7 h-7 rounded-md" />
                                </div>
                            </div>
                            <div class="pl-8 flex flex-col gap-3">
                                <div>
                                    <x-skeleton class="h-2 w-16 rounded mb-1.5" />
                                    <x-skeleton class="h-5 w-24 rounded-full" />
                                </div>
                                <x-skeleton class="h-4 w-32 rounded" />
                            </div>
                        </x-card>
                    @endfor
                </div>
            </div>
        </div>
        </div>
        </div>

        @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Medidas',
                    description: 'Se eliminarán permanentemente las medidas seleccionadas que no tengan productos ni requisiciones asociadas.',
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
        @endif
        @if($measures->total() > 0)
            <x-card.footer class="flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-auto overflow-x-auto">
                    {{ $measures->links(data: ['scrollTo' => false]) }}
                </div>
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
{{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar medida' : 'Nueva medida'" maxWidth="md">
            <form wire:submit="save" class="p-5 space-y-4">
                <x-form-field label="Nombre" required error="{{ $errors->first('name') }}">
                    <input type="text" wire:model="name" class="input" placeholder="Ej. Pieza, Metro">
                </x-form-field>
                <x-form-field label="Abreviación" error="{{ $errors->first('abbreviation') }}">
                    <input type="text" wire:model="abbreviation" class="input" placeholder="Ej. pza, m">
                </x-form-field>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="save">
                        {{ $editingId ? 'Guardar cambios' : 'Crear medida' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
    <x-confirm-modal />
</div>