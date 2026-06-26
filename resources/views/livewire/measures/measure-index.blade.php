<div x-data="basicIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $measures->count() }}; init()" data-total-on-page="{{ $measures->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nueva Medida
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg md:shadow-sm">
        @php
            $hasActiveFilters = !empty($search);
        @endphp

        @if($measures->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar medida..." />
                    </div>
                </div>
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($measures->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="ruler" title="No se encontraron medidas." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1024px] {{ $measures->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($measures->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
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
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($measures->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="abbreviation" label="Abreviación" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Productos</th>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="actions text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage">
                            @if($measures->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="6" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron medidas" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach ($measures as $measure)
                                    <tr wire:key="measure-row-{{ $measure->id }}"
                                        class="group hover:bg-surface-hover/30 transition-colors"
                                        :class="selectedRows.includes('{{ $measure->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-4 pr-2 text-center" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="font-medium text-text-primary truncate" title="{{ $measure->name }}">{{ $measure->name }}</p>
                                        </td>
                                        <td>
                                            @if($measure->abbreviation)
                                                <x-badge variant="secondary">{{ $measure->abbreviation }}</x-badge>
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($measure->products_count > 0)
                                                <x-badge variant="info">{{ $measure->products_count }} productos</x-badge>
                                            @else
                                                <x-badge variant="secondary">Sin productos</x-badge>
                                            @endif
                                        </td>
                                        <td class="text-text-muted text-small">
                                            {{ $measure->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="actions pr-4 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">
                                                            Editar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">
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
                        <tbody wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-4 pr-2 text-center">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
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
                <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($measures->isNotEmpty())
                        @foreach($measures as $measure)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                 :class="selectedRows.includes('{{ $measure->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="measure-mobile-card-{{ $measure->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                        <span class="font-bold text-text-primary text-base truncate">{{ $measure->name }}</span>
                                        @if($measure->abbreviation)
                                            <x-badge variant="secondary">{{ $measure->abbreviation }}</x-badge>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <button class="p-1 rounded-md text-text-muted hover:bg-surface-hover hover:text-text-primary transition-colors focus:outline-none">
                                                    <x-lucide-more-vertical class="w-5 h-5" />
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">Editar</x-dropdown-link>
                                                <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta medida? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $measure->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="pl-8 flex flex-col gap-3">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-[10px] text-text-muted uppercase font-semibold">Productos</p>
                                        <div>
                                            @if($measure->products_count > 0)
                                                <x-badge variant="info">{{ $measure->products_count }} productos</x-badge>
                                            @else
                                                <x-badge variant="secondary">Sin productos</x-badge>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-text-secondary">
                                        <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                        <span>Registro: {{ $measure->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron medidas" message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="ruler" title="No se encontraron medidas." />
                        </div>
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
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
                        </div>
                    @endfor
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
        @if($measures->hasPages())
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
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Medida' : 'Nueva Medida'" maxWidth="md">
            <form wire:submit="save" class="p-5 space-y-4">
                <x-form-field label="Nombre" required error="{{ $errors->first('name') }}">
                    <input type="text" wire:model="name" class="input" placeholder="Ej. Pieza, Metro">
                </x-form-field>
                <x-form-field label="Abreviación" error="{{ $errors->first('abbreviation') }}">
                    <input type="text" wire:model="abbreviation" class="input" placeholder="Ej. pza, m">
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="save">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
    <x-confirm-modal />
</div>