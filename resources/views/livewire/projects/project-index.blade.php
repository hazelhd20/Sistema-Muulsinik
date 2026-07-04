<div x-data="projectIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $projects->count() }}; init()" data-total-on-page="{{ $projects->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Gestión" title="Proyectos" icon="hard-hat">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus" class="flex-1 sm:flex-initial justify-center">
                Nuevo Proyecto
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">

        @php
            $hasActiveFilters = !empty($search) || !empty($statusFilter) || !empty($periodFilter);
        @endphp
        @if($projects->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    {{-- Search --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar proyecto o cliente..." />
                    </div>

                    {{-- Filters Popover --}}
                    @php
                        $activeCount = ($statusFilter ? 1 : 0) + ($periodFilter ? 1 : 0);
                    @endphp
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterStatus" :options="$statuses" placeholder="Todos los estados" />
                        </x-form-field>

                        <x-form-field label="Período (Creación)">
                            <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Rango personalizado']"
                                placeholder="Todos los períodos" />
                        </x-form-field>

                        <div x-show="filterPeriod === 'custom'" x-collapse>
                            <div class="grid grid-cols-2 gap-4 mt-2">
                                <x-form-field label="Desde">
                                    <x-date-picker x-model="filterDateFrom" :options="['maxDate' => 'today']" placeholder="Fecha inicio" />
                                </x-form-field>
                                <x-form-field label="Hasta">
                                    <x-date-picker x-model="filterDateTo" :options="['maxDate' => 'today']" placeholder="Fecha fin" />
                                </x-form-field>
                            </div>
                        </div>

                        <x-slot name="footer">
                            <x-button type="button" @click="clearFilters()" variant="link-muted">
                                Limpiar todo
                            </x-button>
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
                                $statusNames = $statuses;
                            @endphp
                            <x-filter-chip label="Estado" :value="$statusNames[$statusFilter] ?? $statusFilter"
                                wire:click="$set('statusFilter', '')" />
                        @endif
                        @if($periodFilter)
                            @php
                                $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Personalizado'];
                                $periodLabel = $periodNames[$periodFilter] ?? $periodFilter;
                                if ($periodFilter === 'custom' && ($dateFrom || $dateTo)) {
                                    $periodLabel .= ' (' . ($dateFrom ?: 'Inicio') . ' - ' . ($dateTo ?: 'Hoy') . ')';
                                }
                            @endphp
                            <x-filter-chip label="Período" :value="$periodLabel"
                                wire:click="$set('periodFilter', ''); $set('dateFrom', ''); $set('dateTo', '')" />
                        @endif

                        @if($activeCount > 1)
                            <x-button wire:click="clearAllFilters" variant="link-danger-muted" icon="eraser" class="!min-h-0 ml-auto">
                                Limpiar todo
                            </x-button>
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
                    @if($projects->isEmpty() && !$hasActiveFilters)
                        <div wire:loading.class="hidden"
                            wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage" class="p-12">
                            <x-empty-state icon="folder" title="Aún no hay proyectos"
                                message="Comienza creando un proyecto para gestionar tus gastos y tareas." />
                        </div>
                    @endif
                    <table
                        class="w-full table-fixed min-w-[1200px] {{ $projects->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                        @if($projects->isEmpty()) wire:loading.class.remove="hidden"
                        wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage" @endif>
                        {{-- Definición centralizada de columnas (Solución ideal para distribución justa y
                        personalización) --}}
                        <colgroup>
                            <col class="w-14">           {{-- Checkbox --}}
                            <col class="w-[24%]">        {{-- Nombre --}}
                            <col class="w-[18%]">        {{-- Cliente --}}
                            <col class="w-[12%]">        {{-- Fechas --}}
                            <col class="w-[12%]">        {{-- Presupuesto --}}
                            <col class="w-[12%]">        {{-- Ejecución --}}
                            <col class="w-[9%]">         {{-- Estado --}}
                            <col class="w-28">           {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-main border-b border-border/40">
                            <tr>
                                <th class="actions pl-6 pr-2 text-left">
                                    <x-table-checkbox x-bind:checked="allSelected"
                                        @change="toggleAll({{ json_encode($projects->pluck('id')->toArray()) }})" />
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
                            @if($projects->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="8" class="py-16">
                                        <x-empty-state icon="search" title="No se encontraron resultados"
                                            message="No hay proyectos que coincidan con los filtros actuales." />
                                    </td>
                                </tr>
                            @elseif($projects->isNotEmpty())
                                @foreach($projects as $project)
                                    <tr wire:key="project-row-{{ $project->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $project->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-6 pr-2 text-left" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $project->id }}" />
                                        </td>
                                        <td class="text-body font-bold text-text-primary truncate max-w-0" title="{{ $project->name }}">
                                            {{ $project->name }}
                                        </td>
                                        <td class="text-body font-medium truncate text-text-secondary max-w-0" title="{{ $project->client?->name ?? '—' }}">
                                            {{ $project->client?->name ?? '—' }}
                                        </td>
                                        <td>
                                            <div class="text-body font-medium text-text-secondary">
                                                {{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}
                                            </div>
                                        </td>
                                        <td class="numeric">
                                            <div class="text-body font-bold text-text-primary tabular-nums text-right">
                                                ${{ number_format($project->budget, 0, '.', ',') }}</div>
                                            <div class="text-xs-fluid text-text-muted mt-0.5 tabular-nums text-right">
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
                                        <td class="pr-2">
                                            @if($project->status)
                                                <x-badge variant="{{ $project->status->color() }}">{{ $project->status->label() }}</x-badge>
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="actions pr-6 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link as="button"
                                                            @click="$dispatch('open-project-detail', { id: {{ $project->id }} })"
                                                            icon="eye">
                                                            Ver detalle
                                                        </x-dropdown-link>
                                                        @if(auth()->user()?->hasPermission('proyectos.editar') || auth()->user()?->hasPermission('*'))
                                                            <x-dropdown-link as="button"
                                                                wire:click="openEditModal({{ $project->id }})" icon="pencil">
                                                                Editar
                                                            </x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()?->hasPermission('proyectos.eliminar') || auth()->user()?->hasPermission('*'))
                                                            <x-dropdown-link as="button"
                                                                type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proyecto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteProject', params: [{{ $project->id }}] })"
                                                                danger="true" icon="trash-2">
                                                                Eliminar
                                                            </x-dropdown-link>
                                                        @endif
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
                                    <td class="actions pl-6 pr-2 text-left">
                                        <x-skeleton class="w-4 h-4 rounded-sm" />
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
                                    <td class="actions pr-6 py-3">
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
                <div class="md:hidden flex flex-col gap-4 mt-2">
                    <div wire:loading.class="hidden"
                        wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                        class="flex flex-col gap-4">
                        @if($projects->isNotEmpty())
                            @foreach($projects as $project)
                                <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden"
                                    x-bind:class="selectedRows.includes('{{ $project->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                    wire:key="project-mobile-row-{{ $project->id }}">
                                    
                                    {{-- Cabecera de la Fila --}}
                                    <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $project->id }}" />
                                            <span class="font-bold text-text-primary text-h3 truncate">{{ $project->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                                            
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" @click="$dispatch('open-project-detail', { id: {{ $project->id }} })" icon="eye">Ver detalle</x-dropdown-link>
                                                    @if(auth()->user()?->hasPermission('proyectos.editar') || auth()->user()?->hasPermission('*'))
                                                        <x-dropdown-link as="button" wire:click="openEditModal({{ $project->id }})" icon="pencil">Editar</x-dropdown-link>
                                                    @endif
                                                    @if(auth()->user()?->hasPermission('proyectos.eliminar') || auth()->user()?->hasPermission('*'))
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este proyecto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteProject', params: [{{ $project->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>

                                    {{-- Contenido Principal --}}
                                    <div class="p-4 flex flex-col gap-4">
                                        {{-- Subtítulo --}}
                                        <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <x-lucide-user class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="truncate font-medium">{{ $project->client?->name ?? 'Sin cliente' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="font-medium">{{ $project->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                            </span>
                                        </div>

                                        {{-- Datos Financieros --}}
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-3 border-t border-border/40">
                                            <div>
                                                <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider mb-1">Presupuesto</p>
                                                <p class="text-body font-medium text-text-primary tabular-nums">${{ number_format($project->budget, 0, '.', ',') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider mb-1">Gastado</p>
                                                <p class="text-body font-medium text-text-primary tabular-nums">${{ number_format($project->total_expenses, 0, '.', ',') }}</p>
                                            </div>
                                            <div class="col-span-2 pt-1">
                                                <div class="flex justify-between items-center mb-1.5">
                                                    <span class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Ejecución</span>
                                                    <span class="text-xs-fluid font-bold text-text-primary tabular-nums">{{ $project->budget_used_percent }}%</span>
                                                </div>
                                                <div class="w-full h-1.5 bg-surface-main rounded-full overflow-hidden">
                                                    @php
                                                        $percent = min($project->budget_used_percent, 100);
                                                        $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
                                                    @endphp
                                                    <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $percent }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        @else
                            @if($hasActiveFilters)
                                <div class="p-8">
                                    <x-empty-state icon="search" title="No se encontraron resultados"
                                        message="No hay proyectos que coincidan con los filtros actuales." />
                                </div>
                            @else
                                <div class="p-12">
                                    <x-empty-state icon="folder" title="Aún no hay proyectos"
                                        message="Comienza creando un proyecto para gestionar tus gastos y tareas." />
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden"
                        wire:target="search, statusFilter, periodFilter, previousPage, nextPage, gotoPage"
                        class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden opacity-{{ 100 - ($i * 15) }}">
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                        <x-skeleton class="h-5 w-32 rounded" />
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-skeleton class="h-6 w-16 rounded-full" />
                                        <x-skeleton class="w-8 h-8 rounded-md" />
                                    </div>
                                </div>

                                {{-- Contenido Principal --}}
                                <div class="p-4 flex flex-col gap-4">
                                    {{-- Subtítulo --}}
                                    <div class="flex flex-wrap gap-2">
                                        <x-skeleton class="h-3 w-28 rounded" />
                                        <x-skeleton class="h-3 w-24 rounded" />
                                    </div>

                                    {{-- Datos Financieros --}}
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-3 border-t border-border/40">
                                        <div>
                                            <x-skeleton class="h-3 w-16 mb-1.5 rounded" />
                                            <x-skeleton class="h-4 w-24 rounded" />
                                        </div>
                                        <div>
                                            <x-skeleton class="h-3 w-16 mb-1.5 rounded" />
                                            <x-skeleton class="h-4 w-24 rounded" />
                                        </div>
                                        <div class="col-span-2 pt-1">
                                            <div class="flex justify-between items-center mb-1.5">
                                                <x-skeleton class="h-3 w-16 rounded" />
                                                <x-skeleton class="h-3 w-8 rounded" />
                                            </div>
                                            <x-skeleton class="h-1.5 w-full rounded-full" />
                                        </div>
                                    </div>
                                </div>
                            </x-card>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(auth()->user()->hasPermission('proyectos.eliminar') || auth()->user()->hasPermission('*'))
                <x-bulk-actions-bar>
                    <x-button @click="$dispatch('confirm-action', {
                            title: 'Eliminar Proyectos',
                            description: 'Se eliminarán permanentemente los proyectos seleccionados que no tengan dependencias.',
                            confirmLabel: 'Eliminar',
                            variant: 'danger',
                            action: 'bulkDelete',
                            params: []
                        })" variant="danger" icon="trash-2" size="sm">
                        Eliminar
                    </x-button>
                </x-bulk-actions-bar>
            @endif

        </div>

        {{-- Pagination Footer (Card Footer on Desktop) --}}
        @if($projects->total() > 0)
            <x-card.footer>
                {{ $projects->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Project Modal --}}
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
                    <x-form-field label="Cliente" error="{{ $errors->first('client_id') }}">
                        <x-custom-select wire:model="client_id" :options="$clients" placeholder="Seleccionar cliente (opcional)..." />
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
                        <x-custom-select wire:model="status" :options="$statuses" placeholder="Seleccionar estado..." />
                    </x-form-field>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary"
                        target="saveProject">{{ $editingId ? 'Guardar Cambios' : 'Crear Proyecto' }}</x-button>
                </div>
            </form>
        </x-modal>

    <livewire:projects.project-detail-drawer wire:key="project-detail-drawer" />
    <x-confirm-modal />
</div>
