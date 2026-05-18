<div class="max-w-5xl mx-auto pb-10">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('cotizador.index') }}" wire:navigate class="btn-icon-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-h2 text-text-primary">{{ $budgetId ? 'Editar Cotización' : 'Nueva Cotización' }}</h1>
            <p class="text-body text-text-muted">Calcula costos rápidamente basándote en el historial de compras.</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <button wire:click="save" class="btn-primary" wire:loading.attr="disabled">
                <i data-lucide="save" class="w-4 h-4"></i>
                Guardar Presupuesto
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Form & Items --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Header Details --}}
            <div class="card mb-6 p-5">
                <h2 class="text-h2 text-text-primary mb-5 flex items-center gap-2">
                    <i data-lucide="info" class="w-5 h-5 text-text-muted"></i>
                    Información General
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">Título de Cotización *</label>
                        <input type="text" wire:model="title" class="input" placeholder="Ej. Colado de losa 50m2">
                        @error('title') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">Cliente (Opcional)</label>
                        <input type="text" wire:model="client" class="input" placeholder="Nombre del cliente">
                        @error('client') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="label">Descripción / Notas</label>
                        <textarea wire:model="description" class="input min-h-[80px]" placeholder="Detalles adicionales del trabajo..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Items List --}}
            <div class="card mb-6 p-0 overflow-hidden">
                <div class="p-5 border-b border-border bg-surface-main">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-h2 text-text-primary flex items-center gap-2">
                            <i data-lucide="list" class="w-5 h-5 text-text-muted"></i>
                            Conceptos y Materiales
                        </h2>
                        <button wire:click="addManualItem" class="btn-secondary text-xs-fluid py-1.5 px-3">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Concepto Manual
                        </button>
                    </div>

                    {{-- Search Product --}}
                    <div class="relative" x-data="{ open: @entangle('searchResults').length > 0 }">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500"></i>
                            <input wire:model.live.debounce.300ms="searchQuery" type="text" class="input pl-10 border-primary-300 focus:border-primary-500 bg-primary-50/30" placeholder="Buscar producto para agregar (carga precio histórico)...">
                            <div wire:loading wire:target="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-primary-600" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            </div>
                        </div>

                        {{-- Dropdown Results --}}
                        @if(!empty($searchResults))
                            <div class="absolute z-10 mt-1 w-full bg-surface-panel rounded-lg shadow-lg border border-border overflow-hidden">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    @foreach($searchResults as $index => $product)
                                        <li>
                                            <button wire:click="addProduct({{ $index }})" class="w-full text-left px-4 py-2 hover:bg-surface-hover flex items-center justify-between group transition-colors">
                                                <div>
                                                    <p class="text-body font-medium text-text-primary group-hover:text-primary-600">{{ $product['name'] }}</p>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs-fluid text-text-muted">{{ $product['category'] }}</span>
                                                        <span class="text-[10px] bg-surface-main px-1.5 py-0.5 rounded text-text-secondary">{{ $product['measure_abbr'] }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs-fluid text-text-muted mb-0.5">Último costo</p>
                                                    <p class="text-body font-semibold text-text-primary">${{ number_format($product['last_price'], 2) }}</p>
                                                </div>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="table-embedded border-t-0 border-x-0 rounded-none">
                    <table>
                        <thead>
                            <tr>
                                <th class="w-10"></th>
                                <th>Concepto</th>
                                <th class="w-24 text-center">Cant.</th>
                                <th class="w-32 text-right">P. Unitario</th>
                                <th class="w-32 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr class="group" wire:key="item-row-{{ $index }}">
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="removeItem({{ $index }})" class="text-text-muted hover:text-danger transition-colors p-1 rounded hover:bg-surface-main opacity-0 group-hover:opacity-100">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['product_id'])
                                            <div class="flex flex-col">
                                                <span class="text-body font-medium text-text-primary">{{ $item['concept'] }}</span>
                                                <span class="text-xs-fluid text-text-muted">Prod. Catálogo ({{ $item['measure_abbr'] }})</span>
                                            </div>
                                        @else
                                            <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.concept" class="input h-8 text-body px-2 bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-surface-panel" placeholder="Escribe un concepto...">
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.quantity" step="0.01" class="input h-8 text-body px-2 pr-8 text-right bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-surface-panel">
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs-fluid text-text-muted pointer-events-none">{{ $item['measure_abbr'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-body text-text-muted pointer-events-none">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price" step="0.01" class="input h-8 text-body pl-6 pr-2 text-right bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-surface-panel">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-text-primary tabular-nums">
                                        ${{ number_format($item['line_total'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-text-muted">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="package-search" class="w-8 h-8 mb-2 opacity-50"></i>
                                            <p class="text-body font-medium">No hay conceptos en la cotización</p>
                                            <p class="text-xs-fluid mt-1">Busca un producto arriba o agrega un concepto manual.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column: Summary --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="card p-5 sticky top-[72px]">
                <h2 class="text-h2 text-text-primary mb-5 flex items-center gap-2">
                    <i data-lucide="calculator" class="w-5 h-5 text-text-muted"></i>
                    Resumen Financiero
                </h2>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center text-body text-text-secondary">
                        <span>Costo Directo (Subtotal)</span>
                        <span class="tabular-nums font-medium text-text-primary">${{ number_format($this->subtotal, 2) }}</span>
                    </div>

                    <div class="pt-3 border-t border-border border-dashed">
                        <label class="flex items-center justify-between mb-1">
                            <span class="text-body text-text-secondary flex items-center gap-1.5">
                                Margen de Ganancia/Imprevistos
                                <i data-lucide="help-circle" class="w-3.5 h-3.5 text-text-muted" title="Porcentaje extra sobre el costo directo."></i>
                            </span>
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="number" wire:model.live.debounce.500ms="marginPercent" step="1" min="0" max="100" class="input h-9 text-right pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">%</span>
                            </div>
                            <div class="text-right flex-1 text-body text-text-muted">
                                +${{ number_format($this->subtotal * ($marginPercent / 100), 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-main rounded-lg p-4 flex flex-col items-center justify-center text-center">
                    <p class="text-xs-fluid text-text-muted mb-1 uppercase tracking-wider font-semibold">Total Presupuestado</p>
                    <p class="text-3xl font-bold text-text-primary tabular-nums tracking-tight">
                        ${{ number_format($this->grand_total, 2) }}
                    </p>
                </div>

                @if(!empty($items))
                    <div class="mt-4 pt-4 border-t border-border flex flex-col gap-2">
                        <div class="flex justify-between items-center text-xs-fluid text-text-muted">
                            <span>Total de conceptos:</span>
                            <span class="font-medium text-text-primary">{{ count($items) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs-fluid text-text-muted">
                            <span>Monto promedio por ítem:</span>
                            <span class="font-medium text-text-primary">${{ number_format($this->grand_total / count($items), 2) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
