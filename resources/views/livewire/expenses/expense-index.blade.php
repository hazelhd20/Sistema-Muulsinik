<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Control de Gastos</h1>
            <p class="text-sm text-text-muted">Registra y controla los gastos operativos por proyecto</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="card flex items-center gap-3" style="padding: 0.75rem 1.25rem;">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4 text-green-600"></i>
                </div>
                <div>
                    <p class="text-xs text-text-muted">Gasto del mes</p>
                    <p class="text-lg font-bold text-text-primary">${{ number_format($totalMonth, 0, '.', ',') }}</p>
                </div>
            </div>
            <button wire:click="openCreateModal" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Registrar Gasto
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('budget_alert'))
        <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium">
            {{ session('budget_alert') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar gasto..." class="input pl-10">
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
                    <th class="text-right">Monto</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $expense->concept }}</p>
                            <p class="text-xs text-text-muted">Por: {{ $expense->user->name ?? '—' }}</p>
                        </td>
                        <td>
                            <span class="text-sm">{{ $expense->project->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="badge badge-primary">
                                {{ $categories[$expense->category] ?? $expense->category }}
                            </span>
                        </td>
                        <td class="text-sm text-text-secondary">{{ $expense->date->format('d/m/Y') }}</td>
                        <td class="text-right font-semibold text-text-primary">${{ number_format($expense->amount, 2, '.', ',') }}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($expense->receipt_file)
                                    <a href="{{ asset('storage/' . $expense->receipt_file) }}" target="_blank" class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-primary-600 transition">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </a>
                                @endif
                                <button
                                    wire:click="deleteExpense({{ $expense->id }})"
                                    wire:confirm="¿Deseas eliminar este gasto? Esta acción no se puede deshacer."
                                    class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition"
                                >
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <i data-lucide="receipt" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                            <p class="text-text-muted">No hay gastos registrados</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">Registrar Gasto</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="createExpense" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Concepto *</label>
                        <input wire:model="concept" type="text" class="input" placeholder="Ej. Compra de cemento">
                        @error('concept') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Monto *</label>
                            <input wire:model="amount" type="number" step="0.01" class="input" placeholder="0.00">
                            @error('amount') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Fecha *</label>
                            <input wire:model="date" type="date" class="input">
                            @error('date') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Proyecto *</label>
                            <x-custom-select 
                                wire:model="projectId" 
                                :options="$projects->pluck('name', 'id')->toArray()" 
                                placeholder="Seleccionar..." 
                            />
                            @error('projectId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Categoría *</label>
                            <x-custom-select 
                                wire:model="category" 
                                :options="$categories" 
                                placeholder="Seleccionar..." 
                            />
                            @error('category') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Comprobante (opcional)</label>
                        <input wire:model="receiptFile" type="file" accept=".jpg,.jpeg,.png,.pdf" class="input text-sm">
                        <p class="mt-1 text-xs text-text-muted">JPG, PNG o PDF. Máximo 20 MB.</p>
                        @error('receiptFile') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="createExpense" class="transition-opacity">Registrar Gasto</span>
                            <span wire:loading wire:target="createExpense" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
