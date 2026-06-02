<div>
    {{-- Page Header --}}
    <x-page-header subtitle="Compras" title="Nueva Requisición Manual">
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
            <h3 class="text-small font-semibold text-text-primary mb-4 border-b border-border pb-2">Datos Generales</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="label">Proyecto *</label>
                    <x-custom-select wire:model="reqProjectId" :options="$projects->pluck('name', 'id')->toArray()"
                        placeholder="Seleccionar proyecto..." />
                    @error('reqProjectId') <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-1">
                    <label class="label">Vendedor (Opcional)</label>
                    @php
                        $vendorOptions = [];
                        foreach ($vendors as $vendor) {
                            $vendorOptions[$vendor->id] = $vendor->name . ' (' . ($vendor->supplier->trade_name ?? 'Sin Proveedor') . ')';
                        }
                    @endphp
                    <x-custom-select wire:model="reqVendorId" :options="$vendorOptions" placeholder="Vendedor..." />
                    @error('reqVendorId') <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-1">
                    <label class="label">Fecha *</label>
                    <input wire:model="reqDate" type="date" class="input w-full">
                    @error('reqDate') <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-4">
                    <label class="label">Anotaciones</label>
                    <textarea wire:model="reqAnnotations" class="input w-full" rows="2"
                        placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                    @error('reqAnnotations') <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 2. Captura de Productos --}}
        <div class="card p-6">
            <h3 class="text-small font-semibold text-text-primary mb-4 border-b border-border pb-2">Productos Solicitados</h3>

            {{-- Datalist de productos existentes --}}
            <datalist id="products-list">
                @foreach($products as $prod)
                    <option value="{{ $prod->canonical_name }}"></option>
                @endforeach
            </datalist>

            {{-- Formulario para añadir --}}
            <div class="border border-border rounded-xl p-5 mb-5 bg-surface-main space-y-4 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="label">Producto *</label>
                        <input wire:model="itemName" type="text" list="products-list" class="input w-full"
                            placeholder="Ej. Cemento Cruz Azul">
                    </div>
                    <div class="w-full sm:w-40">
                        <label class="label">Categoría</label>
                        <x-custom-select wire:model="itemCategoryId" :options="$categories->pluck('name', 'id')->toArray()" placeholder="Sin categoría" />
                    </div>
                    <div class="w-full sm:w-28">
                        <label class="label">Cant. *</label>
                        <input wire:model="itemQuantity" type="number" step="0.01" class="input w-full text-right" placeholder="0">
                    </div>
                    <div class="w-full sm:w-36">
                        <label class="label">Unidad *</label>
                        @php
                            $measureOptions = $measures->mapWithKeys(fn($m) => [
                                ($m->abbreviation ?? $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')
                            ])->toArray();
                        @endphp
                        <x-custom-select wire:model="itemUnit" :options="$measureOptions"
                            placeholder="Seleccionar..." />
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="label">Precio U.</label>
                        <input wire:model="itemPrice" type="number" step="0.01" class="input w-full text-right" placeholder="0.00">
                    </div>
                    <div class="w-full sm:w-auto shrink-0">
                        <button type="button" wire:click="addItem" class="btn-primary w-full sm:w-auto gap-1.5 h-10">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Añadir</span>
                        </button>
                    </div>
                </div>
                @error('itemName') <p class="text-xs-fluid text-danger">{{ $message }}</p> @enderror
            </div>

            {{-- Tabla de Productos Agregados --}}
            @if(count($items) > 0)
                <div class="table-container shadow-sm border border-border">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-surface-hover border-b border-border">
                                <th class="text-left px-4 py-3">Producto</th>
                                <th class="text-left px-4 py-3">Categoría</th>
                                <th class="text-center px-4 py-3">Cant.</th>
                                <th class="text-center px-4 py-3">Unidad</th>
                                <th class="text-right px-4 py-3">Precio U.</th>
                                <th class="text-right px-4 py-3">Subtotal</th>
                                <th class="text-right px-4 py-3">IVA</th>
                                <th class="text-right px-4 py-3">Total</th>
                                <th class="w-12 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($items as $i => $item)
                                @php
                                    $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                                    $iva = round($subtotal * 0.16, 2);
                                    $total = $subtotal + $iva;
                                    $catName = '';
                                    if (!empty($item['category_id'])) {
                                        $catName = $categories->firstWhere('id', $item['category_id'])?->name ?? '';
                                    }
                                @endphp
                                <tr class="hover:bg-surface-hover/50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-text-primary">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $catName ?: '—' }}</td>
                                    <td class="px-4 py-3 text-center text-text-secondary tabular-nums">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-3 text-center text-text-muted">{{ $item['unit'] }}</td>
                                    <td class="px-4 py-3 text-right text-text-secondary tabular-nums">
                                        ${{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-secondary tabular-nums">
                                        ${{ number_format($subtotal, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted tabular-nums">${{ number_format($iva, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-text-primary tabular-nums">
                                        ${{ number_format($total, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" wire:click="removeItem({{ $i }})" class="btn-icon-danger" title="Eliminar ítem">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
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
                <div class="flex justify-end mt-6">
                    <div class="min-w-[300px] bg-surface-main border border-border rounded-xl p-5 space-y-2">
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">Subtotal s/IVA</span>
                            <span class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($subtotalTotal, 2, '.', ',') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-text-muted">IVA (16%)</span>
                            <span class="text-small font-medium text-text-muted tabular-nums">${{ number_format($ivaTotal, 2, '.', ',') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                            <span class="text-body font-semibold text-text-primary">Total c/IVA</span>
                            <span class="text-h3 font-bold text-text-primary tabular-nums">${{ number_format($grandTotal, 2, '.', ',') }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12 border-2 border-dashed border-border rounded-xl">
                    <div class="w-12 h-12 rounded-xl bg-surface-hover flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="package-plus" class="w-6 h-6 text-text-muted"></i>
                    </div>
                    <p class="text-body font-medium text-text-primary mb-1">Sin productos</p>
                    <p class="text-small text-text-muted">Usa el formulario superior para añadir artículos a tu requisición.</p>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('requisiciones.index') }}" wire:navigate class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="createRequisition">
                <span wire:loading.class="opacity-0" wire:target="createRequisition" class="flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Crear Requisición
                </span>
                <span wire:loading wire:target="createRequisition" class="absolute flex items-center justify-center">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                </span>
            </button>
        </div>
    </form>
</div>
