<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Catálogo de Medidas</h1>
            <p class="text-sm text-text-muted">Gestiona las unidades de medida usadas en el sistema.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulario --}}
        <div class="lg:col-span-1">
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">{{ $editingId ? 'Editar Medida' : 'Nueva Medida' }}</h2>
                <form wire:submit="save">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre *</label>
                        <input type="text" wire:model="name" class="input w-full" placeholder="Ej. Pieza, Metro">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Abreviación</label>
                        <input type="text" wire:model="abbreviation" class="input w-full" placeholder="Ej. pza, m">
                        @error('abbreviation') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-2 justify-end">
                        @if($editingId)
                            <button type="button" wire:click="cancelEdit" class="btn-secondary">Cancelar</button>
                        @endif
                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="w-4 h-4 mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="lg:col-span-2">
            <div class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div class="relative w-72">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" class="input pl-10 w-full" placeholder="Buscar medida...">
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-surface-main text-text-muted text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Abreviación</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($measures as $measure)
                                <tr class="hover:bg-surface-main/50 transition">
                                    <td class="px-4 py-3 font-medium text-text-primary">{{ $measure->name }}</td>
                                    <td class="px-4 py-3">{{ $measure->abbreviation ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="edit({{ $measure->id }})" class="p-1 text-primary-600 hover:bg-primary-50 rounded">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button wire:click="delete({{ $measure->id }})" class="p-1 text-red-600 hover:bg-red-50 rounded ml-1" onclick="confirm('¿Seguro que deseas eliminar esta medida?') || event.stopImmediatePropagation()">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-text-muted">
                                        No se encontraron medidas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $measures->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
