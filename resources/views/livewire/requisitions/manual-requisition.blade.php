<div>
    {{-- Page Header --}}
    <x-page-header subtitle="Requisiciones" title="Nueva Requisición Manual">
        <x-slot:actions>
            <a href="{{ route('requisiciones.index') }}" class="btn-secondary" wire:navigate>
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Volver
            </a>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="createRequisition" class="space-y-6">
        {{-- 1. Datos Generales --}}
        <div class="card p-6">
            <h2 class="text-h2 text-text-primary mb-4">Datos Generales</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-form-field label="Proyecto" required error="{{ $errors->first('reqProjectId') }}">
                    <x-custom-select wire:model="reqProjectId" :options="$projects->pluck('name', 'id')->toArray()"
                        placeholder="Seleccionar proyecto..." />
                </x-form-field>

                @php
                    $vendorOptions = [];
                    foreach ($vendors as $vendor) {
                        $vendorOptions[$vendor->id] = $vendor->name . ' (' . ($vendor->supplier->trade_name ?? 'Sin Proveedor') . ')';
                    }
                @endphp
                <x-form-field label="Vendedor (Opcional)" error="{{ $errors->first('reqVendorId') }}">
                    <x-custom-select wire:model="reqVendorId" :options="$vendorOptions" placeholder="Vendedor..." />
                </x-form-field>

                <x-form-field label="Fecha" required error="{{ $errors->first('reqDate') }}">
                    <input wire:model="reqDate" type="date" class="input w-full">
                </x-form-field>

                <div class="md:col-span-3">
                    <x-form-field label="Anotaciones" error="{{ $errors->first('reqAnnotations') }}">
                        <textarea wire:model="reqAnnotations" class="input w-full" rows="2"
                            placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                    </x-form-field>
                </div>
            </div>
        </div>

        {{-- 2. Productos --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-h2 text-text-primary">Productos</h2>
                    @if(count($items) > 0)
                        <span class="badge badge-secondary">{{ count($items) }}
                            {{ count($items) === 1 ? 'producto' : 'productos' }}</span>
                    @endif
                </div>
                <button type="button" wire:click="addManualItem" class="btn-secondary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Concepto Manual
                </button>
            </div>

            {{-- Search Product --}}
            <div class="px-6 pb-4" x-data>
                <div class="relative">
                    <div class="relative">
                        <i data-lucide="search"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
                        <input wire:model.live.debounce.300ms="searchQuery" type="text"
                            class="input pl-10 border-border focus:border-primary-500 bg-surface-card"
                            placeholder="Buscar producto del catálogo para agregar...">
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
                <div class="table-embedded border-t-0 border-x-0 rounded-none md:!overflow-visible">
                    <table>
                        <thead>
                            <tr>
                                <th class="w-[30%]">Producto</th>
                                <th class="w-[15%]">Categoría</th>
                                <th class="text-center w-[10%]">Cant.</th>
                                <th class="text-center w-[10%]">Unidad</th>
                                <th class="text-right w-[12%]">P.U. s/IVA</th>
                                <th class="text-right w-[10%]">Subtotal</th>
                                <th class="text-right w-[10%]">Total c/IVA</th>
                                <th class="w-[3%]"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $i => $item)
                                @php
                                    $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                                    $iva = round($subtotal * 0.16, 2);
                                    $total = $subtotal + $iva;
                                @endphp
                                <tr class="align-top hover:bg-surface-hover/30 transition-all duration-200 group"
                                    wire:key="item-row-{{ $i }}">
                                    <td class="pb-4">
                                        <input wire:model.live.debounce.400ms="items.{{ $i }}.name" type="text"
                                            class="input text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white w-full"
                                            placeholder="Nombre del producto">
                                    </td>
                                    <td class="pb-4">
                                        <x-custom-select wire:model.live="items.{{ $i }}.category_id"
                                            :options="$categories->pluck('name', 'id')->toArray()"
                                            placeholder="Sin categoría" />
                                    </td>
                                    <td class="pb-4">
                                        <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number"
                                            step="0.01"
                                            class="input text-center tabular-nums text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white"
                                            placeholder="0">
                                    </td>
                                    <td class="pb-4">
                                        @php
                                            $measureOptions = $measures->mapWithKeys(fn($m) => [
                                                ($m->abbreviation ?? $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')
                                            ])->toArray();
                                        @endphp
                                        <x-custom-select wire:model.live="items.{{ $i }}.unit" :options="$measureOptions"
                                            placeholder="Unidad" />
                                    </td>
                                    <td class="pb-4">
                                        <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number"
                                            step="0.01"
                                            class="input text-right tabular-nums text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white"
                                            placeholder="0.00">
                                    </td>
                                    <td
                                        class="text-right font-medium text-text-primary tabular-nums text-small align-middle pb-4">
                                        ${{ number_format($subtotal, 2, '.', ',') }}
                                    </td>
                                    <td
                                        class="text-right font-semibold text-text-primary tabular-nums text-small align-middle pb-4">
                                        ${{ number_format($total, 2, '.', ',') }}
                                    </td>
                                    <td class="text-center pb-4">
                                        <button type="button" wire:click="removeItem({{ $i }})"
                                            class="btn-icon-danger mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totales --}}
                @php
                    $subtotalTotal = collect($items)->sum(fn($i) => ($i['quantity'] ?? 0) * ($i['unit_price'] ?? 0));
                    $ivaTotal = round($subtotalTotal * 0.16, 2);
                    $grandTotal = $subtotalTotal + $ivaTotal;
                @endphp
                <div class="flex justify-end px-6 py-5">
                    <x-totals-summary>
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">Subtotal s/IVA</span>
                            <span
                                class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($subtotalTotal, 2, '.', ',') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">IVA (16%)</span>
                            <span
                                class="text-small font-medium text-text-muted tabular-nums">${{ number_format($ivaTotal, 2, '.', ',') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                            <span class="text-body font-semibold text-text-primary">Total c/IVA</span>
                            <span
                                class="text-h3 font-bold text-text-primary tabular-nums">${{ number_format($grandTotal, 2, '.', ',') }}</span>
                        </div>
                    </x-totals-summary>
                </div>
            @else
                <div class="text-center py-12 mx-6 mb-6 border-2 border-dashed border-border rounded-xl">
                    <div class="w-12 h-12 rounded-xl bg-surface-hover flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="package-plus" class="w-6 h-6 text-text-muted"></i>
                    </div>
                    <p class="text-body font-medium text-text-primary mb-1">Sin productos</p>
                    <p class="text-small text-text-muted">Busca un producto del catálogo arriba o agrega un concepto manual.
                    </p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end pt-6">
            <x-submit-button target="createRequisition">Crear Requisición</x-submit-button>
        </div>
    </form>
</div>