<div x-data="{ selectedRows: @entangle('selectedRows') }">
    <x-page-header subtitle="Trabajos menores" title="Cotizador Rápido">
        <x-slot:actions>
            <x-button href="{{ route('cotizador.wizard') }}" variant="primary" icon="calculator" wire:navigate>
                Nueva Cotización
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar cotización..." />

        {{-- Filters Popover --}}
        @php
            $activeCount = $periodFilter ? 1 : 0;
        @endphp
        <div x-data="{
            filterPeriod: '{{ $periodFilter }}',
            initFilters() {
                this.filterPeriod = '{{ $periodFilter }}';
            },
            applyFilters() {
                if ($wire.periodFilter !== this.filterPeriod) $wire.set('periodFilter', this.filterPeriod);
            },
            clearFilters() {
                this.filterPeriod = '';
                this.applyFilters();
                open = false;
            }
        }">
            <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                <x-form-field label="Período">
                    <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']" placeholder="Todos los períodos" />
                </x-form-field>

                <x-slot name="footer">
                    <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                        Limpiar filtros
                    </button>
                    <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                        Aplicar Filtros
                    </x-button>
                </x-slot>
            </x-filters-popover>
        </div>
    </div>

    {{-- Active Chips Row --}}
    @if($activeCount > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if($periodFilter)
            @php
                $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año'];
            @endphp
            <x-filter-chip label="Período" :value="$periodNames[$periodFilter] ?? $periodFilter" wire:click="$set('periodFilter', '')" />
        @endif
    </div>
    @endif

    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, periodFilter, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($budgets->isNotEmpty())
                    <table>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-on:change="$el.checked ? selectedRows = [...new Set([...(selectedRows || []), ...[{{ $budgets->pluck('id')->join(',') }}].map(String)])] : selectedRows = (selectedRows || []).filter(id => ![{{ $budgets->pluck('id')->join(',') }}].map(String).includes(id))"
                                        :checked="[{{ $budgets->pluck('id')->join(',') }}].length > 0 && [{{ $budgets->pluck('id')->join(',') }}].map(String).every(id => (selectedRows || []).includes(id))" />
                                </th>
                                <x-sortable-header field="title" label="Título" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <th class="text-center">Ítems</th>
                                <x-sortable-header field="grand_total" label="Monto Total" :sortField="$sortField"
                                    :sortDirection="$sortDirection" align="right" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budgets as $budget)
                                <tr wire:key="budget-row-{{ $budget->id }}"
                                    class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                    :class="(selectedRows || []).map(String).includes('{{ $budget->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                    </td>
                                    <td>
                                        <p class="font-medium text-text-primary">{{ $budget->title }}</p>
                                        @if($budget->description)
                                            <p class="text-xs-fluid text-text-muted truncate max-w-[200px]">{{ $budget->description }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-body text-text-secondary">{{ $budget->client ?? '—' }}</span>
                                    </td>
                                    <td class="text-body text-text-secondary">{{ $budget->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center text-body">{{ $budget->items_count }}</td>
                                    <td class="text-right font-semibold text-text-primary">
                                        ${{ number_format($budget->grand_total, 2, '.', ',') }}</td>
                                    <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" icon="edit-2" wire:navigate>
                                                        Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="deleteBudget({{ $budget->id }})"
                                                        wire:confirm="¿Eliminar esta cotización? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                        Eliminar
                                                    </x-dropdown-link>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                        message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($budgets->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($budgets as $budget)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                         :class="(selectedRows || []).map(String).includes('{{ $budget->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                         wire:key="quick-budget-mobile-card-{{ $budget->id }}">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <x-table-checkbox x-model="selectedRows" value="{{ $budget->id }}" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-text-primary text-body">{{ $budget->title }}</span>
                                    </div>
                                    <p class="text-xs-fluid text-text-secondary mt-1 truncate">Cliente: {{ $budget->client ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-text-primary text-h6">
                                    ${{ number_format($budget->grand_total, 2, '.', ',') }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                            <div>
                                <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Ítems</p>
                                <span class="inline-flex items-center gap-1.5 text-text-primary">
                                    <i data-lucide="list" class="w-3.5 h-3.5 text-text-muted"></i>
                                    {{ $budget->items_count }}
                                </span>
                            </div>
                            @if($budget->description)
                                <div class="col-span-2 flex items-start gap-1.5 mt-1 pt-2 border-t border-border/50">
                                    <i data-lucide="align-left" class="w-3.5 h-3.5 mt-0.5 text-text-muted shrink-0"></i>
                                    <span class="text-text-secondary line-clamp-2">{{ $budget->description }}</span>
                                </div>
                            @endif
                            <div class="col-span-2 flex items-center justify-between mt-1 pt-2 border-t border-border/50">
                                <div class="flex items-center gap-1.5 text-text-secondary">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-text-muted"></i>
                                    <span>Registro: {{ $budget->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <x-button variant="secondary" class="w-full justify-center">
                                        <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                                        <span class="ml-2">Opciones</span>
                                    </x-button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" icon="edit-2" wire:navigate>
                                        Editar
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="deleteBudget({{ $budget->id }})"
                                        wire:confirm="¿Eliminar esta cotización? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                        Eliminar
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-center">Ítems</th>
                            <th class="text-right">Monto Total</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
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
            </div>

            {{-- Skeletons Móviles --}}
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <x-skeleton class="h-5 w-32 rounded" />
                                <x-skeleton class="h-3 w-24 rounded mt-1.5" />
                            </div>
                            <x-skeleton class="h-5 w-20 rounded" />
                        </div>
                        <div class="flex justify-between items-center bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                            <x-skeleton class="h-4 w-24 rounded" />
                            <x-skeleton class="h-4 w-16 rounded" />
                        </div>
                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Cotizaciones',
                    description: 'Se eliminarán permanentemente las cotizaciones seleccionadas.',
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

    </div>
    
    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

    <div class="mt-4">{{ $budgets->links() }}</div>
</div>