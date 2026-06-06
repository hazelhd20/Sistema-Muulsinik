<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nueva Medida
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="search" wire:model.live.debounce.300ms="search" class="input pl-10 pr-10 w-full"
                placeholder="Buscar medida..." @focus="focused = true" @blur="focused = false">
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
            <div class="table-container">
                @if($measures->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="name" label="Nombre" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="abbreviation" label="Abreviación" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($measures as $measure)
                                <tr>
                                    <td class="font-medium text-text-primary">{{ $measure->name }}</td>
                                    <td>
                                        @if($measure->abbreviation)
                                            <span class="badge badge-secondary">{{ $measure->abbreviation }}</span>
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-button wire:click="openEditModal({{ $measure->id }})" variant="icon-primary" icon="pencil" title="Editar medida" />
                                            <x-button wire:click="delete({{ $measure->id }})"
                                                wire:confirm="¿Seguro que deseas eliminar esta medida?" variant="icon-danger" icon="trash-2"
                                                title="Eliminar medida" />
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
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Abreviación</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <div class="h-4 skeleton rounded w-48"></div>
                                </td>
                                <td>
                                    <div class="h-5 skeleton rounded-full w-16"></div>
                                </td>
                                <td class="actions justify-end flex gap-1">
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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