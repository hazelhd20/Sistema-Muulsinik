<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-widest mb-0.5">Gestión</p>
            <h1 class="text-h1 text-text-primary">Proyectos</h1>
        </div>
        <button wire:click="openCreateModal" class="btn-primary">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Nuevo Proyecto
        </button>
    </div>

    {{-- Flash messages --}}
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
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar proyecto o cliente..."
                class="input pl-10">
        </div>
        <x-custom-select wire:model.live="statusFilter" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Todos los estados"
            class="w-auto min-w-[160px]" />
    </div>

    {{-- Projects Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mb-5">
        @forelse($projects as $project)
            <div class="card group">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                            <i data-lucide="hard-hat" class="w-5 h-5 text-primary-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-small font-semibold text-text-primary truncate">{{ $project->name }}</h3>
                            <p class="text-xs-fluid text-text-muted">{{ $project->client ?? 'Sin cliente' }}</p>
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
                <div class="mb-3.5">
                    <div class="flex items-center justify-between text-xs-fluid mb-1.5">
                        <span class="text-text-muted">Ejecución presupuestal</span>
                        <span class="font-semibold text-text-primary tabular-nums">{{ $project->budget_used_percent }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-surface-main rounded-full overflow-hidden">
                        @php
                            $percent = min($project->budget_used_percent, 100);
                            $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
                        @endphp
                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                            style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs-fluid text-text-muted mt-1.5 tabular-nums">
                        <span>${{ number_format($project->total_expenses, 0, '.', ',') }} ejecutado</span>
                        <span>${{ number_format($project->budget, 0, '.', ',') }}</span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-border">
                    <div class="flex items-center gap-1 text-xs-fluid text-text-muted">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <span>{{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="openEditModal({{ $project->id }})" class="btn-icon-primary" title="Editar">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="deleteProject({{ $project->id }})"
                            wire:confirm="¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer."
                            class="btn-icon-danger" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card text-center py-12">
                <i data-lucide="folder-open" class="w-9 h-9 mx-auto mb-2 text-text-muted opacity-25"></i>
                <p class="text-small text-text-muted">No hay proyectos registrados</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    {{ $projects->links() }}

    {{-- Edit Project Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            x-init="$el.querySelector('input')?.focus()">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showEditModal', false)"></div>
            <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-h2 font-semibold text-text-primary">Editar Proyecto</h2>
                    <button wire:click="$set('showEditModal', false)" class="p-1 rounded-md hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>

                <form wire:submit="updateProject" class="p-5 space-y-4">
                    <div>
                        <label class="label">Nombre del proyecto *</label>
                        <input wire:model="name" type="text" class="input" placeholder="Ej. Residencial Los Álamos">
                        @error('name') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Descripción</label>
                        <textarea wire:model="description" class="input" rows="3"
                            placeholder="Descripción breve del proyecto..."></textarea>
                        @error('description') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Cliente</label>
                            <input wire:model="client" type="text" class="input" placeholder="Nombre del cliente">
                        </div>
                        <div>
                            <label class="label">Presupuesto *</label>
                            <input wire:model="budget" type="number" step="0.01" class="input" placeholder="0.00">
                            @error('budget') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Fecha de inicio</label>
                            <input wire:model="startDate" type="date" class="input">
                        </div>
                        <div>
                            <label class="label">Fecha estimada de término</label>
                            <input wire:model="endDate" type="date" class="input">
                            @error('endDate') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Estado</label>
                        <x-custom-select wire:model="status" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Seleccionar estado..." />
                        @error('status') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showEditModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary relative" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="updateProject"
                                class="transition-opacity">Guardar Cambios</span>
                            <span wire:loading wire:target="updateProject"
                                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                        fill="none" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Create Project Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            x-init="$el.querySelector('input')?.focus()">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-h2 font-semibold text-text-primary">Nuevo Proyecto</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-md hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>

                <form wire:submit="createProject" class="p-5 space-y-4">
                    <div>
                        <label class="label">Nombre del proyecto *</label>
                        <input wire:model="name" type="text" class="input" placeholder="Ej. Residencial Los Álamos">
                        @error('name') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Descripción</label>
                        <textarea wire:model="description" class="input" rows="3"
                            placeholder="Descripción breve del proyecto..."></textarea>
                        @error('description') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Cliente</label>
                            <input wire:model="client" type="text" class="input" placeholder="Nombre del cliente">
                        </div>
                        <div>
                            <label class="label">Presupuesto *</label>
                            <input wire:model="budget" type="number" step="0.01" class="input" placeholder="0.00">
                            @error('budget') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Fecha de inicio</label>
                            <input wire:model="startDate" type="date" class="input">
                        </div>
                        <div>
                            <label class="label">Fecha estimada de
                                término</label>
                            <input wire:model="endDate" type="date" class="input">
                            @error('endDate') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="createProject"
                                class="transition-opacity">Crear Proyecto</span>
                            <span wire:loading wire:target="createProject"
                                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                        fill="none" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>