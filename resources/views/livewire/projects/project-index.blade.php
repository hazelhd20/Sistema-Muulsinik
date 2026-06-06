<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Gestión" title="Proyectos">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Proyecto
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar proyecto o cliente..." />

        {{-- Filters Toggle Button with counter badge --}}
        <x-button @click="showFilters = !showFilters" variant="secondary" icon="sliders-horizontal" class="shrink-0"
            x-bind:class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.statusFilter }">
            Filtros
            @if($statusFilter)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
            @endif
        </x-button>

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
        <div class="card !p-4">
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

    {{-- Projects Table --}}
    <div class="relative min-h-[200px] mb-5">
        <div wire:loading.class="hidden" wire:target="search, statusFilter, previousPage, nextPage, gotoPage"
            class="w-full">
            <div class="table-container">
                @if($projects->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="name" label="Nombre del Proyecto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="start_date" label="Fechas" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="budget" label="Presupuesto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th>Ejecución</th>
                                <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr class="group">
                                    <td class="font-medium whitespace-nowrap text-text-primary">
                                        <span class="max-w-[200px] truncate"
                                            title="{{ $project->name }}">{{ $project->name }}</span>
                                    </td>
                                    <td class="max-w-[150px] truncate text-text-secondary"
                                        title="{{ $project->client ?? '—' }}">
                                        {{ $project->client ?? '—' }}
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1 text-text-primary">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-text-muted"></i>
                                            <span>{{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-text-primary font-medium tabular-nums">
                                            ${{ number_format($project->budget, 0, '.', ',') }}</div>
                                        <div class="text-xs-fluid text-text-muted mt-0.5 tabular-nums">
                                            ${{ number_format($project->total_expenses, 0, '.', ',') }} gastado</div>
                                    </td>
                                    <td class="min-w-[120px]">
                                        <div class="flex items-center justify-between text-xs-fluid mb-1.5">
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
                                    </td>
                                    <td>
                                        <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <x-button href="{{ route('proyectos.show', $project->id) }}" variant="icon" icon="eye"
                                                title="Ver detalle" aria-label="Ver detalle" wire:navigate />
                                            <x-button wire:click="openEditModal({{ $project->id }})" variant="icon-primary" icon="pencil"
                                                title="Editar proyecto" aria-label="Editar proyecto" />
                                            <x-button wire:click="deleteProject({{ $project->id }})"
                                                wire:confirm="¿Estás seguro de que deseas eliminar este proyecto? Esta acción no se puede deshacer."
                                                variant="icon-danger" icon="trash-2" title="Eliminar proyecto"
                                                aria-label="Eliminar proyecto" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="folder-open" title="No hay proyectos registrados"
                        message="Crea tu primer proyecto para comenzar." />
                @endif
            </div>
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, statusFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre del Proyecto</th>
                            <th>Cliente</th>
                            <th>Fechas</th>
                            <th>Presupuesto</th>
                            <th>Ejecución</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td>
                                    <div class="h-4 skeleton rounded w-32"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-24"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-20"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-20 mb-1"></div>
                                    <div class="h-3 skeleton rounded w-16"></div>
                                </td>
                                <td>
                                    <div class="h-3 skeleton rounded w-8 mb-1.5"></div>
                                    <div class="w-full h-1.5 skeleton rounded-full"></div>
                                </td>
                                <td>
                                    <div class="h-6 skeleton rounded-full w-20"></div>
                                </td>
                                <td class="text-right flex justify-end gap-1">
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    {{ $projects->links() }}

    {{-- Project Modal --}}
    @if($showModal)
        <x-modal show="showModal" :title="$editingId ? 'Editar Proyecto' : 'Nuevo Proyecto'">
            <form wire:submit="saveProject" class="p-5 space-y-4">
                <x-form-field label="Nombre del proyecto" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input"
                        placeholder="Ej. Residencial Los Álamos">
                </x-form-field>

                <x-form-field label="Descripción" error="{{ $errors->first('description') }}">
                    <textarea wire:model="description" class="input" rows="3"
                        placeholder="Descripción breve del proyecto..."></textarea>
                </x-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field label="Cliente" error="{{ $errors->first('client') }}">
                        <input wire:model="client" type="text" class="input" placeholder="Nombre del cliente">
                    </x-form-field>
                    <x-form-field label="Presupuesto" required error="{{ $errors->first('budget') }}">
                        <input wire:model="budget" type="number" step="0.01"
                            class="input" placeholder="0.00">
                    </x-form-field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field label="Fecha de inicio" error="{{ $errors->first('startDate') }}">
                        <input wire:model="startDate" type="date" class="input">
                    </x-form-field>
                    <x-form-field label="Fecha estimada de término" error="{{ $errors->first('endDate') }}">
                        <input wire:model="endDate" type="date" class="input">
                    </x-form-field>
                </div>

                @if($editingId)
                    <x-form-field label="Estado" error="{{ $errors->first('status') }}">
                        <x-custom-select wire:model="status" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Seleccionar estado..." />
                    </x-form-field>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveProject">{{ $editingId ? 'Guardar Cambios' : 'Crear Proyecto' }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>