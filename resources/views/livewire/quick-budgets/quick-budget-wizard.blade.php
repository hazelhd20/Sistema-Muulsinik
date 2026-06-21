<div>
    @php
        $breadcrumbs = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cotizador Rápido', 'url' => route('cotizador.index')],
            ['label' => $budgetId ? 'Editar Cotización' : 'Nueva Cotización']
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" :title="$budgetId ? 'Editar Cotización' : 'Nueva Cotización'">
    </x-page-header>

    <div class="space-y-6">
        {{-- Header Details --}}
        <x-card class="mb-6">
            <x-card.header title="Datos Generales" />
            <x-card.body>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="{{ $advancedMode ? 'md:col-span-5' : 'md:col-span-6' }}">
                        <x-form-field label="Título de Cotización" required error="{{ $errors->first('title') }}">
                            <input wire:model="title" type="text" class="input w-full"
                                placeholder="Ej. Materiales para Obra Centro">
                        </x-form-field>
                    </div>
                    <div class="{{ $advancedMode ? 'md:col-span-5' : 'md:col-span-6' }}">
                        <x-form-field label="Cliente (Opcional)" error="{{ $errors->first('client_id') }}">
                            <x-custom-select wire:model.live="client_id"
                                :options="$clients"
                                minSearch="5"
                                placeholder="Seleccionar cliente..." />
                        </x-form-field>
                    </div>
                    @if($advancedMode)
                    <div class="md:col-span-2">
                        <x-form-field label="Margen Global" error="{{ $errors->first('marginPercent') }}">
                            <div class="relative">
                                <input type="number" wire:model.live.debounce.500ms="marginPercent" step="1" min="0" max="100"
                                    class="input w-full pl-3 pr-8 font-medium">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">%</span>
                            </div>
                        </x-form-field>
                    </div>
                    @endif
                    <div class="md:col-span-12">
                        <x-form-field label="Descripción / Notas" error="{{ $errors->first('description') }}">
                            <textarea wire:model="description" class="input w-full" rows="2"
                                placeholder="Notas adicionales que aparecerán en el PDF..."></textarea>
                        </x-form-field>
                    </div>
                </div>
            </x-card.body>
        </x-card>

        {{-- Items List --}}
        <x-card class="mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-border/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h3 class="font-medium text-text-primary tracking-tight">Conceptos y Materiales</h3>
                    @if(count($items) > 0)
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-surface-main border border-border/60 text-xs font-medium text-text-muted">
                            <x-lucide-list class="w-3.5 h-3.5" />
                            {{ count($items) }} {{ count($items) === 1 ? 'concepto' : 'conceptos' }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-5">
                    <div class="flex items-center gap-2">
                        <x-toggle wire:model.live="advancedMode" label="Modo Avanzado" />
                    </div>
                    <div class="flex-shrink-0">
                        <x-button wire:click="addManualItem" variant="soft" icon="plus" class="text-xs w-full sm:w-auto justify-center">
                            Concepto Manual
                        </x-button>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5">
                {{-- Search Product --}}
                <div x-data="{ open: true }" class="relative max-w-lg" @click.outside="open = false">
                    <div class="relative">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                        <input wire:model.live.debounce.300ms="searchQuery" 
                            wire:keydown.enter.prevent="addFirstProduct"
                            @focus="open = true" @input="open = true" type="text"
                            class="input w-full pl-10 pr-10 border-border focus:border-primary-500 bg-surface-card"
                            placeholder="Buscar producto para agregar (carga precio histórico)...">
                        
                        @if(strlen($searchQuery) > 0)
                            <button type="button" wire:click="$set('searchQuery', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors p-1 rounded-full hover:bg-surface-hover">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        @endif
                    </div>

                    {{-- Dropdown Results & Loading Skeleton --}}
                    @if(strlen($searchQuery) >= 2)
                        <div x-show="open" x-cloak class="absolute z-[45] mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in">
                            
                            {{-- Skeleton Loading --}}
                            <div wire:loading wire:target="searchQuery" class="w-full">
                                <div class="px-4 py-3 border-b border-border/40">
                                    <div class="flex items-center justify-between">
                                        <div class="w-2/3">
                                            <x-skeleton class="h-4 w-3/4 rounded mb-2" />
                                            <div class="flex gap-2">
                                                <x-skeleton class="h-3 w-16 rounded" />
                                                <x-skeleton class="h-3 w-8 rounded" />
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end w-1/3">
                                            <x-skeleton class="h-3 w-12 rounded mb-2" />
                                            <x-skeleton class="h-4 w-16 rounded" />
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="w-2/3">
                                            <x-skeleton class="h-4 w-1/2 rounded mb-2" />
                                            <div class="flex gap-2">
                                                <x-skeleton class="h-3 w-20 rounded" />
                                                <x-skeleton class="h-3 w-8 rounded" />
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end w-1/3">
                                            <x-skeleton class="h-3 w-12 rounded mb-2" />
                                            <x-skeleton class="h-4 w-16 rounded" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Results --}}
                            <div wire:loading.remove wire:target="searchQuery">
                                @if(!empty($searchResults))
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        @foreach($searchResults as $index => $product)
                                            <li>
                                                <button type="button" wire:click="addProduct({{ $index }})"
                                                    class="w-full text-left px-4 py-2.5 hover:bg-surface-hover flex items-center justify-between group transition-colors">
                                                    <div>
                                                        <p class="text-small font-medium text-text-primary group-hover:text-primary-600">
                                                            {{ $product['name'] }}</p>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-xs text-text-muted">{{ $product['category'] }}</span>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface-hover text-text-secondary border border-border text-[9px] font-bold uppercase tracking-wider">
                                                                {{ $product['measure_abbr'] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    @if($product['last_price'] > 0)
                                                        <div class="text-right flex flex-col items-end">
                                                            <div class="flex items-center gap-1 group/history relative">
                                                                <span class="text-[10px] uppercase tracking-wider text-text-muted font-medium">Costo ref.</span>
                                                                @if(count($product['history'] ?? []) > 0)
                                                                    <div x-data="{ showHistory: false }" @mouseenter="showHistory = true" @mouseleave="showHistory = false">
                                                                        <x-lucide-history class="w-3.5 h-3.5 text-primary-500 hover:text-primary-600 transition-colors" />
                                                                        <div x-show="showHistory" x-cloak class="absolute right-0 top-full mt-1 bg-surface-main border border-border shadow-md rounded-lg p-3 z-[60] min-w-[140px] pointer-events-none">
                                                                            <p class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-2 border-b border-border/50 pb-1.5">Últimas Compras</p>
                                                                            @foreach($product['history'] as $h)
                                                                                <div class="flex justify-between items-center gap-4 text-xs mb-1.5 last:mb-0">
                                                                                    <span class="text-text-muted font-medium">{{ $h['date'] }}</span>
                                                                                    <span class="font-bold text-text-primary tabular-nums">${{ number_format($h['price'], 2) }}</span>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <p class="text-small font-semibold text-text-primary">
                                                                ${{ number_format($product['last_price'], 2) }}</p>
                                                        </div>
                                                    @endif
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="p-4 text-center">
                                        <p class="text-small text-text-muted">No se encontraron productos en el catálogo.</p>
                                        <p class="text-xs text-primary-600 mt-1 cursor-pointer hover:underline" wire:click="addManualItem">
                                            Da clic en "Concepto Manual" para agregarlo tú mismo.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items Table --}}
            @if(count($items) > 0)
                {{-- Desktop Table --}}
                <div class="hidden md:block w-full overflow-x-auto">
                    <table class="w-full text-left table-inputs-compact">
                        <thead>
                            <tr class="bg-surface-main border-b border-border/40 text-xs font-semibold text-text-muted uppercase tracking-wider">
                                <th class="pl-6 pr-4 py-3 whitespace-nowrap {{ $advancedMode ? 'w-[25%]' : 'w-[30%]' }}">Concepto</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap w-[10%]">Tipo</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap w-[10%]">Unidad</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap w-[10%]">Cant.</th>
                                @if($advancedMode)
                                    <th class="px-4 py-3 text-right whitespace-nowrap w-[15%]">Costo U.</th>
                                @endif
                                <th class="px-4 py-3 text-right whitespace-nowrap {{ $advancedMode ? 'w-[15%]' : 'w-[20%]' }}">{{ $advancedMode ? 'Precio V. (P.U.)' : 'Precio Unitario' }}</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap {{ $advancedMode ? 'w-[20%]' : 'w-[15%]' }}">Total Línea</th>
                                <th class="pr-6 pl-4 py-3 w-[5%]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            @foreach($items as $index => $item)
                                <tr class="align-middle hover:bg-surface-hover/30 transition-colors group"
                                    wire:key="item-row-{{ $index }}">
                                    <td class="pl-6 pr-4 py-4">
                                        @if($item['product_id'])
                                            <div class="flex flex-col">
                                                <span class="text-body font-medium text-text-primary">{{ $item['concept'] }}</span>
                                            </div>
                                        @else
                                            <div class="flex flex-col gap-2">
                                                <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.concept"
                                                    class="input input-inline text-small w-full {{ $errors->has('items.'.$index.'.concept') ? 'border-error-500 bg-error-50/50' : '' }}"
                                                    placeholder="Escribe un concepto...">
                                                @error('items.'.$index.'.concept')
                                                    <p class="text-error-600 text-[10px] font-medium leading-tight mt-0.5">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 {{ $advancedMode ? 'align-top pt-5' : 'align-middle' }}">
                                        <x-custom-select wire:model.live="items.{{ $index }}.item_type"
                                            :options="$itemTypes"
                                            minSearch="10"
                                            placeholder="Tipo" />
                                    </td>
                                    <td class="px-4 py-4 {{ $advancedMode ? 'align-top pt-5' : 'align-middle' }}">
                                        @if($item['product_id'])
                                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-surface-hover text-text-secondary border border-border/60 text-[10px] font-bold uppercase tracking-wider">
                                                {{ $item['measure_abbr'] }}
                                            </span>
                                        @else
                                            <x-custom-select wire:model.live="items.{{ $index }}.measure_id"
                                                :options="$measures"
                                                placeholder="Unidad" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 {{ $advancedMode ? 'align-top pt-5' : 'align-middle' }}">
                                        <div class="relative">
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                                step="0.01"
                                                class="input text-center tabular-nums text-small w-full py-1.5 shadow-sm transition-colors {{ $errors->has('items.'.$index.'.quantity') ? 'border-error-500 focus:border-error-500 bg-error-50' : 'border-border/60 bg-surface-card focus:border-primary-500' }}">
                                        </div>
                                        @error('items.'.$index.'.quantity')
                                            <p class="text-error-600 text-[10px] font-medium leading-tight mt-1 text-center">{{ current(explode(' ', $message)) }} inv.</p>
                                        @enderror
                                    </td>
                                    @if($advancedMode)
                                    <td class="px-4 py-4 align-top pt-5">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none font-medium">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_cost"
                                                step="0.01"
                                                class="input pl-7 pr-3 text-right tabular-nums text-small w-full py-1.5 shadow-sm transition-colors focus:border-primary-500
                                                {{ ($item['unit_cost'] ?? 0) == 0 ? 'bg-warning-50 border-warning-300 text-warning-800' : 'bg-surface-card border-border/60' }}">
                                        </div>
                                        @if(($item['unit_cost'] ?? 0) == 0)
                                            <p class="text-[9px] text-warning-600 mt-1 text-right leading-tight tracking-tight">Sin historial,<br>ingresa costo</p>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="px-4 py-4 {{ $advancedMode ? 'align-top pt-5' : 'align-middle' }}">
                                        <div class="relative group/margin">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none font-medium">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                                step="0.01"
                                                class="input pl-7 pr-3 text-right tabular-nums text-small font-medium text-text-primary w-full py-1.5 shadow-sm transition-colors {{ $errors->has('items.'.$index.'.unit_price') ? 'border-error-500 focus:border-error-500 bg-error-50' : 'border-border/60 bg-surface-card focus:border-primary-500' }}">
                                            
                                            @if($advancedMode)
                                            <!-- Indicador de Margen -->
                                            <div class="absolute -top-7 right-0 bg-surface-main text-[10px] font-bold text-text-secondary border border-border/80 px-2 py-0.5 rounded opacity-0 group-hover/margin:opacity-100 transition-opacity pointer-events-none shadow-sm whitespace-nowrap z-10">
                                                Margen: {{ number_format($item['margin_percent'] ?? 0, 1) }}%
                                            </div>
                                            @endif
                                        </div>
                                        @error('items.'.$index.'.unit_price')
                                            <p class="text-error-600 text-[10px] font-medium leading-tight mt-1 text-right">Monto inv.</p>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-text-primary tabular-nums text-small {{ $advancedMode ? 'align-top pt-6' : 'align-middle' }}">
                                        ${{ number_format($item['line_total'], 2) }}
                                    </td>
                                    <td class="pr-6 pl-4 py-4 text-center {{ $advancedMode ? 'align-top pt-5' : 'align-middle' }}">
                                        <x-button type="button" wire:click="removeItem({{ $index }})" variant="icon-danger" icon="trash-2" class="opacity-40 group-hover:opacity-100 transition-opacity" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Formulario de conceptos (Mobile) --}}
                <div class="md:hidden flex flex-col gap-4 px-6 pt-6 pb-2">
                    @foreach($items as $index => $item)
                        <div class="bg-surface-main rounded-xl p-5 relative flex flex-col gap-3" wire:key="mobile-item-{{ $index }}">
                            <div class="flex justify-between items-center border-b border-border/40 pb-2 mb-1">
                                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                                    Concepto {{ $index + 1 }}
                                </span>
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-danger opacity-70 hover:opacity-100 p-1 -mr-1 transition-opacity">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </div>
                            
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-text-primary">Producto</label>
                                @if($item['product_id'])
                                    <div class="flex flex-col p-3 bg-surface-card rounded-lg border border-border">
                                        <span class="text-body font-medium text-text-primary">{{ $item['concept'] }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <select wire:model.live="items.{{ $index }}.item_type" class="text-[9px] bg-surface-hover border border-border/50 rounded text-text-muted font-bold uppercase tracking-wider cursor-pointer hover:border-primary-300 hover:text-primary-600 focus:ring-0 py-0.5 px-1.5 h-auto leading-tight transition-colors">
                                                <option value="material">Material</option>
                                                <option value="labor">Mano de Obra</option>
                                                <option value="service">Servicio</option>
                                            </select>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface-hover text-text-secondary border border-border text-[9px] font-bold uppercase tracking-wider">
                                                {{ $item['measure_abbr'] }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.concept"
                                        class="input w-full" placeholder="Escribe un concepto...">
                                    <div class="mt-2 flex gap-2">
                                        <select wire:model.live="items.{{ $index }}.item_type" class="text-[9px] bg-surface-hover border border-border/50 rounded text-text-muted font-bold uppercase tracking-wider cursor-pointer hover:border-primary-300 hover:text-primary-600 focus:ring-0 py-1.5 px-2 h-auto leading-tight transition-colors">
                                            <option value="material">Material</option>
                                            <option value="labor">Mano de Obra</option>
                                            <option value="service">Servicio</option>
                                        </select>
                                        <div class="flex-1">
                                            <x-custom-select wire:model.live="items.{{ $index }}.measure_id"
                                                :options="$measures"
                                                placeholder="Unidad" />
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="grid {{ $advancedMode ? 'grid-cols-2' : 'grid-cols-1' }} gap-3">
                                @if($advancedMode)
                                <div>
                                    <label class="text-xs font-medium text-text-primary mb-1 block">Costo U.</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none">$</span>
                                        <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_cost" step="0.01"
                                            class="input w-full pl-7" placeholder="0.00">
                                    </div>
                                </div>
                                @endif
                                <div>
                                    <label class="text-xs font-medium text-text-primary mb-1 block">Cantidad</label>
                                    <div class="relative">
                                        <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.quantity" step="0.01"
                                            class="input w-full pr-8" placeholder="0">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-text-muted pointer-events-none">{{ $item['measure_abbr'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-text-primary mb-1 block">{{ $advancedMode ? 'Precio Venta (P.U.)' : 'Precio Unitario' }}</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none">$</span>
                                    <input type="number" wire:model.live.debounce.500ms="items.{{ $index }}.unit_price" step="0.01"
                                        class="input w-full pl-7 font-medium text-text-primary" placeholder="0.00">
                                    @if($advancedMode)
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-text-muted bg-surface-card px-1 rounded pointer-events-none border border-border/50">M. {{ number_format($item['margin_percent'] ?? 0, 1) }}%</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-2 flex justify-between items-center pt-3 border-t border-border/50">
                                <span class="text-small font-medium text-text-secondary">Total Línea:</span>
                                <span class="font-bold text-text-primary tabular-nums">
                                    ${{ number_format($item['line_total'], 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Totales y Opciones --}}
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end px-6 pt-6 pb-8 border-t border-border/40 gap-6">
                    {{-- Opciones de Presupuesto --}}
                    <div class="w-full md:w-auto">
                        <div class="bg-surface-main border border-border/60 rounded-xl p-4 flex items-center justify-between gap-6 shadow-sm w-full sm:w-auto min-w-[250px]">
                            <div class="flex items-start gap-3">
                                <div class="bg-surface-card p-2 rounded-lg border border-border shadow-sm">
                                    <x-lucide-receipt class="w-4 h-4 text-primary-600" />
                                </div>
                                <div>
                                    <h4 class="text-small font-semibold text-text-primary">Desglose de IVA</h4>
                                    <p class="text-[11px] text-text-muted mt-0.5">Calcula 16% sobre venta</p>
                                </div>
                            </div>
                            <div>
                                <x-toggle wire:model.live="includeTax" />
                            </div>
                        </div>
                    </div>

                    <x-totals-summary class="w-full sm:w-1/2 md:w-1/3 min-w-[280px]">
                        @if($advancedMode)
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">Costo Base</span>
                            <span class="text-small font-medium text-text-muted tabular-nums">
                                ${{ number_format($this->cost_subtotal, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-6 mt-1 pb-2 border-b border-border/40">
                            <span class="text-small text-text-muted">Ganancia (Margen global {{ $marginPercent }}%)</span>
                            <span class="text-small font-semibold text-primary-600 tabular-nums">
                                +${{ number_format($this->subtotal - $this->cost_subtotal, 2) }}
                            </span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between gap-6 {{ $advancedMode ? 'pt-2' : '' }}">
                            <span class="text-small text-text-secondary font-medium">Subtotal Venta</span>
                            <span class="text-small font-bold text-text-secondary tabular-nums">
                                ${{ number_format($this->subtotal, 2) }}
                            </span>
                        </div>
                        @if($includeTax)
                            <div class="flex items-center justify-between gap-6 mt-1">
                                <span class="text-small text-text-muted">IVA (16%)</span>
                                <span class="text-small font-medium text-text-muted tabular-nums">
                                    ${{ number_format($this->tax_amount, 2) }}
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-border/60">
                            <span class="text-body font-semibold text-text-primary">Total Presupuestado</span>
                            <span class="text-2xl font-bold text-text-primary tabular-nums tracking-tight">
                                ${{ number_format($this->grand_total, 2) }}
                            </span>
                        </div>

                        {{-- Botón de Acción --}}
                        <div class="pt-6 mt-6">
                            <x-button wire:click="save" variant="primary" target="save"
                                class="w-full py-3 rounded-xl shadow-sm text-small tracking-wide"
                                icon="check-circle">
                                Guardar Presupuesto
                            </x-button>
                        </div>
                    </x-totals-summary>
                </div>
            @else
                <div class="px-6 pb-6 pt-2">
                    <x-empty-state icon="package-plus" title="Sin conceptos"
                        message="Busca un producto del catálogo arriba o agrega un concepto manual."
                        class="py-12" />
                </div>
            @endif
        </x-card>
    </div>
</div>