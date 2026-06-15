<div>
    <x-page-header 
        subtitle="Requisiciones" 
        title="Nueva Requisición Manual" 
        backUrl="{{ $source === 'borradores' ? route('requisiciones.index', ['tab' => 'borradores']) : route('requisiciones.index') }}">
    </x-page-header>

    <form wire:submit="createRequisition" class="space-y-6">
        {{-- 1. Datos Generales --}}
        <x-card>
            <x-card.header title="Datos Generales" />
            <x-card.body>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form-field label="Proyecto" required error="{{ $errors->first('form.projectId') }}">
                    <x-custom-select wire:model="form.projectId" :options="$projects->pluck('name', 'id')->toArray()"
                        placeholder="Seleccionar proyecto..." />
                </x-form-field>

                @php
                    $vendorOptions = [];
                    foreach ($vendors as $vendor) {
                        $vendorOptions[$vendor->id] = $vendor->name . ' (' . ($vendor->supplier->trade_name ?? 'Sin Proveedor') . ')';
                    }
                @endphp
                <x-form-field label="Vendedor" error="{{ $errors->first('form.vendorId') }}">
                    <x-custom-select wire:model="form.vendorId" :options="$vendorOptions" placeholder="Vendedor..." />
                </x-form-field>

                <x-form-field label="Fecha" required error="{{ $errors->first('form.date') }}">
                    <x-date-picker wire:model="form.date" />
                </x-form-field>

                <div class="md:col-span-3">
                    <x-form-field label="Anotaciones" error="{{ $errors->first('form.annotations') }}">
                        <textarea wire:model="form.annotations" class="input w-full" rows="2"
                            placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                    </x-form-field>
                </div>
            </div>
            </x-card.body>
        </x-card>

        {{-- 2. Productos --}}
        <x-card class="mb-6 overflow-hidden">
            <x-card.header title="Productos">
                <x-slot:action>
                    <div class="flex items-center gap-3">
                        @if(count($form->items) > 0)
                            <x-badge variant="secondary">{{ count($form->items) }}</x-badge>
                        @endif
                        <x-button wire:click="addManualItem" variant="secondary" icon="plus">
                            Concepto Manual
                        </x-button>
                    </div>
                </x-slot:action>
            </x-card.header>

            <div class="px-6 py-5">
                {{-- Search Product --}}
                <div x-data class="relative max-w-lg">
                    <div class="relative">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                        <input wire:model.live.debounce.300ms="searchQuery" type="text"
                            class="input w-full pl-10 border-border focus:border-primary-500 bg-surface-card"
                            placeholder="Buscar producto del catálogo para agregar...">
                        <div wire:loading wire:target="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <span class="spinner spinner-sm"></span>
                        </div>
                    </div>

                    {{-- Dropdown Results --}}
                    @if(!empty($searchResults))
                        <div class="absolute z-[45] mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in">
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
                                                    <x-badge variant="secondary">{{ $product['measure_abbr'] }}</x-badge>
                                                </div>
                                            </div>
                                            @if($product['last_price'] > 0)
                                                <div class="text-right">
                                                    <p class="text-xs text-text-muted">Último costo</p>
                                                    <p class="text-small font-semibold text-text-primary">
                                                        ${{ number_format($product['last_price'], 2) }}</p>
                                                </div>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif(strlen($searchQuery) >= 2 && empty($searchResults))
                        <div class="absolute z-[45] mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in p-4 text-center">
                            <p class="text-small text-text-muted">No se encontraron productos en el catálogo.</p>
                            <p class="text-xs text-primary-600 mt-1 cursor-pointer hover:underline" wire:click="addManualItem">
                                Da clic en "Concepto Manual" para agregarlo tú mismo.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items Table --}}
            @if(count($form->items) > 0)
                {{-- Desktop Table --}}
                <x-card.table class="hidden md:block w-full">
                    <table class="w-full text-left table-inputs-compact">
                        <thead>
                            <tr class="bg-surface-th border-y border-border text-xs font-semibold text-text-muted uppercase tracking-wider">
                                <th class="pl-6 pr-4 py-3 whitespace-nowrap w-[30%]">Producto</th>
                                <th class="px-4 py-3 whitespace-nowrap w-[15%]">Categoría</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap w-[10%]">Cant.</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap w-[10%]">Unidad</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap w-[12%]">P.U. s/IVA</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap w-[10%]">Subtotal</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap w-[10%]">Total c/IVA</th>
                                <th class="pr-6 pl-4 py-3 w-[3%]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border border-b border-border">
                            @foreach($form->items as $i => $item)
                                @php
                                    $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                                    $iva = round($subtotal * 0.16, 2);
                                    $total = $subtotal + $iva;
                                @endphp
                                <tr class="align-middle hover:bg-surface-hover/30 transition-colors group"
                                    wire:key="item-row-{{ $item['id'] ?? $i }}">
                                    <td class="pl-6 pr-4 py-4">
                                        <input wire:model.live.debounce.400ms="form.items.{{ $i }}.name" type="text"
                                            class="input text-small w-full"
                                            placeholder="Nombre del producto">
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.category_id"
                                            :options="$categories->pluck('name', 'id')->toArray()"
                                            placeholder="Sin categoría" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <input wire:model.live.debounce.400ms="form.items.{{ $i }}.quantity" type="number"
                                            step="0.01"
                                            class="input text-center tabular-nums text-small"
                                            placeholder="0">
                                    </td>
                                    <td class="px-4 py-4">
                                        @php
                                            $measureOptions = $measures->mapWithKeys(fn($m) => [
                                                ($m->abbreviation ?? $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')
                                            ])->toArray();
                                        @endphp
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.unit" :options="$measureOptions"
                                            placeholder="Unidad" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <input wire:model.live.debounce.400ms="form.items.{{ $i }}.unit_price" type="number"
                                            step="0.01"
                                            class="input text-right tabular-nums text-small"
                                            placeholder="0.00">
                                    </td>
                                    <td
                                        class="px-4 py-4 text-right font-medium text-text-primary tabular-nums text-small align-middle">
                                        ${{ number_format($subtotal, 2, '.', ',') }}
                                    </td>
                                    <td
                                        class="px-4 py-4 text-right font-semibold text-text-primary tabular-nums text-small align-middle">
                                        ${{ number_format($total, 2, '.', ',') }}
                                    </td>
                                    <td class="pr-6 pl-4 py-4 text-center">
                                        <x-button wire:click="removeItem({{ $i }})" variant="icon-danger" icon="trash-2" class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card.table>

                {{-- Mobile Cards --}}
                <div class="md:hidden flex flex-col gap-4 px-6 pt-6">
                    @foreach($form->items as $i => $item)
                        @php
                            $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                            $iva = round($subtotal * 0.16, 2);
                            $total = $subtotal + $iva;
                        @endphp
                        <div class="bg-surface-main/30 border border-border/50 rounded-xl p-4 relative" wire:key="mobile-item-{{ $item['id'] ?? $i }}">
                            <button type="button" wire:click="removeItem({{ $i }})" class="absolute top-2 right-2 text-danger opacity-70 hover:opacity-100 p-1">
                                <x-lucide-x class="w-5 h-5" />
                            </button>
                            
                            <div class="flex flex-col gap-3">
                                <div class="pr-8">
                                    <label class="text-xs text-text-muted mb-1 block">Producto</label>
                                    <input wire:model.live.debounce.400ms="form.items.{{ $i }}.name" type="text"
                                        class="input w-full" placeholder="Nombre del producto">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-text-muted mb-1 block">Categoría</label>
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.category_id"
                                            :options="$categories->pluck('name', 'id')->toArray()"
                                            placeholder="Sin categoría" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-text-muted mb-1 block">Unidad</label>
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.unit" :options="$measureOptions"
                                            placeholder="Unidad" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-text-muted mb-1 block">Cantidad</label>
                                        <input wire:model.live.debounce.400ms="form.items.{{ $i }}.quantity" type="number" step="0.01"
                                            class="input w-full" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="text-xs text-text-muted mb-1 block">Precio U.</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none">$</span>
                                            <input wire:model.live.debounce.400ms="form.items.{{ $i }}.unit_price" type="number" step="0.01"
                                                class="input w-full pl-7" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 flex justify-between items-center pt-3 border-t border-border/50">
                                    <span class="text-small font-medium text-text-secondary">Total Linea:</span>
                                    <span class="font-bold text-h3 text-text-primary tabular-nums">
                                        ${{ number_format($total, 2, '.', ',') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Totales (calculados en ManualRequisition::render()) --}}
                <div class="flex justify-end px-6 pt-6 pb-8">
                    <div class="w-full sm:w-1/2 md:w-1/3 min-w-[250px]">
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">Subtotal s/IVA</span>
                            <span class="text-small font-medium text-text-secondary tabular-nums">
                                ${{ number_format($totals['subtotal'], 2, '.', ',') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">IVA (16%)</span>
                            <span class="text-small font-medium text-text-muted tabular-nums">
                                ${{ number_format($totals['iva'], 2, '.', ',') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                            <span class="text-body font-semibold text-text-primary">Total c/IVA</span>
                            <span class="text-h3 font-bold text-text-primary tabular-nums">
                                ${{ number_format($totals['total'], 2, '.', ',') }}
                            </span>
                        </div>
                        
                        {{-- Botón de Acción --}}
                        <div class="flex justify-end pt-6 mt-6 border-t border-border/50">
                            <x-button type="submit" variant="primary" target="createRequisition">Crear Requisición</x-button>
                        </div>
                    </div>
                </div>
            @else
                <div class="px-6 pb-6">
                    <x-empty-state icon="package-plus" title="Sin productos"
                        message="Busca un producto del catálogo arriba o agrega un concepto manual."
                        class="border border-dashed border-border rounded-xl py-10" />
                </div>
            @endif
        </x-card>
    </form>
</div>
