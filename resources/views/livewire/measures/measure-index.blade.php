<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Catálogo de Medidas</h1>
            <p class="text-sm text-text-muted">Gestiona las unidades de medida usadas en el sistema.</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nueva Medida
        </button>
    </div>

    @if (session()->has('success'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' })">
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="input pl-10"
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
                    <th class="text-center">Acciones</th>
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
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEditModal({{ $measure->id }})"
                                    class="btn-icon-primary" title="Editar">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="delete({{ $measure->id }})"
                                    wire:confirm="¿Seguro que deseas eliminar esta medida?"
                                    class="btn-icon-danger" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-12">
                            <i data-lucide="ruler" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                            <p class="text-text-muted">No se encontraron medidas.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $measures->links() }}</div>

    {{-- Create / Edit Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-md">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">
                        {{ $editingId ? 'Editar Medida' : 'Nueva Medida' }}
                    </h2>
                    <button wire:click="$set('showCreateModal', false)"
                        class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre *</label>
                        <input type="text" wire:model="name" class="input" placeholder="Ej. Pieza, Metro">
                        @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Abreviación</label>
                        <input type="text" wire:model="abbreviation" class="input" placeholder="Ej. pza, m">
                        @error('abbreviation') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>