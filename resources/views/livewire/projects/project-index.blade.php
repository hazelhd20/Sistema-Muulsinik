<div x-data="projectIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $projects->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Gestión" title="Proyectos">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Proyecto
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <x-card class="mt-4 border-x-0 rounded-none md:border-x md:rounded-[10px] shadow-none md:shadow-sm">

        @php
            $hasActiveFilters = !empty($search) || !empty($statusFilter) || !empty($periodFilter);
        @endphp
        @if($projects->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="md:rounded-t-lg md:bg-surface-card">
            {{-- Filters Bar --}}
            <div
                class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between w-full p-4 md:px-6 md:py-4">
                {{-- Search --}}
                <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar proyecto o cliente..." />

                {{-- Filters Popover --}}
                @php
                    $activeCount = ($statusFilter ? 1 : 0) + ($periodFilter ? 1 : 0);
                @endphp
                <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                    <x-form-field label="Estado">
                        <x-custom-select x-model="filterStatus" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Todos los estados" />
                    </x-form-field>

                    <x-form-field label="Período (Creación)">
                        <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
                            placeholder="Todos los períodos" />
                    </x-form-field>

                    <x-slot name="footer">
                        <button type="button" @click="clearFilters()"
                            class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                            Limpiar filtros
                        </button>
                        <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                            Aplicar Filtros
                        </x-button>
                    </x-slot>
                </x-filters-popover>
            </div>

            {{-- Active Chips Row --}}
            @if($activeCount > 0)
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
                    @if($statusFilter)
                        @php
                            $statusNames = ['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado'];
                        @endphp
                        <x-filter-chip label="Estado" :value="$statusNames[$statusFilter] ?? $statusFilter"
                            wire:click="$set('statusFilter', '')" />
                    @endif
                    @if($periodFilter)
                        @php
                            $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año'];
                        @endphp
                        <x-filter-chip label="Período" :value="$periodNames[$periodFilter] ?? $periodFilter"
                            wire:click="$set('periodFilter', '')" />
                    @endif
                </div>
            @endif
        </div> {{-- End Header Group --}}
        @endif

        {{-- Projects Table --}}
        <div class="relative">
            <div class="w-full">
                {{-- Desktop View --}}
                <x-card.table class="hidden md:block">
                @if($projects->isEmpty())
                    <div wire:loading.class="hidden" wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="folder" title="No se encontraron proyectos" message="No hay registros que coincidan con tu búsqueda." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1024px] {{ $projects->isEmpty() ? 'hidden' : '' }}"
                    @if($projects->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                    @endif
                >
                        {{-- Definición centralizada de columnas (Solución ideal para distribución justa y personalización) --}}
                        <colgroup>
                            <col class="w-14">           {{-- Checkbox --}}
                            <col class="w-auto">         {{-- Nombre (Ocupa el espacio restante equitativamente) --}}
                            <col class="w-[22%]">        {{-- Cliente (Porcentaje fijo para justa distribución) --}}
                            <col class="w-36">           {{-- Fechas --}}
                            <col class="w-36">           {{-- Presupuesto --}}
                            <col class="w-36">           {{-- Ejecución --}}
                            <col class="w-32">           {{-- Estado --}}
                            <col class="w-28">           {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-th border-b border-border">
                            <tr>
                                <th class="actions text-center pl-6 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $projects->pluck('id')->join(',') }}])" />
                                </th>
                                <x-sortable-header field="name" label="Nombre del Proyecto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="start_date" label="Fechas" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="budget" label="Presupuesto" :sortField="$sortField"
                                    :sortDirection="$sortDirection" class="numeric" align="right" />
                                <th>Ejecución</th>
                                <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="actions pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden"
                            wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage">
                            @if($projects->isNotEmpty())
                                @foreach($projects as $project)
                                    <tr wire:key="project-row-{{ $project->id }}"
                                        class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $project->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions text-center pl-6 pr-2" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $project->id }}" />
                                        </td>
                                        <td class="font-semibold text-text-primary truncate" title="{{ $project->name }}">
                                            {{ $project->name }}
                                        </td>
                                        <td class="truncate text-text-secondary" title="{{ $project->client ?? '—' }}">
                                            {{ $project->client ?? '—' }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1 text-text-primary">
                                                <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted" />
                                                <span>{{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                            </div>
                                        </td>
                                        <td class="numeric">
                                            <div class="text-text-primary font-medium tabular-nums text-right">
                                                ${{ number_format($project->budget, 0, '.', ',') }}</div>
                                            <div class="text-xs text-text-muted mt-0.5 tabular-nums text-right">
                                                ${{ number_format($project->total_expenses, 0, '.', ',') }} gastado</div>
                                        </td>
                                        <td class="min-w-[120px]">
                                            <div class="flex items-center justify-between text-xs mb-1.5">
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
                                        <td class="pr-2">
                                            <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                                        </td>
                                        <td class="actions pr-6" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical"
                                                            class="text-text-muted hover:text-text-primary"
                                                            aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link as="button"
                                                            @click="$dispatch('open-project-detail', { id: {{ $project->id }} })"
                                                            icon="eye">
                                                            Ver detalle
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button"
                                                            wire:click="openEditModal({{ $project->id }})" icon="pencil">
                                                            Editar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button"
                                                            wire:click="deleteProject({{ $project->id }})"
                                                            wire:confirm="¿Eliminar este proyecto? Esta acción no puede deshacerse."
                                                            danger="true" icon="trash-2">
                                                            Eliminar
                                                        </x-dropdown-link>
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden"
                            wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                            class="hidden">
                            @for($i = 0; $i < 6; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions text-center pl-6 pr-2">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-32" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td class="numeric">
                                        <x-skeleton class="h-4 rounded w-20 mb-1 ml-auto" />
                                        <x-skeleton class="h-3 rounded w-16 ml-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-3 rounded w-8 mb-1.5" />
                                        <x-skeleton class="w-full h-1.5 rounded-full" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-6 rounded-full w-20" />
                                    </td>
                                    <td class="actions pr-6">
                                        <div class="flex items-center justify-end">
                                            <x-skeleton class="w-8 h-8 rounded-md" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </x-card.table>

                {{-- Tarjetas Móviles (Mobile View) --}}
                <div class="md:hidden flex flex-col gap-4 p-4">
                    <div wire:loading.class="hidden"
                        wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                        class="flex flex-col gap-4">
                        @if($projects->isNotEmpty())
                            @foreach($projects as $project)
                                <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                                    :class="selectedRows.includes('{{ $project->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                    wire:key="project-mobile-card-{{ $project->id }}">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="flex items-start gap-3">
                                            <div class="pt-0.5">
                                                <x-table-checkbox x-model="selectedRows" value="{{ $project->id }}" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="font-bold text-text-primary text-body">{{ $project->name }}</span>
                                                </div>
                                                <p class="text-xs text-text-secondary mt-1 truncate">
                                                    {{ $project->client ?? 'Sin cliente' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 flex flex-col items-end gap-1.5">
                                            <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 gap-2 text-xs text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                                        <div>
                                            <p class="mb-0.5 text-[10px] uppercase font-semibold">Presupuesto</p>
                                            <span
                                                class="font-semibold text-text-primary tabular-nums">${{ number_format($project->budget, 0, '.', ',') }}</span>
                                        </div>
                                        <div>
                                            <p class="mb-0.5 text-[10px] uppercase font-semibold">Gastado</p>
                                            <span
                                                class="text-text-primary tabular-nums">${{ number_format($project->total_expenses, 0, '.', ',') }}</span>
                                        </div>
                                        <div class="col-span-2 mt-1">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[10px] font-medium text-text-secondary">Ejecución</span>
                                                <span
                                                    class="text-[10px] font-bold text-text-primary tabular-nums">{{ $project->budget_used_percent }}%</span>
                                            </div>
                                            <div class="w-full h-1.5 bg-surface-hover rounded-full overflow-hidden">
                                                @php
                                                    $percent = min($project->budget_used_percent, 100);
                                                    $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
                                                @endphp
                                                <div class="{{ $barColor }} h-full rounded-full transition-all"
                                                    style="width: {{ $percent }}%"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 col-span-2 mt-1">
                                            <x-lucide-calendar class="w-3.5 h-3.5 shrink-0" />
                                            <span>Inicio: {{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <x-button variant="secondary" class="w-full justify-center">
                                                    <x-lucide-more-horizontal class="w-4 h-4" />
                                                    <span class="ml-2">Opciones</span>
                                                </x-button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link as="button"
                                                    @click="$dispatch('open-project-detail', { id: {{ $project->id }} })"
                                                    icon="eye">
                                                    Ver detalle
                                                </x-dropdown-link>
                                                <x-dropdown-link as="button" wire:click="openEditModal({{ $project->id }})"
                                                    icon="pencil">
                                                    Editar
                                                </x-dropdown-link>
                                                <x-dropdown-link as="button" wire:click="deleteProject({{ $project->id }})"
                                                    wire:confirm="¿Eliminar este proyecto? Esta acción no puede deshacerse."
                                                    danger="true" icon="trash-2">
                                                    Eliminar
                                                </x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="bg-surface-card border border-border shadow-sm rounded-xl p-8">
                                <x-empty-state icon="folder" title="No se encontraron proyectos"
                                    message="No hay registros que coincidan con tu búsqueda." />
                            </div>
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden"
                        wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                        class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <div
                                class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex items-start gap-3">
                                        <div class="pt-0.5"><x-skeleton class="w-4 h-4 rounded-sm" /></div>
                                        <div class="min-w-0">
                                            <x-skeleton class="h-5 w-32 rounded mb-1.5" />
                                            <x-skeleton class="h-3 w-24 rounded" />
                                        </div>
                                    </div>
                                    <x-skeleton class="h-6 w-20 rounded-full shrink-0" />
                                </div>
                                <div
                                    class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                                    <div>
                                        <x-skeleton class="h-3 w-20 rounded mb-1.5" />
                                        <x-skeleton class="h-4 w-20 rounded" />
                                    </div>
                                    <div>
                                        <x-skeleton class="h-3 w-16 rounded mb-1.5" />
                                        <x-skeleton class="h-4 w-20 rounded" />
                                    </div>
                                    <div class="col-span-2 mt-1">
                                        <div class="flex justify-between mb-1.5">
                                            <x-skeleton class="h-3 w-12 rounded" />
                                            <x-skeleton class="h-3 w-8 rounded" />
                                        </div>
                                        <x-skeleton class="h-1.5 w-full rounded-full" />
                                    </div>
                                    <div class="col-span-2 mt-1 pt-2 border-t border-border/50">
                                        <x-skeleton class="h-4 w-32 rounded" />
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2 border-t border-border mt-1">
                                    <x-skeleton class="h-9 w-full rounded-md" />
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            <x-bulk-actions-bar>
                <x-button @click="$dispatch('confirm-action', {
                        title: 'Eliminar Proyectos',
                        description: 'Se eliminarán permanentemente los proyectos seleccionados que no tengan dependencias.',
                        confirmLabel: 'Eliminar',
                        variant: 'danger',
                        action: 'bulkDelete',
                        params: []
                    })" variant="danger" icon="trash-2">
                    Eliminar
                </x-button>
            </x-bulk-actions-bar>

        </div>

        {{-- Pagination Footer (Card Footer on Desktop) --}}
        @if($projects->hasPages())
            <x-card.footer>
                {{ $projects->links() }}
            </x-card.footer>
        @endif
    </x-card>

    {{-- Project Modal --}}
    @if($showModal)
        <x-modal show="showModal" :title="$editingId ? 'Editar Proyecto' : 'Nuevo Proyecto'">
            <form wire:submit="saveProject" class="p-5 space-y-4">
                <x-form-field label="Nombre del proyecto" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input" placeholder="Ej. Residencial Los Álamos">
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
                        <input wire:model="budget" type="number" step="0.01" class="input" placeholder="0.00">
                    </x-form-field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field label="Fecha de inicio" error="{{ $errors->first('startDate') }}">
                        <x-date-picker wire:model="startDate" />
                    </x-form-field>
                    <x-form-field label="Fecha estimada de término" error="{{ $errors->first('endDate') }}">
                        <x-date-picker wire:model="endDate" />
                    </x-form-field>
                </div>

                @if($editingId)
                    <x-form-field label="Estado" error="{{ $errors->first('status') }}">
                        <x-custom-select wire:model="status" :options="['activo' => 'Activo', 'en_pausa' => 'En Pausa', 'completado' => 'Completado', 'cancelado' => 'Cancelado']" placeholder="Seleccionar estado..." />
                    </x-form-field>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary"
                        target="saveProject">{{ $editingId ? 'Guardar Cambios' : 'Crear Proyecto' }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <livewire:projects.project-detail-drawer />
</div>