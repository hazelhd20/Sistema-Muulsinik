<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Medida
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="search" wire:model.live.debounce.50ms="search" class="input pl-10 pr-10 w-full"
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
    <div class="table-container">
        @if($measures->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <x-sortable-header field="name" label="Nombre" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <x-sortable-header field="abbreviation" label="Abreviación" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($measures as $measure)
                        <tr>
                            <td class="font-medium">{{ $measure->name }}</td>
                            <td>
                                @if($measure->abbreviation)
                                    <span class="badge badge-secondary">{{ $measure->abbreviation }}</span>
                                @else
                                    <span class="text-text-muted">—</span>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEditModal({{ $measure->id }})" class="btn-icon-primary"
                                        title="Editar medida">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="delete({{ $measure->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar esta medida?" class="btn-icon-danger"
                                        title="Eliminar medida">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
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
                    <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                    <x-submit-button target="save">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                    </x-submit-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>