<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Categorías">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nueva Categoría
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="search" wire:model.live.debounce.300ms="search" class="input pl-10 pr-10 w-full"
                placeholder="Buscar categoría..." @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

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

    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($categories->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th>Productos</th>
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr wire:key="category-row-{{ $category->id }}">
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
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-button wire:click="openEditModal({{ $category->id }})" variant="icon-primary" icon="pencil" title="Editar categoría" />
                                            <x-button wire:click="delete({{ $category->id }})"
                                                wire:confirm="¿Eliminar esta categoría? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2"
                                                title="Eliminar categoría" />
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
                    <div class="card p-4 flex justify-between items-center relative overflow-hidden transition-colors group">
                        
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-text-primary text-body truncate block mb-1">{{ $category->name }}</span>
                            @if($category->products_count > 0)
                                <x-badge variant="info">{{ $category->products_count }} productos</x-badge>
                            @else
                                <x-badge variant="secondary">Sin productos</x-badge>
                            @endif
                        </div>

                        <div class="flex justify-end gap-1 shrink-0 ml-3">
                            <x-button wire:click="openEditModal({{ $category->id }})" variant="icon-primary" icon="pencil" class="text-xs-fluid w-8 h-8" />
                            <x-button wire:click="delete({{ $category->id }})" wire:confirm="¿Eliminar esta categoría? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2" class="text-xs-fluid w-8 h-8" />
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
    </div>

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