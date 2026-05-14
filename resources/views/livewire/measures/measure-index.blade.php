<div>
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Medidas">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Medida
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="search" wire:model.live.debounce.50ms="search" class="input pl-10"
                placeholder="Buscar medida...">
        </div>
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
                                <button wire:click="openEditModal({{ $measure->id }})"
                                    class="btn-icon-primary" title="Editar medida">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="delete({{ $measure->id }})"
                                    wire:confirm="¿Seguro que deseas eliminar esta medida?"
                                    class="btn-icon-danger" title="Eliminar medida">
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
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-1.5">
                                {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                            </span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                <span class="spinner spinner-sm opacity-80"></span>
                                Guardando…
                            </span>
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif
</div>