<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-widest mb-0.5">Catálogos</p>
            <h1 class="text-h1 text-text-primary">Medidas</h1>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Nueva Medida
        </button>
    </div>

    @if (session()->has('success'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' }); $el.remove()"
            wire:key="toast-success-{{ microtime(true) }}">
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, icon: 'error', title: '{{ session('error') }}' }); $el.remove()"
            wire:key="toast-error-{{ microtime(true) }}">
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
                            <i data-lucide="ruler" class="w-8 h-8 mx-auto mb-2 text-text-muted opacity-25"></i>
                            <p class="text-small text-text-muted">No se encontraron medidas.</p>
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
            <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-md">
                <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-h2 text-text-primary">
                        {{ $editingId ? 'Editar Medida' : 'Nueva Medida' }}
                    </h2>
                    <button wire:click="$set('showCreateModal', false)"
                        class="p-1 rounded-md hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
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
                        <button type="submit" class="btn-primary relative" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="save" class="transition-opacity">
                                {{ $editingId ? 'Guardar Cambios' : 'Crear Medida' }}
                            </span>
                            <span wire:loading wire:target="save" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>