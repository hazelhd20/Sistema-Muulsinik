<div x-data="quickBudgetIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $budgets->count() }}; init()" data-total-on-page="{{ $budgets->count() }}">
    <x-page-header subtitle="Trabajos menores" title="Cotizador Rápido">
        <x-slot:actions>
            <x-button href="{{ route('cotizador.wizard') }}" variant="primary" icon="calculator" class="w-full sm:w-auto justify-center" wire:navigate>
                Nueva Cotización
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
        @php
            $activeCount = $periodFilter ? 1 : 0;
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($budgets->isNotEmpty() || $hasActiveFilters)
            <div class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar cotización..." />
                    </div>

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

                @if($activeCount > 0)
                    <div class="flex flex-wrap items-center gap-2 pb-3 md:px-6 md:pb-4 pt-1">
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
            </div>
        @endif

        <div class="relative">
            <div wire:loading.class="hidden" wire:target="search, periodFilter, previousPage, nextPage, gotoPage"
                class="w-full">
                <x-card.table class="hidden md:block w-full">
                    @if($budgets->isEmpty() && !$hasActiveFilters)
                        <div wire:loading.class="hidden"
                            wire:target="search, periodFilter, previousPage, nextPage, gotoPage" class="p-12">
                            <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                                message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                        </div>
                    @endif
                    <table
                        class="w-full table-fixed min-w-[1100px] {{ $budgets->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                        @if($budgets->isEmpty()) wire:loading.class.remove="hidden"
                        wire:target="search, periodFilter, previousPage, nextPage, gotoPage" @endif>
                        <colgroup>
                            <col class="w-14">
                            <col class="w-[35%]">
                            <col class="w-[20%]">
                            <col class="w-[10%]">
                            <col class="w-[8%]">
                            <col class="w-[12%]">
                            <col class="w-[12%]">
                            <col class="w-28">
                        </colgroup>
                        <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions pl-6 pr-2 text-left">
                                    <x-table-checkbox x-bind:checked="allSelected"
                                        @change="toggleAll({{ json_encode($budgets->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="title" label="Título" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="text-center text-xs-fluid font-semibold uppercase tracking-wider text-text-muted">Conceptos</th>
                                <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="grand_total" label="Monto Total" :sortField="$sortField"
                                    :sortDirection="$sortDirection" align="right" />
                                <th class="actions pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($budgets->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="8" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron cotizaciones"
                                            message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($budgets as $budget)
                                    <tr wire:key="budget-row-{{ $budget->id }}"
                                        class="group hover:bg-surface-hover transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $budget->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions pl-6 pr-2 text-left" @click.stop="$event.stopPropagation()">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                        </td>
                                        <td class="max-w-0">
                                            <p class="text-body font-bold text-text-primary truncate" title="{{ $budget->title }}">
                                                {{ $budget->title }}</p>
                                            @if($budget->description)
                                                <p class="text-xs-fluid text-text-muted truncate" title="{{ $budget->description }}">
                                                    {{ $budget->description }}</p>
                                            @endif
                                        </td>
                                        <td class="max-w-0">
                                            <span class="text-body font-medium text-text-secondary truncate block"
                                                title="{{ $budget->client?->name ?? '—' }}">{{ $budget->client?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-body font-medium text-text-secondary">{{ $budget->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center text-body font-medium text-text-secondary">{{ $budget->items_count }}</td>
                                        <td>
                                            @if($budget->status)
                                                <x-badge variant="{{ $budget->status->color() }}">{{ $budget->status->label() }}</x-badge>
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-body font-bold text-right tabular-nums text-text-primary numeric">
                                            ${{ number_format($budget->grand_total, 2, '.', ',') }}</td>
                                        <td class="actions pr-6 py-3" @click.stop="$event.stopPropagation()">
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

                <div class="md:hidden flex flex-col gap-4 mt-2">
                    <div wire:loading.class="hidden"
                        wire:target="search, periodFilter, previousPage, nextPage, gotoPage"
                        class="flex flex-col gap-4">
                        @if($budgets->isNotEmpty())
                            @foreach($budgets as $budget)
                                <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden"
                                    x-bind:class="selectedRows.includes('{{ $budget->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                    wire:key="quick-budget-mobile-card-{{ $budget->id }}">

                                    <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                            <span class="font-bold text-text-primary text-h3 truncate">{{ $budget->title }}</span>
                                            @if($budget->status)
                                                <x-badge variant="{{ $budget->status->color() }}">{{ $budget->status->label() }}</x-badge>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" icon="edit-2" wire:navigate>
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

                                    <div class="p-4 flex flex-col gap-4">
                                        <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <x-lucide-user class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="truncate font-medium">{{ $budget->client?->name ?? 'Sin cliente' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="font-medium">{{ $budget->created_at->format('d/m/Y') }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <x-lucide-list class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="font-medium">{{ $budget->items_count }} concepto{{ $budget->items_count !== 1 ? 's' : '' }}</span>
                                            </span>
                                        </div>

                                        @if($budget->description)
                                            <div class="pt-3 border-t border-border/40">
                                                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Descripción</p>
                                                <p class="text-body font-medium text-text-secondary line-clamp-2">{{ $budget->description }}</p>
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between pt-3 mt-1 border-t border-border/40">
                                            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Monto Total</p>
                                            <p class="font-bold text-h2 text-text-primary tabular-nums">
                                                ${{ number_format($budget->grand_total, 2, '.', ',') }}
                                            </p>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="search" title="No se encontraron cotizaciones"
                                    message="Intenta ajustar tus filtros de búsqueda." />
                            </x-card>
                        @else
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                                    message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                            </x-card>
                        @endif
                    </div>

                    <div wire:loading.class.remove="hidden"
                        wire:target="search, periodFilter, previousPage, nextPage, gotoPage"
                        class="hidden flex flex-col gap-4 mt-2">
                        @for($i = 0; $i < 4; $i++)
                            <x-card class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                        <x-skeleton class="h-5 w-24 rounded" />
                                        <x-skeleton class="h-5 w-20 rounded-full" />
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-skeleton class="w-7 h-7 rounded-md" />
                                    </div>
                                </div>
                                <div class="pl-8 flex flex-col gap-3">
                                    <div class="flex gap-3">
                                        <x-skeleton class="h-3 w-28 rounded" />
                                        <x-skeleton class="h-3 w-20 rounded" />
                                    </div>
                                    <div class="pt-3 border-t border-border/40 flex items-center justify-between">
                                        <x-skeleton class="h-3 w-16 rounded" />
                                        <x-skeleton class="h-6 w-24 rounded" />
                                    </div>
                                </div>
                            </x-card>
                        @endfor
                    </div>
                </div>
            </div>

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

        @if($budgets->total() > 0)
            <x-card.footer>
                {{ $budgets->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    <x-confirm-modal />
</div>