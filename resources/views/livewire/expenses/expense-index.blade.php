<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Control financiero" title="Gastos">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Registrar Gasto
            </button>
        </x-slot:actions>
    </x-page-header>



    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar gasto..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button" class="btn-secondary shrink-0"
            :class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.projectFilter || $wire.categoryFilter || $wire.periodFilter }">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Filtros
            @php
                $activeCount = ($projectFilter ? 1 : 0) + ($categoryFilter ? 1 : 0) + ($periodFilter ? 1 : 0);
            @endphp
            @if($activeCount > 0)
                <span
                    class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">{{ $activeCount }}</span>
            @endif
        </button>

        <div class="flex-1"></div>

        {{-- Clear button: only when filters active --}}
        @if($search || $projectFilter || $categoryFilter || $periodFilter)
            <button
                wire:click="$set('search', ''); $set('projectFilter', ''); $set('categoryFilter', ''); $set('periodFilter', '');"
                type="button"
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
        <div class="bg-surface-hover border border-border rounded-xl p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-wrap">
                <div class="flex items-center gap-2 shrink-0">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="projectFilter" :options="$projects->pluck('name', 'id')->toArray()"
                    placeholder="Todos los proyectos" class="w-full sm:w-48" />
                <x-custom-select wire:model.live="categoryFilter" :options="$categories"
                    placeholder="Todas las categorías" class="w-full sm:w-44" />
                <x-custom-select wire:model.live="periodFilter" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
                    placeholder="Todos los períodos" class="w-full sm:w-44" />
            </div>
        </div>
    </div>


    {{-- Table --}}
    <div class="table-container">
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
                                <p class="font-medium">{{ $expense->concept }}</p>
                                <p class="text-xs-fluid text-text-muted">Por: {{ $expense->user->name ?? '—' }}</p>
                            </td>
                            <td>
                                @if($expense->is_distributed)
                                    <span class="badge badge-secondary" title="Prorrateado entre proyectos activos">
                                        <i data-lucide="split" class="w-3 h-3 mr-1 inline-block"></i> Distribuido
                                    </span>
                                @else
                                    <span class="text-body">{{ $expense->project->name ?? '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <x-dynamic-badge :value="$categories[$expense->category] ?? $expense->category" />
                            </td>
                            <td class="text-body text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                            <td class="numeric font-semibold">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                            <td class="actions">
                                <div class="flex items-center justify-end gap-1">
                                    @if($expense->receipt_file)
                                        <a href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank"
                                            class="btn-icon-primary" title="Ver comprobante">
                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                        </a>
                                    @endif
                                    <button wire:click="deleteExpense({{ $expense->id }})"
                                        wire:confirm="¿Deseas eliminar este gasto? Esta acción no se puede deshacer."
                                        class="btn-icon-danger">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <x-empty-state icon="receipt" title="No hay gastos registrados"
                message="Registra un gasto para comenzar a llevar el control." />
        @endif
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
                        <div class="flex items-center justify-between mb-1">
                            <label class="label mb-0">Proyecto *</label>
                            <div class="flex items-center gap-1.5">
                                <input type="checkbox" wire:model.live="isDistributed" id="isDistributed"
                                    class="rounded border-border text-primary-600 focus:ring-primary-500 w-3 h-3">
                                <label for="isDistributed"
                                    class="text-[10px] uppercase font-bold tracking-wider text-text-secondary cursor-pointer hover:text-text-primary transition-colors">Prorratear
                                    (Activos)</label>
                            </div>
                        </div>

                        <div x-data="{ distributed: @entangle('isDistributed') }" class="relative">
                            <div x-show="!distributed">
                                <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
                                    placeholder="Seleccionar..." />
                            </div>
                            <div x-show="distributed"
                                class="input flex items-center bg-surface-hover text-text-muted cursor-not-allowed"
                                style="display: none;">
                                <i data-lucide="split" class="w-4 h-4 mr-2"></i> Gasto distribuido
                            </div>
                        </div>
                        @error('projectId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <x-form-field label="Categoría" required error="{{ $errors->first('category') }}">
                        <x-custom-select wire:model="category" :options="$categories" placeholder="Seleccionar..." />
                    </x-form-field>
                </div>

                <x-form-field label="Comprobante (opcional)">
                    <x-file-input wire:key="receipt-file" inputId="receipt-file-upload" wire:model="receiptFile" accept=".jpg,.jpeg,.png,.pdf" maxSize="20 MB" />
                </x-form-field>

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                    <x-submit-button target="createExpense">
                        Registrar Gasto
                    </x-submit-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>