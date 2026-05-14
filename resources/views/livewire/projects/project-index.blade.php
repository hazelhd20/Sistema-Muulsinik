<div>
    {{-- Header --}}
    <x-page-header subtitle="Gestión" title="Proyectos">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Proyecto
            </button>
        </x-slot:actions>
    </x-page-header>


    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar proyecto o cliente..."
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
                    <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" class="shrink-0" />
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
                        <a href="{{ url('/proyectos/' . $project->id) }}" class="btn-icon" title="Ver detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                        <button wire:click="openEditModal({{ $project->id }})" class="btn-icon-primary" title="Editar proyecto">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="deleteProject({{ $project->id }})"
                            wire:confirm="¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer."
                            class="btn-icon-danger" title="Eliminar proyecto">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card">
                <x-empty-state icon="folder-open" title="No hay proyectos registrados" message="Crea tu primer proyecto para comenzar." />
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    {{ $projects->links() }}

    {{-- Edit Project Modal --}}
    @if($showEditModal)
        <x-modal show="showEditModal" title="Editar Proyecto">
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
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateProject" class="inline-flex items-center gap-1.5">Guardar Cambios</span>
                            <span wire:loading wire:target="updateProject" class="inline-flex items-center gap-2">
                                <span class="spinner spinner-sm opacity-80"></span>
                                Guardando…
                            </span>
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif

    {{-- Create Project Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" title="Nuevo Proyecto">
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
                            <span wire:loading.remove wire:target="createProject" class="inline-flex items-center gap-1.5">Crear Proyecto</span>
                            <span wire:loading wire:target="createProject" class="inline-flex items-center gap-2">
                                <span class="spinner spinner-sm opacity-80"></span>
                                Creando…
                            </span>
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif
</div>