<div>
    <x-page-header subtitle="Cotizador" :title="$budgetId ? 'Editar Cotización' : 'Nueva Cotización'">
        <x-slot:actions>
            <a href="{{ route('cotizador.index') }}" wire:navigate class="btn-secondary">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        {{-- Header Details --}}
        <div class="card p-6">
            <h2 class="text-h2 text-text-primary mb-4">Información General</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form-field label="Título de Cotización" required error="{{ $errors->first('title') }}">
                    <input wire:model="title" type="text" class="input w-full"
                        placeholder="Ej. Materiales para Obra Centro">
                </x-form-field>
                <x-form-field label="Cliente (Opcional)" error="{{ $errors->first('client') }}">
                    <input wire:model="client" type="text" class="input w-full" placeholder="Nombre del cliente">
                </x-form-field>
                <x-form-field label="Margen de Ganancia" error="{{ $errors->first('marginPercent') }}">
                    <div class="relative">
                        <input type="number" wire:model.live.debounce.500ms="marginPercent" step="1" min="0" max="100"
                            class="input w-full text-right pr-8">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">%</span>
                    </div>
                </x-form-field>
                <div class="md:col-span-3">
                    <x-form-field label="Descripción / Notas" error="{{ $errors->first('description') }}">
                        <textarea wire:model="description" class="input w-full" rows="2"
                            placeholder="Notas adicionales que aparecerán en el PDF..."></textarea>
                    </x-form-field>
                </div>
            </div>
        </div>

        {{-- Items List --}}
        <div class="card mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-h2 text-text-primary">Conceptos y Materiales</h2>
                    @if(count($items) > 0)
                        <span class="badge badge-secondary">{{ count($items) }}
                            {{ count($items) === 1 ? 'concepto' : 'conceptos' }}</span>
                    @endif
                </div>
                <button type="button" wire:click="addManualItem" class="btn-secondary">
                    <i data-lucide="plus" class="w-4 h-4"></i> Concepto Manual
                </button>
            </div>

            {{-- Search Product --}}
            <div class="mb-4" x-data>
                <div class="relative">
                    <div class="relative">
                        <i data-lucide="search"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
                        <input wire:model.live.debounce.300ms="searchQuery" type="text"
                            class="input pl-10 border-border focus:border-primary-500 bg-surface-card"
                            placeholder="Buscar producto para agregar (carga precio histórico)...">
                        <div wire:loading wire:target="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <span class="spinner spinner-sm"></span>
                        </div>
                    </div>

                    {{-- Dropdown Results --}}
                    @if(!empty($searchResults))
                        <div
                            class="absolute z-10 mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in">
                            <ul class="max-h-60 overflow-y-auto py-1">
                                @foreach($searchResults as $index => $product)
                                    <li>
                                        <button type="button" wire:click="addProduct({{ $index }})"
                                            class="w-full text-left px-4 py-2.5 hover:bg-surface-hover flex items-center justify-between group transition-colors">
                                            <div>
                                                <p
                                                    class="text-small font-medium text-text-primary group-hover:text-primary-600">
                                                    {{ $product['name'] }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span
                                                        class="text-xs-fluid text-text-muted">{{ $product['category'] }}</span>
                                                    <span class="badge badge-secondary">{{ $product['measure_abbr'] }}</span>
                                                </div>
                                            </div>
                                            @if($product['last_price'] > 0)
                                                <div class="text-right">
                                                    <p class="text-xs-fluid text-text-muted">Último costo</p>
                                                    <p class="text-small font-semibold text-text-primary">
                                                        ${{ number_format($product['last_price'], 2) }}</p>
                                                </div>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items Table --}}
            @if(count($items) > 0)
                <div class="table-embedded">
                    <table>
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="w-24 text-center">Cant.</th>
                                <th class="w-32 text-right">P. Unitario</th>
                                <th class="w-32 text-right">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr class="group transition-colors duration-150 hover:bg-surface-hover/30"
                                    wire:key="item-row-{{ $index }}">
                                    <td class="px-4 py-3">
                                        @if($item['product_id'])
                                            <div class="flex flex-col">
                                                <span class="text-body font-medium text-text-primary">{{ $item['concept'] }}</span>
                                                <span class="text-xs-fluid text-text-muted">Prod. Catálogo
                                                    ({{ $item['measure_abbr'] }})</span>
                                            </div>
                                        @else
                                            <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.concept"
                                                class="input h-8 text-small px-2 bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-white"
                                                placeholder="Escribe un concepto...">
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                                step="0.01"
                                                class="input h-8 text-small px-2 pr-8 text-right bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-white">
                                            <span
                                                class="absolute right-2 top-1/2 -translate-y-1/2 text-xs-fluid text-text-muted pointer-events-none">{{ $item['measure_abbr'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span
                                                class="absolute left-2 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                                step="0.01"
                                                class="input h-8 text-small pl-6 pr-2 text-right bg-transparent border-transparent hover:border-border focus:border-primary-500 focus:bg-white">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-text-primary tabular-nums text-small">
                                        ${{ number_format($item['line_total'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                            class="btn-icon-danger mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state icon="package-search" title="No hay conceptos en la cotización"
                    message="Busca un producto arriba o agrega un concepto manual."
                    class="border border-dashed border-border rounded-xl py-10" />
            @endif

            {{-- Totals --}}
            @if(!empty($items))
                <div class="flex justify-end mt-4">
                    <x-totals-summary>
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">Costo Directo (Subtotal)</span>
                            <span
                                class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        @if($marginPercent > 0)
                            <div class="flex items-center justify-between gap-6">
                                <span class="text-small text-text-muted">Margen ({{ $marginPercent }}%)</span>
                                <span
                                    class="text-small font-medium text-text-secondary tabular-nums">+${{ number_format($this->subtotal * ($marginPercent / 100), 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                            <span class="text-body font-semibold text-text-primary">Total Presupuestado</span>
                            <span
                                class="text-h3 font-bold text-text-primary tabular-nums">${{ number_format($this->grand_total, 2) }}</span>
                        </div>
                    </x-totals-summary>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end pt-6">
            <x-submit-button target="save">Guardar Presupuesto</x-submit-button>
        </div>
    </div>
</div>