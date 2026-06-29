<div x-data="quickBudgetIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $budgets->count() }}; init()" data-total-on-page="{{ $budgets->count() }}">
    <x-page-header subtitle="Trabajos menores" title="Cotizador Rápido">
        <x-slot:actions>
            <x-button href="{{ route('cotizador.wizard') }}" variant="primary" icon="calculator" wire:navigate>
                Nueva Cotización
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <x-card class="mt-4 mb-6">
        @php
            $activeCount = $periodFilter ? 1 : 0;
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($budgets->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="md:rounded-t-lg md:bg-surface-card">
                {{-- Filters Bar --}}
                <div
                    class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between w-full p-4 md:px-6 md:py-4">
                    <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar cotización..." />

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterStatus" :options="$statuses"
                                placeholder="Todos los estados" />
                        </x-form-field>

                        <x-form-field label="Creador">
                            <x-custom-select x-model="filterUser" :options="$users->pluck('name', 'id')->toArray()"
                                placeholder="Todos los creadores" />
                        </x-form-field>

                        <x-form-field label="Período">
                            <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Rango personalizado']" placeholder="Todos los períodos" />
                        </x-form-field>

                        <div x-show="filterPeriod === 'custom'" x-collapse class="col-span-full mt-2">
                            <div class="grid grid-cols-2 gap-4">
                                <x-form-field label="Desde">
                                    <x-date-picker x-model="filterDateFrom" :options="['maxDate' => 'today']"
                                        placeholder="Fecha inicio" />
                                </x-form-field>
                                <x-form-field label="Hasta">
                                    <x-date-picker x-model="filterDateTo" :options="['maxDate' => 'today']"
                                        placeholder="Fecha fin" />
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
                            <x-button wire:click="clearAllFilters" variant="link-danger-muted" icon="eraser" class="!text-xs !min-h-0 ml-auto">
                                Limpiar todo
                            </x-button>
                        @endif
                    </div>
                @endif
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div wire:loading.class="hidden" wire:target="search, periodFilter, previousPage, nextPage, gotoPage"
                class="w-full">
                <x-card.table class="hidden md:block w-full">
                    @if($budgets->isEmpty() && !$hasActiveFilters)
                        <div class="p-8">
                            <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                                message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                        </div>
                    @endif
                    <table
                        class="w-full table-fixed min-w-[1100px] {{ $budgets->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}">
                        <colgroup>
                            <col class="w-14"> {{-- Checkbox --}}
                            <col class="w-[35%]"> {{-- Título --}}
                            <col class="w-[20%]"> {{-- Cliente --}}
                            <col class="w-[10%]"> {{-- Fecha --}}
                            <col class="w-[8%]"> {{-- Ítems --}}
                            <col class="w-[12%]"> {{-- Estado --}}
                            <col class="w-[12%]"> {{-- Monto Total --}}
                            <col class="w-28"> {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($budgets->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="title" label="Título" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="text-center">Conceptos</th>
                                <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="grand_total" label="Monto Total" :sortField="$sortField"
                                    :sortDirection="$sortDirection" align="right" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($budgets->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="7" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron cotizaciones"
                                            message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($budgets as $budget)
                                    <tr wire:key="budget-row-{{ $budget->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $budget->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-4 pr-2 text-center" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="font-semibold text-text-primary truncate" title="{{ $budget->title }}">
                                                {{ $budget->title }}</p>
                                            @if($budget->description)
                                                <p class="text-xs text-text-muted truncate" title="{{ $budget->description }}">
                                                    {{ $budget->description }}</p>
                                            @endif
                                        </td>
                                        <td class="max-w-0">
                                            <span class="text-body text-text-secondary truncate block"
                                                title="{{ $budget->client?->name ?? '—' }}">{{ $budget->client?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-body text-text-secondary">{{ $budget->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center text-body">{{ $budget->items_count }}</td>
                                        <td>
                                            @if($budget->status)
                                                <x-badge variant="{{ $budget->status->color() }}">{{ $budget->status->label() }}</x-badge>
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-semibold tabular-nums text-text-primary numeric">
                                            ${{ number_format($budget->grand_total, 2, '.', ',') }}</td>
                                        <td class="actions pr-4 py-3" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        <x-dropdown-link
                                                            href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}"
                                                            icon="edit-2" wire:navigate>
                                                            Editar
                                                        </x-dropdown-link>
                                                        @if(auth()->user()->hasPermission('cotizaciones.eliminar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" type="button"
                                                                @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta cotización? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteBudget', params: [{{ $budget->id }}] })"
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
                    </table>
                </x-card.table>

                <div class="md:hidden p-4 flex flex-col gap-4">
                    {{-- Tarjetas Móviles (Mobile View) --}}
                    @if($budgets->isNotEmpty())
                        <div class="flex flex-col gap-4">
                            @foreach($budgets as $budget)
                                <div class="card p-4 flex flex-col gap-3 relative transition-colors"
                                    :class="selectedRows.includes('{{ $budget->id }}') ? 'bg-primary-50/50' : ''"
                                    wire:key="quick-budget-mobile-card-{{ $budget->id }}">

                                    <div class="flex justify-between items-start gap-2">
                                        <div class="flex items-start gap-3">
                                            <div class="pt-0.5">
                                                <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="font-bold text-text-primary text-body">{{ $budget->title }}</span>
                                                </div>
                                                <p class="text-xs text-text-secondary mt-1 truncate">Cliente:
                                                    {{ $budget->client?->name ?? '—' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-bold text-text-primary text-h6">
                                                ${{ number_format($budget->grand_total, 2, '.', ',') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                                        <div>
                                            <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">
                                                Conceptos</p>
                                            <span class="inline-flex items-center gap-1.5 text-text-primary">
                                                <x-lucide-list class="w-3.5 h-3.5 text-text-muted" />
                                                {{ $budget->items_count }}
                                            </span>
                                        </div>
                                        @if($budget->description)
                                            <div class="col-span-2 flex items-start gap-1.5 mt-1 pt-2 border-t border-border/50">
                                                <x-lucide-align-left class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0" />
                                                <span class="text-text-secondary line-clamp-2">{{ $budget->description }}</span>
                                            </div>
                                        @endif
                                        <div
                                            class="col-span-2 flex items-center justify-between mt-1 pt-2 border-t border-border/50">
                                            <div class="flex items-center gap-1.5 text-text-secondary">
                                                <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted" />
                                                <span>Registro: {{ $budget->created_at->format('d/m/Y') }}</span>
                                            </div>
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
                                                <x-dropdown-link href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}"
                                                    icon="edit-2" wire:navigate>
                                                    Editar
                                                </x-dropdown-link>
                                                @if(auth()->user()->hasPermission('cotizaciones.eliminar') || auth()->user()->hasPermission('*'))
                                                    <x-dropdown-link as="button" type="button"
                                                        @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar esta cotización? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteBudget', params: [{{ $budget->id }}] })"
                                                        danger="true" icon="trash-2">
                                                        Eliminar
                                                    </x-dropdown-link>
                                                @endif
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron cotizaciones"
                                message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                                message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                        </div>
                    @endif
                </div>
            </div>

            {{-- Skeleton Loader --}}
            <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
                class="hidden absolute inset-0 w-full z-10 bg-surface-main">
                <x-card.table class="hidden md:block w-full">
                    <table class="w-full table-fixed min-w-[1100px]">
                        <colgroup>
                            <col class="w-14"> {{-- Checkbox --}}
                            <col class="w-[35%]"> {{-- Título --}}
                            <col class="w-[20%]"> {{-- Cliente --}}
                            <col class="w-[10%]"> {{-- Fecha --}}
                            <col class="w-[8%]"> {{-- Conceptos --}}
                            <col class="w-[12%]"> {{-- Monto Total --}}
                            <col class="w-28"> {{-- Acciones --}}
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="actions pl-4 pr-2"></th>
                                <th>Título</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th class="text-center">Conceptos</th>
                                <th class="text-right">Monto Total</th>
                                <th class="actions text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 5; $i++)
                                <tr>
                                    <td class="actions pl-4 pr-2 text-center">
                                        <x-skeleton class="w-4 h-4 rounded mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4  rounded w-40 mb-1" />
                                        <x-skeleton class="h-3  rounded w-28" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4  rounded w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4  rounded w-20" />
                                    </td>
                                    <td class="text-center">
                                        <x-skeleton class="h-4  rounded w-8 mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4  rounded w-16 ml-auto" />
                                    </td>
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-skeleton class="w-8 h-8  rounded" />
                                            <x-skeleton class="w-8 h-8  rounded" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </x-card.table>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden"
                    wire:target="search, periodFilter, previousPage, nextPage, gotoPage"
                    class="hidden flex flex-col gap-4 p-4">
                    @for($i = 0; $i < 4; $i++)
                        <div
                            class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-start gap-3">
                                    <div class="pt-0.5">
                                        <x-skeleton class="w-4 h-4 rounded-sm" />
                                    </div>
                                    <div class="min-w-0">
                                        <x-skeleton class="h-5 w-32 rounded mb-1.5" />
                                        <x-skeleton class="h-3 w-24 rounded" />
                                    </div>
                                </div>
                                <x-skeleton class="h-6 w-20 rounded" />
                            </div>
                            <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                                <div>
                                    <x-skeleton class="h-3 w-12 rounded mb-1.5" />
                                    <x-skeleton class="h-4 w-8 rounded" />
                                </div>
                                <div class="col-span-2 mt-1 pt-2 border-t border-border/50">
                                    <x-skeleton class="h-4 w-32 rounded" />
                                </div>
                            </div>
                            <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                                <x-skeleton class="h-9 w-24 rounded-md" />
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(auth()->user()->hasPermission('cotizaciones.eliminar') || auth()->user()->hasPermission('*'))
                <x-bulk-actions-bar>
                    <x-button @click="$dispatch('confirm-action', {
                        title: 'Eliminar Cotizaciones',
                        description: 'Se eliminarán permanentemente las cotizaciones seleccionadas.',
                        confirmLabel: 'Eliminar',
                        variant: 'danger',
                        action: 'bulkDelete',
                        params: []
                    })" variant="danger" icon="trash-2">
                        Eliminar
                    </x-button>
                </x-bulk-actions-bar>
            @endif
        </div>

        {{-- Pagination Footer --}}
        @if($budgets->total() > 0)
            <x-card.footer>
                {{ $budgets->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </x-card>

    {{-- Delete / Action Modals --}}
    <x-confirm-modal />
</div>