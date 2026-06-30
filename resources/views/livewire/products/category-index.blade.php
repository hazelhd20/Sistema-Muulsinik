<div x-data="basicIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $categories->count() }}; init()" data-total-on-page="{{ $categories->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Categorías">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                    Nueva Categoría
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg">
        @php
            $hasActiveFilters = !empty($search);
        @endphp

        @if($categories->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar categoría..." />
                    </div>
                </div>
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($categories->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="layers" title="No se encontraron categorías." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1024px] {{ $categories->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($categories->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
                    @endif
                >
                    <colgroup>
                        <col class="w-14">           {{-- Checkbox --}}
                        <col class="w-[50%]">        {{-- Nombre --}}
                        <col class="w-[20%]">        {{-- Productos --}}
                        <col class="w-[15%]">        {{-- Fecha de Registro --}}
                        <col class="w-28">           {{-- Acciones --}}
                    </colgroup>
                    <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($categories->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Productos</th>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="actions text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage">
                            @if($categories->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="5" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron categorías" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach ($categories as $category)
                                    <tr wire:key="category-row-{{ $category->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $category->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-4 pr-2 text-center" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $category->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="font-medium text-text-primary truncate" title="{{ $category->name }}">
                                                {{ $category->name }}
                                            </p>
                                        </td>
                                        <td>
                                            @if($category->products_count > 0)
                                                <span class="text-sm font-medium text-text-secondary">{{ $category->products_count }} producto{{ $category->products_count !== 1 ? 's' : '' }}</span>
                                            @else
                                                <span class="text-sm font-normal text-text-muted">Sin productos</span>
                                            @endif
                                        </td>
                                        <td class="text-sm font-medium text-text-muted">
                                            {{ $category->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="actions pr-4 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" wire:click="openEditModal({{ $category->id }})" icon="pencil">
                                                                Editar
                                                            </x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta categoría? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $category->id }}] })" danger="true" icon="trash-2">
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
                    @if($categories->isNotEmpty())
                        @foreach($categories as $category)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                 :class="selectedRows.includes('{{ $category->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="category-mobile-card-{{ $category->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $category->id }}" />
                                        <span class="font-bold text-text-primary text-base truncate">{{ $category->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                            </x-slot>
                                            <x-slot name="content">
                                                @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $category->id }})" icon="pencil">Editar</x-dropdown-link>
                                                @endif
                                                @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta categoría? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'delete', params: [{{ $category->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                @endif
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="pl-8 flex flex-col gap-3">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-2xs text-text-muted uppercase font-semibold">Productos</p>
                                        <div>
                                            @if($category->products_count > 0)
                                                <span class="text-sm font-medium text-text-secondary">{{ $category->products_count }} producto{{ $category->products_count !== 1 ? 's' : '' }}</span>
                                            @else
                                                <span class="text-sm font-normal text-text-muted">Sin productos</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-text-secondary">
                                        <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                        <span>Registro: {{ $category->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron categorías" message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="layers" title="No se encontraron categorías." />
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
                                    <x-skeleton class="h-5 w-48 rounded" />
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

        {{-- Bulk Actions Bar --}}
        @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Categorías',
                    description: 'Se eliminarán permanentemente las categorías seleccionadas que no tengan productos.',
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
        @if($categories->total() > 0)
            <x-card.footer class="flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-auto overflow-x-auto">
                    {{ $categories->links(data: ['scrollTo' => false]) }}
                </div>
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
{{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Categoría' : 'Nueva Categoría'" maxWidth="md">
            <form wire:submit="save" class="p-5 space-y-4">
                <x-form-field label="Nombre" required error="{{ $errors->first('name') }}">
                    <input type="text" wire:model="name" class="input" placeholder="Ej. Eléctrico, Plomería">
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="save">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Categoría' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
    <x-confirm-modal />
</div>