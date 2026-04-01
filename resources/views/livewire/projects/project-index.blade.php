<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Proyectos</h1>
            <p class="text-sm text-text-muted">Gestiona y supervisa todos tus proyectos de construcción</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nuevo Proyecto
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar proyecto o cliente..." class="input pl-10">
        </div>
        <select wire:model.live="statusFilter" class="input w-auto min-w-[160px]">
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="en_pausa">En Pausa</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>

    {{-- Projects Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        @forelse($projects as $project)
            <div class="card hover:shadow-md transition-shadow group">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                            <i data-lucide="hard-hat" class="w-5 h-5 text-primary-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-text-primary truncate">{{ $project->name }}</h3>
                            <p class="text-xs text-text-muted">{{ $project->client ?? 'Sin cliente' }}</p>
                        </div>
                    </div>
                    @php
                        $statusColors = [
                            'activo' => 'badge-success',
                            'en_pausa' => 'badge-warning',
                            'completado' => 'badge-primary',
                            'cancelado' => 'badge-danger',
                        ];
                    @endphp
                    <span class="badge {{ $statusColors[$project->status] ?? '' }} shrink-0">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>

                {{-- Budget progress --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-text-muted">Presupuesto</span>
                        <span class="font-semibold text-text-primary">{{ $project->budget_used_percent }}%</span>
                    </div>
                    <div class="w-full h-2 bg-surface-main rounded-full overflow-hidden">
                        @php
                            $percent = min($project->budget_used_percent, 100);
                            $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-500');
                        @endphp
                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-text-muted mt-1">
                        <span>${{ number_format($project->total_expenses, 0, '.', ',') }}</span>
                        <span>${{ number_format($project->budget, 0, '.', ',') }}</span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-1 text-xs text-text-muted">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <span>{{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="{{ url('/proyectos/' . $project->id) }}" class="p-1.5 rounded-lg hover:bg-surface-hover transition text-text-muted hover:text-primary-600">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                        <button
                            wire:click="deleteProject({{ $project->id }})"
                            wire:confirm="¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer."
                            class="p-1.5 rounded-lg hover:bg-red-50 transition text-text-muted hover:text-danger"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card text-center py-12">
                <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-3 text-text-muted opacity-40"></i>
                <h3 class="text-lg font-semibold text-text-primary mb-1">No hay proyectos</h3>
                <p class="text-sm text-text-muted mb-4">Comienza creando tu primer proyecto de construcción</p>
                <button wire:click="openCreateModal" class="btn-primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Crear Proyecto
                </button>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    {{ $projects->links() }}

    {{-- Create Project Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-init="$el.querySelector('input')?.focus()">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">Nuevo Proyecto</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>

                <form wire:submit="createProject" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre del proyecto *</label>
                        <input wire:model="name" type="text" class="input" placeholder="Ej. Residencial Los Álamos">
                        @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Descripción</label>
                        <textarea wire:model="description" class="input" rows="3" placeholder="Descripción breve del proyecto..."></textarea>
                        @error('description') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Cliente</label>
                            <input wire:model="client" type="text" class="input" placeholder="Nombre del cliente">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Presupuesto *</label>
                            <input wire:model="budget" type="number" step="0.01" class="input" placeholder="0.00">
                            @error('budget') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Fecha de inicio</label>
                            <input wire:model="startDate" type="date" class="input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Fecha estimada de término</label>
                            <input wire:model="endDate" type="date" class="input">
                            @error('endDate') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="createProject">Crear Proyecto</span>
                            <span wire:loading wire:target="createProject">Creando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
