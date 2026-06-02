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
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Abreviación</th>
                    <th class="actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($measures as $measure)
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
                @empty
                    <tr>
                        <td colspan="3">
                            <x-empty-state icon="ruler" title="No se encontraron medidas." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $measures->links() }}</div>

    {{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Medida' : 'Nueva Medida'" maxWidth="md">
            <form wire:submit="save" class="p-5 space-y-4">
                <div>
                    <label class="label">Nombre *</label>
                    <input type="text" wire:model="name" class="input" placeholder="Ej. Pieza, Metro">
                    @error('name') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Abreviación</label>
                    <input type="text" wire:model="abbreviation" class="input" placeholder="Ej. pza, m">
                    @error('abbreviation') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showCreateModal', false)"
                        class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary relative" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.class="opacity-0" wire:target="save"
                            class="inline-flex items-center gap-1.5 transition-opacity">
                            {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                        </span>
                        <span wire:loading wire:target="save"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>