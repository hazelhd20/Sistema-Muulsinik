<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Control financiero" title="Gastos">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Registrar Gasto
            </x-button>
        </x-slot:actions>
    </x-page-header>



    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar gasto..." />

        {{-- Filters Popover --}}
        @php
            $activeCount = ($projectFilter ? 1 : 0) + ($categoryFilter ? 1 : 0) + ($periodFilter ? 1 : 0);
        @endphp
        <x-filters-popover :activeCount="$activeCount" :columns="1">
            <x-form-field label="Proyecto">
                <x-custom-select wire:model.live="projectFilter" :options="$projects->pluck('name', 'id')->toArray()" placeholder="Todos los proyectos" />
            </x-form-field>

            <x-form-field label="Categoría">
                <x-custom-select wire:model.live="categoryFilter" :options="$categories" placeholder="Todas las categorías" />
            </x-form-field>

            <x-form-field label="Período">
                <x-custom-select wire:model.live="periodFilter" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']" placeholder="Todos los períodos" />
            </x-form-field>

            <x-slot name="footer">
                <button type="button" wire:click="$set('projectFilter', ''); $set('categoryFilter', ''); $set('periodFilter', '');" @click="open = false" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                    Limpiar filtros
                </button>
            </x-slot>
        </x-filters-popover>


    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
                @if($expenses->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <x-sortable-header field="concept" label="Concepto" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="project_id" label="Proyecto" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="category" label="Categoría" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="date" label="Fecha" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <x-sortable-header field="amount" label="Monto" :sortField="$sortField" :sortDirection="$sortDirection" align="right" />
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                <tr>
                                    <td>
                                        <p class="font-medium text-text-primary">{{ $expense->concept }}</p>
                                        <p class="text-xs-fluid text-text-muted">Por: {{ $expense->user->name ?? '—' }}</p>
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
                                    <td class="text-body text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                                    <td class="numeric font-semibold text-text-primary">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            @if($expense->receipt_file)
                                                <x-button href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank"
                                                    variant="icon-primary" title="Ver comprobante" icon="file-text" />
                                            @endif
                                            <x-button wire:click="deleteExpense({{ $expense->id }})"
                                                wire:confirm="¿Eliminar este gasto? Esta acción no puede deshacerse."
                                                variant="icon-danger" icon="trash-2" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state
                        icon="{{ ($search || $projectFilter || $categoryFilter || $periodFilter) ? 'search-x' : 'receipt' }}"
                        title="{{ ($search || $projectFilter || $categoryFilter || $periodFilter) ? 'Sin resultados' : 'No hay gastos registrados' }}"
                        message="{{ ($search || $projectFilter || $categoryFilter || $periodFilter) ? 'No se encontraron gastos con los filtros aplicados.' : 'Registra un gasto para comenzar a llevar el control.' }}">
                        @if($search || $projectFilter || $categoryFilter || $periodFilter)
                            <x-slot:actions>
                                <x-button
                                    wire:click="$set('search', ''); $set('projectFilter', ''); $set('categoryFilter', ''); $set('periodFilter', '');"
                                    variant="secondary" icon="rotate-ccw">
                                    Limpiar filtros
                                </x-button>
                            </x-slot:actions>
                        @endif
                    </x-empty-state>
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($expenses->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($expenses as $expense)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors group">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="font-bold text-text-primary text-body truncate block">{{ $expense->concept }}</span>
                                <p class="text-xs-fluid text-text-secondary mt-1 truncate">Por: {{ $expense->user->name ?? '—' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-bold text-text-primary text-body block">${{ number_format($expense->amount, 2, '.', ',') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs-fluid text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                            <div class="flex items-center gap-1.5 col-span-2">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0"></i>
                                <span>{{ $expense->date->format('d/m/Y') }}</span>
                            </div>
                            
                            <div class="col-span-2 mt-1">
                                <span class="font-medium text-text-secondary mb-1 block">Proyecto:</span>
                                @if($expense->is_distributed)
                                    <x-badge variant="primary" icon="split">Distribuido</x-badge>
                                @else
                                    <span class="text-text-primary truncate block">{{ $expense->project->name ?? '—' }}</span>
                                @endif
                            </div>

                            <div class="col-span-2 mt-1">
                                <span class="font-medium text-text-secondary mb-1 block">Categoría:</span>
                                <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            @if($expense->receipt_file)
                                <x-button href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" variant="icon-primary" title="Ver comprobante" icon="file-text" class="text-xs-fluid w-8 h-8" />
                            @endif
                            <x-button wire:click="deleteExpense({{ $expense->id }})" wire:confirm="¿Eliminar este gasto? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2" class="text-xs-fluid w-8 h-8" />
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, projectFilter, categoryFilter, periodFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Proyecto</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th class="numeric">Monto</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <x-skeleton class="h-4  rounded w-48 mb-1.5" />
                                    <x-skeleton class="h-3  rounded w-32" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-36" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded-full w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20 ml-auto" />
                                </td>
                                <td class="actions">
                                    <div class="flex items-center justify-end gap-1">
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
                        <div class="bg-surface-hover/50 p-3 rounded-xl border border-border/50 flex flex-col gap-2">
                            <x-skeleton class="h-3 w-24 rounded" />
                            <x-skeleton class="h-4 w-32 rounded mt-1" />
                            <x-skeleton class="h-5 w-24 rounded-full mt-1" />
                        </div>
                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>

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
                        <input wire:model="date" type="date" class="input">
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
                                    <i data-lucide="split" class="w-4 h-4 mr-2"></i> Gasto distribuido
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