<div x-data="expenseIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $expenses->count() }}; init()" data-total-on-page="{{ $expenses->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Control financiero" title="Gastos" icon="wallet">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Registrar Gasto
            </x-button>
        </x-slot:actions>
    </x-page-header>



    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg">
        
        @php
            $hasActiveFilters = !empty($search) || !empty($projectFilter) || !empty($categoryFilter) || !empty($periodFilter) || !empty($userFilter);
        @endphp
        @if($expenses->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
            {{-- Filters Bar --}}
            <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                {{-- Search --}}
                <div class="flex-1 min-w-0">
                    <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar gasto..." />
                </div>

            {{-- Filters Popover --}}
            @php
                $activeCount = ($projectFilter ? 1 : 0) + ($categoryFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($userFilter ? 1 : 0);
            @endphp
            <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                <x-form-field label="Proyecto">
                    <x-custom-select x-model="filterProject" :options="$projects" placeholder="Todos los proyectos" />
                </x-form-field>

                <x-form-field label="Categoría">
                    <x-custom-select x-model="filterCategory" :options="$categories" placeholder="Todas las categorías" />
                </x-form-field>

                <x-form-field label="Creador">
                    <x-custom-select x-model="filterUser" :options="$users->pluck('name', 'id')->toArray()" placeholder="Todos los usuarios" />
                </x-form-field>

                <x-form-field label="Período">
                    <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Rango personalizado']" placeholder="Todos los períodos" />
                </x-form-field>

                <div x-show="filterPeriod === 'custom'" x-collapse class="col-span-full mt-2">
                    <div class="grid grid-cols-2 gap-4">
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
            @if($projectFilter)
                <x-filter-chip label="Proyecto" :value="$projects[$projectFilter] ?? 'Desconocido'" wire:click="$set('projectFilter', '')" />
            @endif
            @if($categoryFilter)
                <x-filter-chip label="Categoría" :value="$categories[$categoryFilter] ?? $categoryFilter" wire:click="$set('categoryFilter', '')" />
            @endif
            @if($userFilter)
                <x-filter-chip label="Usuario" :value="$users->firstWhere('id', $userFilter)?->name ?? 'Desconocido'" wire:click="$set('userFilter', '')" />
            @endif
            @if($periodFilter)
                @php
                    $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Personalizado'];
                    $periodLabel = $periodNames[$periodFilter] ?? $periodFilter;
                    if ($periodFilter === 'custom' && ($dateFrom || $dateTo)) {
                        $periodLabel .= ' (' . ($dateFrom ?: 'Inicio') . ' - ' . ($dateTo ?: 'Hoy') . ')';
                    }
                @endphp
                <x-filter-chip label="Período" :value="$periodLabel" wire:click="$set('periodFilter', ''); $set('dateFrom', ''); $set('dateTo', '')" />
            @endif

            @if($activeCount > 1)
                <x-button wire:click="clearAllFilters" variant="link-danger-muted" icon="eraser" class="!text-xs !min-h-0 ml-auto">
                    Limpiar todo
                </x-button>
            @endif
        </div>
        @endif
        </div> {{-- End Header Group --}}
        @endif

        {{-- Expenses Table --}}
        <div class="relative">
            <div class="w-full">
                {{-- Desktop View --}}
                <x-card.table class="hidden md:block">
                    @if($expenses->isEmpty() && !$hasActiveFilters)
                        <div wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="p-12">
                            <x-empty-state icon="receipt" title="Aún no hay gastos registrados"
                                message="Registra el primer gasto para empezar a llevar el control financiero." />
                        </div>
                    @endif
                    <table class="w-full table-fixed min-w-[1100px] {{ $expenses->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                        @if($expenses->isEmpty())
                            wire:loading.class.remove="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage"
                        @endif
                    >
                        <colgroup>
                            <col class="w-14">           {{-- Checkbox --}}
                            <col class="w-[28%]">        {{-- Concepto --}}
                            <col class="w-[20%]">        {{-- Proyecto --}}
                            <col class="w-[17%]">        {{-- Categoría --}}
                            <col class="w-[12%]">        {{-- Fecha --}}
                            <col class="w-[12%]">        {{-- Monto --}}
                            <col class="w-[11%]">        {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions text-center pl-6 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($expenses->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="concept" label="Concepto" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="project_id" label="Proyecto" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="date" label="Fecha" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="amount" label="Monto" :sortField="$sortField" :sortDirection="$sortDirection" align="right" class="numeric" />
                                <th class="actions pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage">
                            @if($expenses->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="7" class="py-16">
                                        <x-empty-state icon="search" title="No se encontraron resultados"
                                            message="No hay gastos que coincidan con los filtros actuales." />
                                    </td>
                                </tr>
                            @else
                                @foreach($expenses as $expense)
                                <tr wire:key="expense-row-{{ $expense->id }}"
                                    class="group hover:bg-surface-hover transition-colors duration-150"
                                    :class="selectedRows.includes('{{ $expense->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="actions text-center pl-6 pr-2" @click.stop="$event.stopPropagation()">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $expense->id }}" />
                                    </td>
                                    <td class="pr-2 max-w-0">
                                        <p class="font-semibold text-text-primary truncate" title="{{ $expense->concept }}">{{ $expense->concept }}</p>
                                        <p class="text-xs text-text-muted truncate" title="Por: {{ $expense->user->name ?? '—' }}">Por: {{ $expense->user->name ?? '—' }}</p>
                                    </td>
                                    <td class="pr-2 max-w-0">
                                        @if($expense->is_distributed)
                                            <x-badge variant="primary" icon="split">Distribuido</x-badge>
                                        @else
                                            <p class="text-body text-text-secondary truncate" title="{{ $expense->project->name ?? '—' }}">{{ $expense->project->name ?? '—' }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                                    </td>
                                    <td class="text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                                    <td class="numeric font-semibold text-text-primary">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                                    <td class="actions pr-6" @click.stop="$event.stopPropagation()">
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    @if($expense->receipt_file)
                                                        <x-dropdown-link href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" icon="file-text">
                                                            Ver comprobante
                                                        </x-dropdown-link>
                                                    @endif
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este gasto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteExpense', params: [{{ $expense->id }}] })" danger="true" icon="trash-2">
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
                        <tbody wire:loading.class.remove="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions text-center pl-6 pr-2">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-48 mb-1.5" />
                                        <x-skeleton class="h-3 rounded w-32" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-36" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded-full w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td class="numeric">
                                        <x-skeleton class="h-4 rounded w-20 ml-auto" />
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
                <div class="md:hidden flex flex-col gap-4 mt-2">
                    <div wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                        @if($expenses->isNotEmpty())
                            @foreach($expenses as $expense)
                                <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                     :class="selectedRows.includes('{{ $expense->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                     wire:key="expense-mobile-card-{{ $expense->id }}">
                                    
                                    {{-- Cabecera de la Fila --}}
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $expense->id }}" />
                                            <span class="font-bold text-text-primary text-base truncate">{{ $expense->concept }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    @if($expense->receipt_file)
                                                        <x-dropdown-link href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" icon="file-text">Ver comprobante</x-dropdown-link>
                                                    @endif
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este gasto? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteExpense', params: [{{ $expense->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>

                                    {{-- Contenido Indentado --}}
                                    <div class="pl-8 flex flex-col gap-3">
                                        {{-- Subtítulo --}}
                                        <div class="text-xs text-text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <x-lucide-user class="w-3.5 h-3.5 shrink-0" />
                                                <span class="truncate">{{ $expense->user->name ?? 'Sin usuario' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0" />
                                                <span>{{ $expense->date->format('d/m/Y') }}</span>
                                            </span>
                                        </div>

                                        {{-- Datos Financieros --}}
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                            <div>
                                                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Proyecto</p>
                                                @if($expense->is_distributed)
                                                    <x-badge variant="primary" icon="split">Distribuido</x-badge>
                                                @else
                                                    <p class="text-small text-text-primary truncate" title="{{ $expense->project->name ?? '—' }}">{{ $expense->project->name ?? '—' }}</p>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Categoría</p>
                                                <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Monto</p>
                                                <p class="font-bold text-text-primary tabular-nums">${{ number_format($expense->amount, 2, '.', ',') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            @if($hasActiveFilters)
                                <div class="p-12">
                                    <x-empty-state icon="search" title="No se encontraron resultados"
                                        message="No hay gastos que coincidan con los filtros actuales." />
                                </div>
                            @else
                                <div class="p-12">
                                    <x-empty-state icon="receipt" title="Aún no hay gastos registrados"
                                        message="Registra el primer gasto para empezar a llevar el control financiero." />
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                        <x-skeleton class="h-5 w-32 rounded" />
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-skeleton class="w-7 h-7 rounded-md" />
                                    </div>
                                </div>
                                <div class="pl-8 flex flex-col gap-3">
                                    <div class="flex gap-3">
                                        <x-skeleton class="h-3 w-20 rounded" />
                                        <x-skeleton class="h-3 w-16 rounded" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                        <div>
                                            <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                            <x-skeleton class="h-4 w-20 rounded" />
                                        </div>
                                        <div>
                                            <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                            <x-skeleton class="h-5 w-24 rounded-full" />
                                        </div>
                                        <div class="col-span-2">
                                            <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                            <x-skeleton class="h-5 w-16 rounded" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(auth()->user()->hasPermission('gastos.eliminar') || auth()->user()->hasPermission('*'))
                <x-bulk-actions-bar>
                    <x-button
                        @click="$dispatch('confirm-action', {
                            title: 'Eliminar Gastos',
                            description: 'Se eliminarán permanentemente los gastos seleccionados.',
                            confirmLabel: 'Eliminar',
                            variant: 'danger',
                            action: 'bulkDelete',
                            params: []
                        })"
                        variant="danger"
                        icon="trash-2">
                        Eliminar
                    </x-button>
                </x-bulk-actions-bar>
            @endif

        </div>

        {{-- Pagination Footer (Card Footer on Desktop) --}}
        @if($expenses->total() > 0)
        <x-card.footer>
            {{ $expenses->links(data: ['scrollTo' => false]) }}
        </x-card.footer>
        @endif
    </div>

    {{-- Create Modal --}}
    <x-modal show="showCreateModal" title="Registrar Gasto">
        <form wire:submit="createExpense" class="p-5 space-y-4">
            <x-form-field label="Concepto" required error="{{ $errors->first('concept') }}">
                <input wire:model="concept" type="text" class="input" placeholder="Ej. Compra de cemento">
            </x-form-field>

            <div class="grid grid-cols-2 gap-4">
                <x-form-field label="Monto" required error="{{ $errors->first('amount') }}">
                    <input wire:model="amount" type="number" step="0.01" class="input" placeholder="0.00">
                </x-form-field>
                <x-form-field label="Fecha" required error="{{ $errors->first('date') }}">
                    <x-date-picker wire:model="date" />
                </x-form-field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col relative">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="label">Proyecto *</label>
                        <div class="flex items-center gap-1.5">
                            <input type="checkbox" wire:model.live="isDistributed" id="isDistributed"
                                class="rounded border-border accent-primary-600 focus:ring-primary-500 w-3 h-3">
                            <label for="isDistributed"
                                class="text-[10px] uppercase font-bold tracking-wider text-text-secondary cursor-pointer hover:text-text-primary transition-colors">Prorratear
                                (Activos)</label>
                        </div>
                    </div>

                    <x-form-field :error="$errors->first('projectId')">
                        <div x-data="{ distributed: @entangle('isDistributed') }" class="relative">
                            <div x-show="!distributed">
                                <x-custom-select wire:model="projectId" :options="$projects"
                                    placeholder="Seleccionar..." />
                            </div>
                            <div x-show="distributed"
                                class="input flex items-center bg-surface-hover text-text-muted cursor-not-allowed h-[38px]"
                                style="display: none;">
                                <x-lucide-split class="w-4 h-4 mr-2" /> Gasto distribuido
                            </div>
                        </div>
                    </x-form-field>
                </div>
                <x-form-field label="Categoría" required error="{{ $errors->first('category') }}">
                    <x-custom-select wire:model="category" :options="$categories" placeholder="Seleccionar..." />
                </x-form-field>
            </div>

            <x-form-field label="Comprobante (opcional)">
                <x-file-input wire:key="receipt-file" inputId="receipt-file-upload" wire:model="receiptFile" accept=".jpg,.jpeg,.png,.pdf" maxSize="20 MB" />
            </x-form-field>

            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                <x-button type="submit" variant="primary" target="createExpense">
                    Registrar Gasto
                </x-button>
            </div>
        </form>
    </x-modal>
    <x-confirm-modal />
</div>
