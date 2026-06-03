<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Gestión" title="Proyectos">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Proyecto
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar proyecto o cliente..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button" class="btn-secondary shrink-0"
            :class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.statusFilter }">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Filtros
            @if($statusFilter)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
            @endif
        </button>

        <div class="flex-1"></div>

        {{-- Clear button: only when filters active --}}
        @if($search || $statusFilter)
            <button wire:click="$set('search', ''); $set('statusFilter', '');" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
    </div>

    {{-- Expandable Filters Panel --}}
    <div x-show="showFilters" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6">
        <div class="card !bg-surface-hover/50 !p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <div class="flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="statusFilter" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Todos los estados"
                    class="w-full sm:w-48" />
                <p class="text-xs-fluid text-text-muted">Selecciona un estado para filtrar los proyectos</p>
            </div>
        </div>
    </div>

    {{-- Projects Grid --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, statusFilter, previousPage, nextPage, gotoPage" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mb-5 w-full">
            @forelse($projects as $project)
            <div class="card group">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-surface-hover flex items-center justify-center shrink-0">
                            <i data-lucide="hard-hat" class="w-5 h-5 text-text-muted"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-small font-semibold text-text-primary truncate">{{ $project->name }}</h3>
                            <p class="text-xs-fluid text-text-muted">{{ $project->client ?? '—' }}</p>
                        </div>
                    </div>
                    <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" class="shrink-0" />
                </div>

                {{-- Budget progress --}}
                <div class="mb-3.5">
                    <div class="flex items-center justify-between text-xs-fluid mb-1.5">
                        <span class="text-text-muted">Ejecución presupuestal</span>
                        <span
                            class="font-semibold text-text-primary tabular-nums">{{ $project->budget_used_percent }}%</span>
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
                        <a href="{{ url('/proyectos/' . $project->id) }}" class="btn-icon" title="Ver detalle" aria-label="Ver detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                        <button wire:click="openEditModal({{ $project->id }})" class="btn-icon-primary"
                            title="Editar proyecto" aria-label="Editar proyecto">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button wire:click="deleteProject({{ $project->id }})"
                            wire:confirm="¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer."
                            class="btn-icon-danger" title="Eliminar proyecto" aria-label="Eliminar proyecto">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card">
                <x-empty-state icon="folder-open" title="No hay proyectos registrados"
                    message="Crea tu primer proyecto para comenzar." />
            </div>
        @endforelse
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, statusFilter, previousPage, nextPage, gotoPage" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 absolute inset-0 w-full z-10 bg-surface-main">
            @for($i=0; $i<6; $i++)
            <div class="card">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 min-w-0 w-full">
                        <div class="w-9 h-9 rounded-lg skeleton shrink-0"></div>
                        <div class="w-full">
                            <div class="h-4 skeleton rounded w-3/4 mb-1"></div>
                            <div class="h-3 skeleton rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
                <div class="mb-3.5">
                    <div class="flex justify-between mb-1.5">
                        <div class="h-3 skeleton rounded w-1/3"></div>
                        <div class="h-3 skeleton rounded w-8"></div>
                    </div>
                    <div class="w-full h-1.5 skeleton rounded-full mb-1.5"></div>
                    <div class="flex justify-between">
                        <div class="h-3 skeleton rounded w-1/4"></div>
                        <div class="h-3 skeleton rounded w-1/4"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-border">
                    <div class="h-3 skeleton rounded w-24"></div>
                    <div class="flex gap-1">
                        <div class="w-8 h-8 skeleton rounded"></div>
                        <div class="w-8 h-8 skeleton rounded"></div>
                        <div class="w-8 h-8 skeleton rounded"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
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
                    <button type="button" wire:click="$set('showEditModal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary relative" wire:loading.attr="disabled"
                        wire:target="updateProject">
                        <span wire:loading.class="opacity-0" wire:target="updateProject"
                            class="inline-flex items-center gap-1.5 transition-opacity">Guardar Cambios</span>
                        <span wire:loading wire:target="updateProject"
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
                    <button type="submit" class="btn-primary relative" wire:loading.attr="disabled"
                        wire:target="createProject">
                        <span wire:loading.class="opacity-0" wire:target="createProject"
                            class="inline-flex items-center gap-1.5 transition-opacity">Crear Proyecto</span>
                        <span wire:loading wire:target="createProject"
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