<div>
    @php
        $breadcrumbs = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Requisiciones', 'url' => route('requisiciones.index')],
            ['label' => 'Nueva Manual']
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" title="Nueva Requisición (Manual)"> 
    </x-page-header>

    <form wire:submit="createRequisition" class="space-y-6">
        {{-- 1. Datos Generales --}}
        <x-card class="mb-6">
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
            <div class="px-6 py-4 border-b border-border/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h3 class="font-medium text-text-primary tracking-tight">Productos</h3>
                    @if(count($form->items) > 0)
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-surface-main border border-border/60 text-xs font-medium text-text-muted">
                            <x-lucide-package class="w-3.5 h-3.5" />
                            {{ count($form->items) }} {{ count($form->items) === 1 ? 'artículo' : 'artículos' }}
                        </span>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <x-button wire:click="addManualItem" variant="soft" icon="plus" class="text-xs w-full sm:w-auto justify-center">
                        Concepto Manual
                    </x-button>
                </div>
            </div>

            <div class="px-6 py-5">
                {{-- Search Product --}}
                <div x-data="{ open: true }" class="relative max-w-lg" @click.outside="open = false">
                    <div class="relative">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                        <input wire:model.live.debounce.300ms="searchQuery" @focus="open = true" @input="open = true" type="text"
                            class="input w-full pl-10 border-border focus:border-primary-500 bg-surface-card"
                            placeholder="Buscar producto del catálogo para agregar...">
                        <div wire:loading wire:target="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <span class="spinner spinner-sm"></span>
                        </div>
                    </div>

                    {{-- Dropdown Results --}}
                    @if(!empty($searchResults))
                        <div x-show="open" x-cloak class="absolute z-[45] mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in">
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
                        <div x-show="open" x-cloak class="absolute z-[45] mt-1 w-full bg-surface-card rounded-xl shadow-lg border border-border overflow-hidden animate-scale-in p-4 text-center">
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
                <div class="hidden md:block w-full overflow-x-auto">
                    <table class="w-full text-left table-inputs-compact">
                        <thead>
                            <tr class="bg-surface-main border-b border-border/40 text-xs font-semibold text-text-muted uppercase tracking-wider">
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
                        <tbody class="divide-y divide-border/40">
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
                                            class="input input-inline text-small w-full"
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
                                            class="input input-inline text-center tabular-nums text-small w-full"
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
                                            class="input input-inline text-right tabular-nums text-small w-full"
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
                                        <x-button wire:click="removeItem({{ $i }})" variant="icon-danger" icon="trash-2" class="mt-1 opacity-40 hover:opacity-100 focus:opacity-100 transition-opacity" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden flex flex-col gap-4 px-6 pt-6">
                    @foreach($form->items as $i => $item)
                        @php
                            $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                            $iva = round($subtotal * 0.16, 2);
                            $total = $subtotal + $iva;
                        @endphp
                        <div class="bg-surface-main rounded-xl p-5 relative flex flex-col gap-3" wire:key="mobile-item-{{ $item['id'] ?? $i }}">
                            {{-- Card Header --}}
                            <div class="flex justify-between items-center border-b border-border/40 pb-2 mb-1">
                                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                                    Artículo {{ $i + 1 }}
                                </span>
                                <button type="button" wire:click="removeItem({{ $i }})" class="text-danger opacity-70 hover:opacity-100 p-1 -mr-1 transition-opacity">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </div>
                            
                            <x-form-field label="Producto">
                                <input wire:model.live.debounce.400ms="form.items.{{ $i }}.name" type="text"
                                    class="input w-full" placeholder="Nombre del producto">
                            </x-form-field>

                                <div class="grid grid-cols-2 gap-3">
                                    <x-form-field label="Categoría">
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.category_id"
                                            :options="$categories->pluck('name', 'id')->toArray()"
                                            placeholder="Sin categoría" />
                                    </x-form-field>
                                    <x-form-field label="Unidad">
                                        <x-custom-select wire:model.live="form.items.{{ $i }}.unit" :options="$measureOptions"
                                            placeholder="Unidad" />
                                    </x-form-field>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <x-form-field label="Cantidad">
                                        <input wire:model.live.debounce.400ms="form.items.{{ $i }}.quantity" type="number" step="0.01"
                                            class="input w-full" placeholder="0">
                                    </x-form-field>
                                    <x-form-field label="Precio U.">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-small text-text-muted pointer-events-none">$</span>
                                            <input wire:model.live.debounce.400ms="form.items.{{ $i }}.unit_price" type="number" step="0.01"
                                                class="input w-full pl-7" placeholder="0.00">
                                        </div>
                                    </x-form-field>
                                </div>

                                <div class="mt-2 flex justify-between items-center pt-3 border-t border-border/50">
                                    <span class="text-small font-medium text-text-secondary">Total Línea:</span>
                                    <span class="font-bold text-h3 text-text-primary tabular-nums">
                                        ${{ number_format($total, 2, '.', ',') }}
                                    </span>
                                </div>
                        </div>
                    @endforeach
                </div>

                {{-- Totales (calculados en ManualRequisition::render()) --}}
                <div class="flex justify-end px-6 pt-6 pb-8 border-t border-border/40">
                    <x-totals-summary class="w-full sm:w-1/2 md:w-1/3 min-w-[280px]">
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
                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-border/60">
                            <span class="text-body font-semibold text-text-primary">Total final</span>
                            <span class="text-2xl font-bold text-text-primary tabular-nums tracking-tight">
                                ${{ number_format($totals['total'], 2, '.', ',') }}
                            </span>
                        </div>
                        
                        {{-- Botón de Acción --}}
                        <div class="pt-6 mt-6">
                            <x-button type="submit" variant="primary" target="createRequisition"
                                class="w-full py-3 rounded-xl shadow-sm text-small tracking-wide"
                                icon="check-circle">
                                Crear Requisición
                            </x-button>
                        </div>
                    </x-totals-summary>
                </div>
            @else
                <div class="px-6 pb-6 pt-2">
                    <x-empty-state icon="package-plus" title="Sin productos"
                        message="Busca un producto del catálogo arriba o agrega un concepto manual."
                        class="py-12" />
                </div>
            @endif
        </x-card>
    </form>
</div>
