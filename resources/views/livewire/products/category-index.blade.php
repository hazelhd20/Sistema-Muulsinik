<div>
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Categorías">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Categoría
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="search" wire:model.live.debounce.50ms="search" class="input pl-10 pr-10"
                placeholder="Buscar categoría..."
                @focus="focused = true"
                @blur="focused = false">
            <button
                x-show="$wire.search"
                x-transition
                @click="$wire.search = ''"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th class="actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr wire:key="category-row-{{ $category->id }}">
                        <td class="font-medium text-text-primary">{{ $category->name }}</td>
                        <td class="actions">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openEditModal({{ $category->id }})" class="btn-icon-primary" title="Editar categoría">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="delete({{ $category->id }})" wire:confirm="¿Seguro que deseas eliminar esta categoría?" class="btn-icon-danger" title="Eliminar categoría">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">
                            <x-empty-state icon="layers" title="No se encontraron categorías." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>

    {{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Categoría' : 'Nueva Categoría'" maxWidth="md">
                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label">Nombre *</label>
                        <input type="text" wire:model="name" class="input" placeholder="Ej. Eléctrico, Plomería">
                        @error('name') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-1.5">
                                {{ $editingId ? 'Guardar Cambios' : 'Crear Categoría' }}
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
