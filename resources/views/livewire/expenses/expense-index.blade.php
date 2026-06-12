<div x-data="expenseIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $expenses->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Control financiero" title="Gastos">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Registrar Gasto
            </x-button>
        </x-slot:actions>
    </x-page-header>



    {{-- Unified Datagrid Card Container --}}
    <div class="md:card md:border md:border-border md:bg-surface-card md:shadow-sm md:rounded-xl w-full overflow-hidden mt-4">
        
        {{-- Filters Bar (Card Header on Desktop) --}}
        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between w-full p-4 md:px-6 md:py-4 md:border-b md:border-border md:bg-surface-card">
            {{-- Search --}}
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar gasto..." />

            {{-- Filters Popover --}}
            @php
                $activeCount = ($projectFilter ? 1 : 0) + ($categoryFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($userFilter ? 1 : 0);
            @endphp
            <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                <x-form-field label="Proyecto">
                    <x-custom-select x-model="filterProject" :options="$projects->pluck('name', 'id')->toArray()" placeholder="Todos los proyectos" />
                </x-form-field>

                <x-form-field label="Categoría">
                    <x-custom-select x-model="filterCategory" :options="$categories" placeholder="Todas las categorías" />
                </x-form-field>

                <x-form-field label="Creador">
                    <x-custom-select x-model="filterUser" :options="$users->pluck('name', 'id')->toArray()" placeholder="Todos los usuarios" />
                </x-form-field>

                <x-form-field label="Período">
                    <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']" placeholder="Todos los períodos" />
                </x-form-field>

                <x-slot name="footer">
                    <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                        Limpiar todo
                    </button>
                    <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                        Aplicar Filtros
                    </x-button>
                </x-slot>
            </x-filters-popover>
        </div>

        {{-- Active Chips Row --}}
        @if($activeCount > 0)
        <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:pb-0 md:px-6 md:py-3 md:border-b md:border-border/50 md:bg-surface-hover/30">
            @if($projectFilter)
                <x-filter-chip label="Proyecto" :value="$projects->firstWhere('id', $projectFilter)?->name ?? 'Desconocido'" wire:click="$set('projectFilter', '')" />
            @endif
            @if($categoryFilter)
                <x-filter-chip label="Categoría" :value="$categories[$categoryFilter] ?? $categoryFilter" wire:click="$set('categoryFilter', '')" />
            @endif
            @if($userFilter)
                <x-filter-chip label="Creador" :value="$users->firstWhere('id', $userFilter)?->name ?? 'Desconocido'" wire:click="$set('userFilter', '')" />
            @endif
            @if($periodFilter)
                @php
                    $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año'];
                @endphp
                <x-filter-chip label="Período" :value="$periodNames[$periodFilter] ?? $periodFilter" wire:click="$set('periodFilter', '')" />
            @endif
        </div>
        @endif

        {{-- Expenses Table --}}
        <div class="relative min-h-[200px]">
            <div class="w-full">
                {{-- Desktop View --}}
                <div class="table-container table-integrated hidden md:block">
                    <table>
                        <thead class="bg-surface-th border-b border-border">
                            <tr>
                                <th class="actions text-center pl-6 pr-2 w-10">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $expenses->pluck('id')->join(',') }}])" />
                                </th>
                                <x-sortable-header field="concept" label="Concepto" :sortField="$sortField" :sortDirection="$sortDirection" class="w-1/3 min-w-[200px]" />
                                <x-sortable-header field="project_id" label="Proyecto" :sortField="$sortField" :sortDirection="$sortDirection" class="w-48" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField" :sortDirection="$sortDirection" class="w-32" />
                                <x-sortable-header field="date" label="Fecha" :sortField="$sortField" :sortDirection="$sortDirection" class="w-24" />
                                <x-sortable-header field="amount" label="Monto" :sortField="$sortField" :sortDirection="$sortDirection" align="right" class="w-32 numeric" />
                                <th class="actions pr-6">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage">
                            @if($expenses->isNotEmpty())
                                @foreach($expenses as $expense)
                                    <tr wire:key="expense-row-{{ $expense->id }}"
                                        class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                        :class="selectedRows.includes('{{ $expense->id }}') ? 'bg-primary-50/50' : ''">
                                        <td class="actions text-center pl-6 pr-2" @click.stop>
                                            <x-table-checkbox x-model="selectedRows" value="{{ $expense->id }}" />
                                        </td>
                                        <td>
                                            <p class="font-semibold text-text-primary">{{ $expense->concept }}</p>
                                            <p class="text-xs text-text-muted">Por: {{ $expense->user->name ?? '—' }}</p>
                                        </td>
                                        <td>
                                            @if($expense->is_distributed)
                                                <x-badge variant="primary" icon="split">Distribuido</x-badge>
                                            @else
                                                <span class="text-body text-text-secondary">{{ $expense->project->name ?? '—' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                                        </td>
                                        <td class="text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                                        <td class="numeric font-semibold text-text-primary">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                                        <td class="actions pr-6" @click.stop>
                                            <div class="flex items-center justify-end">
                                                <x-dropdown align="right" width="48">
                                                    <x-slot name="trigger">
                                                        <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                    </x-slot>

                                                    <x-slot name="content">
                                                        @if($expense->receipt_file)
                                                            <x-dropdown-link href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" icon="file-text">
                                                                Ver comprobante
                                                            </x-dropdown-link>
                                                        @endif
                                                        <x-dropdown-link as="button" wire:click="deleteExpense({{ $expense->id }})"
                                                            wire:confirm="¿Eliminar este gasto? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                            Eliminar
                                                        </x-dropdown-link>
                                                    </x-slot>
                                                </x-dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7">
                                        <x-empty-state icon="receipt" title="No se encontraron gastos"
                                            message="No hay registros que coincidan con tu búsqueda." />
                                    </td>
                                </tr>
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
                </div>

                {{-- Tarjetas Móviles (Mobile View) --}}
                <div class="md:hidden flex flex-col gap-4 p-4">
                    <div wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                        @if($expenses->isNotEmpty())
                            @foreach($expenses as $expense)
                                <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                                     :class="selectedRows.includes('{{ $expense->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                     wire:key="expense-mobile-card-{{ $expense->id }}">
                                    
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="flex items-start gap-3">
                                            <div class="pt-0.5">
                                                <x-table-checkbox x-model="selectedRows" value="{{ $expense->id }}" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-bold text-text-primary text-body">{{ $expense->concept }}</span>
                                                </div>
                                                <p class="text-xs text-text-secondary mt-1">Por: {{ $expense->user->name ?? '—' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-bold text-text-primary text-h6">
                                                ${{ number_format($expense->amount, 2, '.', ',') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                                        <div>
                                            <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Proyecto</p>
                                            @if($expense->is_distributed)
                                                <x-badge variant="primary" icon="split">Distribuido</x-badge>
                                            @else
                                                <p class="font-medium text-text-primary truncate" title="{{ $expense->project->name ?? '—' }}">{{ $expense->project->name ?? '—' }}</p>
                                            @endif
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1 text-right">Categoría</p>
                                            <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                                        </div>
                                        <div class="col-span-2 flex items-center justify-between mt-1 pt-2 border-t border-border/50">
                                            <span class="text-text-secondary">{{ $expense->date->format('d/m/Y') }}</span>
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
                                                @if($expense->receipt_file)
                                                    <x-dropdown-link href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" icon="file-text">
                                                        Ver comprobante
                                                    </x-dropdown-link>
                                                @endif
                                                <x-dropdown-link as="button" wire:click="deleteExpense({{ $expense->id }})"
                                                    wire:confirm="¿Eliminar este gasto? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                    Eliminar
                                                </x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <x-empty-state icon="receipt" title="No se encontraron gastos" message="No hay registros que coincidan con tu búsqueda." />
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, userFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex items-start gap-3">
                                        <div class="pt-0.5"><x-skeleton class="w-4 h-4 rounded-sm" /></div>
                                        <div>
                                            <x-skeleton class="h-5 w-32 rounded" />
                                            <x-skeleton class="h-3 w-24 rounded mt-1.5" />
                                        </div>
                                    </div>
                                    <x-skeleton class="h-6 w-20 rounded" />
                                </div>
                                <div class="bg-surface-hover/50 p-3 rounded-xl border border-border/50 flex flex-col gap-2">
                                    <div class="flex justify-between">
                                        <x-skeleton class="h-3 w-16 rounded" />
                                        <x-skeleton class="h-3 w-16 rounded" />
                                    </div>
                                    <div class="flex justify-between">
                                        <x-skeleton class="h-4 w-32 rounded mt-1" />
                                        <x-skeleton class="h-5 w-24 rounded-full mt-1" />
                                    </div>
                                    <div class="pt-2 border-t border-border/50 mt-1">
                                        <x-skeleton class="h-3 w-20 rounded" />
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

        </div>

        {{-- Pagination Footer (Card Footer on Desktop) --}}
        @if($expenses->hasPages())
        <div class="p-4 md:px-6 md:py-4 border-t border-border bg-surface-card">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
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
                                    <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
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
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="createExpense">
                        Registrar Gasto
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>