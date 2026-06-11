<div x-data="{ showFilters: false, selectedRows: @entangle('selectedRows') }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nueva Medida
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar medida..." />
    </div>

    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($measures->isNotEmpty())
                    <table>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-on:change="$el.checked ? selectedRows = [...new Set([...(selectedRows || []), ...[{{ $measures->pluck('id')->join(',') }}].map(String)])] : selectedRows = (selectedRows || []).filter(id => ![{{ $measures->pluck('id')->join(',') }}].map(String).includes(id))"
                                        :checked="[{{ $measures->pluck('id')->join(',') }}].length > 0 && [{{ $measures->pluck('id')->join(',') }}].map(String).every(id => (selectedRows || []).includes(id))" />
                                </th>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="abbreviation" label="Abreviación" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Productos</th>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($measures as $measure)
                                <tr wire:key="measure-row-{{ $measure->id }}"
                                    class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                    :class="(selectedRows || []).map(String).includes('{{ $measure->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                    </td>
                                    <td class="font-medium text-text-primary">{{ $measure->name }}</td>
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
                                    <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">
                                                        Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="delete({{ $measure->id }})"
                                                        wire:confirm="¿Eliminar esta medida? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                        Eliminar
                                                    </x-dropdown-link>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="ruler" title="No se encontraron medidas." />
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($measures->isNotEmpty())
            <div class="md:hidden flex flex-col gap-3 mt-2">
                @foreach($measures as $measure)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                         :class="(selectedRows || []).map(String).includes('{{ $measure->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                         wire:key="measure-mobile-card-{{ $measure->id }}">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <x-table-checkbox x-model="selectedRows" value="{{ $measure->id }}" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span class="font-bold text-text-primary text-body">{{ $measure->name }}</span>
                                        @if($measure->abbreviation)
                                            <x-badge variant="secondary">{{ $measure->abbreviation }}</x-badge>
                                        @endif
                                    </div>
                                    <p class="text-xs-fluid text-text-secondary mt-1">
                                        @if($measure->products_count > 0)
                                            <span class="text-info-600 font-medium">{{ $measure->products_count }} productos</span>
                                        @else
                                            <span class="text-text-muted">Sin productos</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-1 pt-2 border-t border-border/50 text-small">
                            <div class="flex items-center gap-1.5 text-text-secondary">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-text-muted"></i>
                                <span>Registro: {{ $measure->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <x-button variant="secondary" class="w-full justify-center">
                                        <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                                        <span class="ml-2">Opciones</span>
                                    </x-button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $measure->id }})" icon="pencil">
                                        Editar
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="delete({{ $measure->id }})"
                                        wire:confirm="¿Eliminar esta medida? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                        Eliminar
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
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
                            <th>Nombre</th>
                            <th>Abreviación</th>
                            <th>Productos</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <x-skeleton class="h-4  rounded w-48" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded-full w-16" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded-full w-24" />
                                </td>
                                <td class="actions justify-end flex gap-1">
                                    <x-skeleton class="w-8 h-8  rounded" />
                                    <x-skeleton class="w-8 h-8  rounded" />
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- Skeletons Móviles --}}
            <div class="md:hidden flex flex-col gap-3 mt-2">
                @for($i = 0; $i < 5; $i++)
                    <div class="card p-4 flex justify-between items-center relative overflow-hidden bg-surface-main">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-skeleton class="h-5 w-24 rounded" />
                                <x-skeleton class="h-5 w-12 rounded-full" />
                            </div>
                            <x-skeleton class="h-5 w-24 rounded-full" />
                        </div>
                        <div class="flex justify-end gap-1 shrink-0 ml-3">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
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

    </div>
    
    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

    <div class="mt-4">{{ $measures->links() }}</div>

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
</div>