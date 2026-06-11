<div x-data="basicIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $categories->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Categorías">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nueva Categoría
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar categoría..." />
    </div>

    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($categories->isNotEmpty())
                    <table>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $categories->pluck('id')->join(',') }}])" />
                                </th>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th>Productos</th>
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr wire:key="category-row-{{ $category->id }}"
                                    class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                    :class="selectedRows.includes('{{ $category->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $category->id }}" />
                                    </td>
                                    <td class="font-medium text-text-primary">
                                        {{ $category->name }}
                                    </td>
                                    <td>
                                        @if($category->products_count > 0)
                                            <x-badge variant="info">{{ $category->products_count }} productos</x-badge>
                                        @else
                                            <x-badge variant="secondary">Sin productos</x-badge>
                                        @endif
                                    </td>
                                    <td class="text-text-muted text-small">
                                        {{ $category->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $category->id }})" icon="pencil">
                                                        Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="delete({{ $category->id }})"
                                                        wire:confirm="¿Eliminar esta categoría? Esta acción no puede deshacerse." danger="true" icon="trash-2">
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
                    <x-empty-state icon="layers" title="No se encontraron categorías." />
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($categories->isNotEmpty())
            <div class="md:hidden flex flex-col gap-3 mt-2">
                @foreach($categories as $category)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                         :class="selectedRows.includes('{{ $category->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                         wire:key="category-mobile-card-{{ $category->id }}">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <x-table-checkbox x-model="selectedRows" value="{{ $category->id }}" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-text-primary text-body">{{ $category->name }}</span>
                                    </div>
                                    <p class="text-xs-fluid text-text-secondary mt-1">
                                        @if($category->products_count > 0)
                                            <span class="text-info-600 font-medium">{{ $category->products_count }} productos</span>
                                        @else
                                            <span class="text-text-muted">Sin productos</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-1 pt-2 border-t border-border/50 text-small">
                            <div class="flex items-center gap-1.5 text-text-secondary">
                                <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted" />
                                <span>Registro: {{ $category->created_at->format('d/m/Y') }}</span>
                            </div>
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
                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $category->id }})" icon="pencil">
                                        Editar
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="delete({{ $category->id }})"
                                        wire:confirm="¿Eliminar esta categoría? Esta acción no puede deshacerse." danger="true" icon="trash-2">
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
                            <x-skeleton class="h-5 w-32 rounded mb-2" />
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

    </div>
    
    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

    <div class="mt-4">{{ $categories->links() }}</div>

    {{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Categoría' : 'Nueva Categoría'" maxWidth="md">
            <form wire:submit="save" class="p-5 space-y-4">
                <x-form-field label="Nombre" required error="{{ $errors->first('name') }}">
                    <input type="text" wire:model="name" class="input" placeholder="Ej. Eléctrico, Plomería">
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="save">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Categoría' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>