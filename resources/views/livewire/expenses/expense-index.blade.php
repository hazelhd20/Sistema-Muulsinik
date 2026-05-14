<div>
    {{-- Header --}}
    <x-page-header subtitle="Control financiero" title="Gastos">
        <x-slot:actions>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Registrar Gasto
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-50">
                <i data-lucide="trending-up" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <p class="text-xs-fluid text-text-muted mb-0.5">Gasto del mes</p>
                <p class="text-h2 text-text-primary">${{ number_format($totalMonth, 0, '.', ',') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-primary-50">
                <i data-lucide="list" class="w-5 h-5 text-primary-600"></i>
            </div>
            <div>
                <p class="text-xs-fluid text-text-muted mb-0.5">Total registros</p>
                <p class="text-h2 text-text-primary">{{ $expenses->total() }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-50">
                <i data-lucide="calendar" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <p class="text-xs-fluid text-text-muted mb-0.5">Período</p>
                <p class="text-body font-semibold text-text-primary">{{ now()->format('F Y') }}</p>
            </div>
        </div>
    </div>


    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar gasto..."
                class="input pl-10 pr-10"
                @focus="focused = true"
                @blur="focused = false">
            <button
                x-show="$wire.search"
                x-transition
                @click="$wire.search = ''"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
        <x-custom-select
            wire:model.live="projectFilter"
            :options="$projects->pluck('name', 'id')->toArray()"
            placeholder="Todos los proyectos"
            class="w-auto min-w-[180px]"
        />
        <x-custom-select
            wire:model.live="categoryFilter"
            :options="$categories"
            placeholder="Todas las categorías"
            class="w-auto min-w-[160px]"
        />
        <x-custom-select
            wire:model.live="periodFilter"
            :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
            placeholder="Todos los períodos"
            class="w-auto min-w-[170px]"
        />
    </div>


    {{-- Table --}}
    <div class="table-container">
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
                @forelse($expenses as $expense)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $expense->concept }}</p>
                            <p class="text-xs-fluid text-text-muted">Por: {{ $expense->user->name ?? '—' }}</p>
                        </td>
                        <td>
                            <span class="text-body">{{ $expense->project->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="badge badge-secondary">
                                {{ $categories[$expense->category] ?? $expense->category }}
                            </span>
                        </td>
                        <td class="text-body text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                        <td class="numeric font-semibold">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                        <td class="actions">
                            <div class="flex items-center justify-end gap-1">
                                @if($expense->receipt_file)
                                    <a href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" class="btn-icon-primary" title="Ver comprobante">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </a>
                                @endif
                                <button
                                    wire:click="deleteExpense({{ $expense->id }})"
                                    wire:confirm="¿Deseas eliminar este gasto? Esta acción no se puede deshacer."
                                    class="btn-icon-danger"
                                >
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state icon="receipt" title="No hay gastos registrados" message="Registra un gasto para comenzar a llevar el control." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <x-modal show="showCreateModal" title="Registrar Gasto">
                <form wire:submit="createExpense" class="p-5 space-y-4">
                    <div>
                        <label class="label">Concepto *</label>
                        <input wire:model="concept" type="text" class="input" placeholder="Ej. Compra de cemento">
                        @error('concept') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Monto *</label>
                            <input wire:model="amount" type="number" step="0.01" class="input" placeholder="0.00">
                            @error('amount') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Fecha *</label>
                            <input wire:model="date" type="date" class="input">
                            @error('date') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Proyecto *</label>
                            <x-custom-select 
                                wire:model="projectId" 
                                :options="$projects->pluck('name', 'id')->toArray()" 
                                placeholder="Seleccionar..." 
                            />
                            @error('projectId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Categoría *</label>
                            <x-custom-select 
                                wire:model="category" 
                                :options="$categories" 
                                placeholder="Seleccionar..." 
                            />
                            @error('category') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Comprobante (opcional)</label>
                        <input wire:model="receiptFile" type="file" accept=".jpg,.jpeg,.png,.pdf" class="input text-body">
                        <p class="mt-1 text-xs-fluid text-text-muted">JPG, PNG o PDF. Máximo 20 MB.</p>
                        @error('receiptFile') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="createExpense" class="inline-flex items-center gap-1.5">Registrar Gasto</span>
                            <span wire:loading wire:target="createExpense" class="inline-flex items-center gap-2">
                                <span class="spinner spinner-sm opacity-80"></span>
                                Registrando…
                            </span>
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif
</div>
