<div x-data="{
    showPreviewModal: false,
    previewUrl: null,
    previewType: null,
    isPdf() {
        return this.previewType === 'application/pdf' || (this.previewUrl && this.previewUrl.toLowerCase().includes('.pdf'));
    },
    isImage() {
        return (this.previewType && this.previewType.startsWith('image/')) || (this.previewUrl && this.previewUrl.match(/\.(jpeg|jpg|gif|png)$/i));
    },
    openServerPreview(url, mimeType) {
        this.previewUrl = url;
        this.previewType = mimeType;
        this.showPreviewModal = true;
    }
}">
    {{-- ═══════ WIZARD HEADER ═══════ --}}
    <x-page-header subtitle="Requisiciones" title="Subir Cotización">
        <x-slot:actions>
            <x-button href="{{ route('requisiciones.index') }}" variant="secondary" icon="arrow-left" wire:navigate>
                Volver
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8" wire:ignore.self>
        @foreach([1 => 'Subir archivo', 2 => 'Procesando', 3 => 'Revisar y guardar'] as $num => $label)
            <div wire:key="step-indicator-{{ $num }}" class="flex items-center gap-2 {{ $num < 3 ? 'flex-1' : '' }}">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-small font-semibold transition-all duration-300 border
                               {{ $step > $num ? 'bg-success text-white border-success' : ($step === $num ? 'bg-primary-600 text-white border-primary-600 shadow-sm' : 'bg-surface-card text-text-muted border-border') }}">
                        @if($step > $num)
                            <i data-lucide="check" class="w-4 h-4" wire:ignore></i>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span
                        class="text-small font-medium {{ $step >= $num ? 'text-text-primary' : 'text-text-muted' }}">{{ $label }}</span>
                </div>
                @if($num < 3)
                    <div
                        class="flex-1 h-0.5 mx-4 rounded-full {{ $step > $num ? 'bg-success' : 'bg-border' }} transition-all duration-500">
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ═══════ PASO 1: UPLOAD ═══════ --}}
    @if($step === 1)
        <div class="card max-w-2xl mx-auto">
            <div class="p-8">
                <x-file-input wire:model="file" variant="dropzone" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls" maxSize="20 MB"
                    :formats="['PDF', 'XLSX', 'JPG', 'PNG']" title="Arrastra tu cotización aquí"
                    subtitle="o haz clic para seleccionar un archivo" inputId="file-upload-input" />

                {{-- Process Button --}}
                @if($file && !$errors->has('file'))
                    <x-button wire:key="process-btn" x-data="{ visible: true }" x-show="visible"
                        @file-removed.window="visible = false" type="button"
                        wire:click="processUpload" wire:loading.attr="disabled" target="processUpload"
                        variant="primary" class="w-full mt-6" icon="scan-line">
                        Procesar Cotización
                    </x-button>
                @endif


            </div>
        </div>
    @endif

    {{-- ═══════ PASO 2: PROCESAMIENTO ═══════ --}}
    @if($step === 2)
        <div class="card max-w-lg mx-auto" @if($processingStatus === 'processing' || $processingStatus === 'pending')
        wire:poll.2s.visible="checkProcessingStatus" @endif x-data>
            <div class="p-8 text-center">
                @if($processingStatus === 'processing' || $processingStatus === 'pending')
                    {{-- Spinner premium con doble anillo --}}
                    <div class="mb-8">
                        <div class="relative w-20 h-20 mx-auto mb-6">
                            {{-- Anillo exterior decorativo --}}
                            <div class="absolute -inset-2.5 rounded-full border border-primary-100 bg-primary-50/40"></div>
                            {{-- Anillo interior con sombra --}}
                            <div class="absolute inset-0 rounded-full bg-primary-50 border border-primary-100 shadow-sm flex items-center justify-center">
                                <span class="spinner-processing"></span>
                            </div>
                        </div>
                        <h2 class="text-h1 font-semibold text-text-primary mb-2">Procesando cotización</h2>
                        <p class="text-body text-text-muted max-w-xs mx-auto leading-relaxed">
                            La IA está extrayendo los datos del documento.<br>
                            Puede tomar hasta 30 segundos.
                        </p>
                    </div>

                    <div class="inline-flex items-center gap-2 text-xs-fluid text-text-muted bg-surface-main border border-border rounded-full px-3 py-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-primary-400 shrink-0"></i>
                        <span>Puedes ir a otra página; te avisaremos al terminar</span>
                    </div>

                    <div class="mt-5 flex justify-center">
                        <x-button href="{{ route('requisiciones.index') }}" variant="secondary" icon="arrow-left" class="text-small" wire:navigate>
                            Ir a Requisiciones
                        </x-button>
                    </div>

                @elseif($processingStatus === 'failed')
                    {{-- Error state --}}
                    <div class="flex flex-col items-center max-w-sm mx-auto text-center py-2">
                        {{-- Ícono de error con borde semántico --}}
                        <div class="w-14 h-14 rounded-2xl bg-danger-light border border-danger-border flex items-center justify-center mb-5 shrink-0">
                            <i data-lucide="alert-triangle" class="w-7 h-7 text-danger" wire:ignore></i>
                        </div>

                        {{-- Título y descripción --}}
                        <h2 class="text-h2 font-semibold text-text-primary mb-1">Error al procesar</h2>
                        <p class="text-small text-text-muted mb-5">No fue posible extraer los datos del documento automáticamente.</p>

                        {{-- Caja de mensaje de error con color semántico --}}
                        <div class="w-full flex gap-2.5 items-start bg-danger-light border border-danger-border rounded-lg px-4 py-3 mb-6 text-left">
                            <i data-lucide="info" class="w-4 h-4 text-danger mt-0.5 shrink-0" wire:ignore></i>
                            <p class="text-xs-fluid text-danger leading-relaxed">
                                {{ $errorMessage ?? 'Ocurrió un error inesperado durante el procesamiento.' }}
                            </p>
                        </div>

                        {{-- Botones con jerarquía clara --}}
                        <div class="flex flex-col w-full gap-2.5">
                            {{-- Acción principal --}}
                            <x-button wire:click="retryProcessing" variant="primary" class="w-full group" icon="refresh-cw" iconClass="transition-transform group-hover:rotate-180 duration-500">
                                Reintentar
                            </x-button>
                            {{-- Separador visual --}}
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px bg-border"></div>
                                <span class="text-xs-fluid text-text-muted whitespace-nowrap">o continúa de otra forma</span>
                                <div class="flex-1 h-px bg-border"></div>
                            </div>
                            {{-- Acciones secundarias --}}
                            <div class="grid grid-cols-2 gap-2">
                                <x-button wire:click="continueManually" variant="secondary" icon="edit-3" class="w-full">
                                    Llenar manualmente
                                </x-button>
                                <x-button wire:click="resetWizard" variant="secondary" icon="file-up" class="w-full">
                                    Cambiar archivo
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══════ PASO 3: FORMULARIO EDITABLE ═══════ --}}
    @if($step === 3)
        <form wire:submit="saveRequisition" x-data>



            {{-- General Info --}}
            <div class="card mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-h2 text-text-primary">Información General</h2>
                    {{-- $quotation se resuelve en QuotationWizard::render() --}}
                    @if($quotation)
                        <x-button type="button"
                            @click="openServerPreview('{{ route('file.preview', ['path' => $quotation->file_path]) }}', '{{ str_ends_with(strtolower($quotation->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg' }}')"
                            variant="secondary" icon="file-search">
                            Ver documento
                        </x-button>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <x-form-field label="Proyecto" :required="true" :error="$errors->first('projectId')">
                        <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
                            placeholder="Seleccionar proyecto..." />
                    </x-form-field>

                    <x-form-field label="Proveedor" :error="$errors->first('supplierName')">
                        <div class="relative w-full">
                            <x-custom-combobox wire:model.live="supplierName"
                                :options="$suppliers->pluck('trade_name')->toArray()" placeholder="Nombre del proveedor..."
                                class="w-full" inputClass="{{ isset($supplierMatch['status']) ? 'pr-8' : '' }}">
                            </x-custom-combobox>
                            @if(($supplierMatch['status'] ?? '') === 'new')
                                <i data-lucide="plus-circle"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-warning pointer-events-none"
                                        title="Se creará como nuevo proveedor" wire:ignore></i>
                            @elseif(($supplierMatch['status'] ?? '') === 'fuzzy')
                                <i data-lucide="sparkles"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                    title="Similitud {{ round(($supplierMatch['confidence'] ?? 0) * 100) }}%" wire:ignore></i>
                            @elseif(($supplierMatch['status'] ?? '') === 'exact')
                                <i data-lucide="check-circle-2"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-success pointer-events-none"
                                        title="Proveedor existente" wire:ignore></i>
                            @endif
                        </div>
                    </x-form-field>

                    <x-form-field label="Tienda / Sucursal" :error="$errors->first('storeName')">
                        <input wire:model="storeName" type="text" class="input" placeholder="Sucursal (opcional)">
                    </x-form-field>

                    <x-form-field label="Fecha" :required="true" :error="$errors->first('date')">
                        <input wire:model="date" type="date" class="input">
                    </x-form-field>

                    <x-form-field label="Vendedor" :error="$errors->first('vendorName')">
                        <div class="relative w-full">
                            <x-custom-combobox wire:model.live="vendorName" :options="$vendors->pluck('name')->toArray()"
                                placeholder="Nombre del vendedor..." class="w-full"
                                inputClass="{{ isset($vendorMatch['status']) ? 'pr-8' : '' }}">
                            </x-custom-combobox>
                            @if(($vendorMatch['status'] ?? '') === 'new')
                                <i data-lucide="plus-circle"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-warning pointer-events-none"
                                        title="Se creará como nuevo vendedor" wire:ignore></i>
                            @elseif(($vendorMatch['status'] ?? '') === 'fuzzy')
                                <i data-lucide="sparkles"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                    title="Similitud {{ round(($vendorMatch['confidence'] ?? 0) * 100) }}%" wire:ignore></i>
                            @elseif(($vendorMatch['status'] ?? '') === 'exact')
                                <i data-lucide="check-circle-2"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-success pointer-events-none"
                                        title="Vendedor existente" wire:ignore></i>
                            @endif
                        </div>
                    </x-form-field>
                </div>

                <x-form-field label="Anotaciones" :error="$errors->first('annotations')" class="mt-4">
                    <textarea wire:model="annotations" class="input" rows="2"
                        placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                </x-form-field>
            </div>



            <div class="card mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-h2 text-text-primary">Productos</h2>
                        @if(count($items) > 0)
                            <x-badge variant="secondary">{{ count($items) }}</x-badge>
                                {{ count($items) === 1 ? 'producto' : 'productos' }}
                        @endif
                    </div>
                    <x-button wire:click="addItem" variant="secondary" icon="plus">
                        Agregar
                    </x-button>
                </div>

                @error('items') <x-alert type="danger" message="{{ $message }}" class="mb-4" /> @enderror


                @if(count($items) > 0)
                    {{-- Tabla de productos --}}
                    <div class="table-embedded table-embedded-form">
                        <table>
                            <thead>
                                <tr>
                                    <th class="w-[26%]">Producto</th>
                                    <th class="w-[13%]">Categoría</th>
                                    <th class="text-center w-[8%]">Cant.</th>
                                    <th class="text-center w-[9%]">Unidad</th>
                                    <th class="text-right w-[13%]">P.U. s/IVA</th>
                                    <th class="text-right w-[13%]">Subtotal</th>
                                    <th class="text-right w-[12%]">Total c/IVA</th>
                                    <th class="w-[6%]"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                    @php
                                        $itemSubtotal = $item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
                                        $itemTotal = $item['line_total'] ?? ($itemSubtotal + ($item['tax_amount'] ?? 0));
                                        $productStatus = $item['_match']['product']['status'] ?? '';
                                        $productBorder = match (true) {
                                            $productStatus === 'exact' => 'border-success/30 bg-success/5',
                                            $productStatus === 'fuzzy' => 'border-primary-500/30 bg-primary-50/5',
                                            $productStatus === 'new'   => 'border-warning/30 bg-warning/5',
                                            default => '',
                                        };
                                    @endphp
                                    <tr class="align-top hover:bg-surface-hover/30 transition-all duration-200 group"
                                        wire:key="item-row-{{ $i }}">
                                        {{-- Nombre / Producto --}}
                                        <td class="pb-4">
                                            @php
                                                $isFuzzyPending      = isset($item['product_confirmed'])
                                                    && !$item['product_confirmed']
                                                    && ($item['_match']['product']['status'] ?? '') === 'fuzzy';
                                                $productStatus       = $item['_match']['product']['status'] ?? '';
                                                $hasProductIndicator = $isFuzzyPending || in_array($productStatus, ['exact', 'new']);
                                            @endphp
                                            <div class="relative">
                                                <x-conflict-popover type="fuzzy-product" :item="$item" :index="$i">
                                                    <input wire:model.live.debounce.600ms="items.{{ $i }}.name" type="text"
                                                        class="input input-inline {{ $productBorder }} text-small w-full {{ $hasProductIndicator ? 'pr-8' : '' }}"
                                                        placeholder="Nombre del producto">
                                                </x-conflict-popover>
                                                {{-- Indicadores estáticos (no interactivos) --}}
                                                @if(!$isFuzzyPending)
                                                    @if($productStatus === 'exact')
                                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-success shrink-0 z-10"
                                                            title="Confirmado en catálogo">
                                                            <i data-lucide="check-circle-2" class="w-4 h-4" wire:ignore></i>
                                                        </div>
                                                    @elseif($productStatus === 'new')
                                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-warning shrink-0 z-10"
                                                            title="Se creará como nuevo producto">
                                                            <i data-lucide="plus-circle" class="w-4 h-4" wire:ignore></i>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Categoría --}}
                                        <td class="pb-4">
                                            @php $hasCatConflict = isset($item['conflict']['category']); @endphp
                                            <x-conflict-popover type="category-conflict" :item="$item" :index="$i" triggerRight="right-8">
                                                <x-custom-select wire:model.live="items.{{ $i }}.category_id"
                                                    :options="$categories->pluck('name', 'id')->toArray()"
                                                    placeholder="Sin categoría"
                                                    textClass="{{ $hasCatConflict ? 'pr-6' : '' }}" />
                                            </x-conflict-popover>
                                        </td>

                                        {{-- Cantidad --}}
                                        <td class="pb-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number"
                                                step="0.01"
                                                class="input input-inline text-center tabular-nums text-small"
                                                placeholder="0">
                                        </td>

                                        {{-- Unidad --}}
                                        <td class="pb-4">
                                            @php $hasUnitConflict = isset($item['conflict']['unit']); @endphp
                                            <x-conflict-popover type="unit-conflict" :item="$item" :index="$i">
                                                <x-custom-combobox wire:model.live.debounce.400ms="items.{{ $i }}.unit"
                                                    :options="$measures->mapWithKeys(fn($m) => [($m->abbreviation ?: $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')])->toArray()"
                                                    placeholder="Unidad"
                                                    inputClass="{{ $hasUnitConflict ? 'pr-8' : '' }}">
                                                </x-custom-combobox>
                                            </x-conflict-popover>
                                        </td>

                                        {{-- Precio Unitario --}}
                                        <td class="pb-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number"
                                                step="0.01"
                                                class="input input-inline text-right tabular-nums text-small"
                                                placeholder="0.00">
                                            @if(($item['discount_percent'] ?? 0) > 0)
                                                <div class="mt-0.5 flex items-center justify-end gap-1.5 text-xs-fluid">
                                                    <span class="text-text-muted line-through tabular-nums">
                                                        ${{ number_format($item['unit_price_original'] ?? 0, 2, '.', ',') }}
                                                    </span>
                                                    <span class="text-success font-medium">
                                                        -{{ rtrim(rtrim(number_format($item['discount_percent'], 2, '.', ''), '0'), '.') }}%
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Subtotal --}}
                                        <td
                                            class="text-right font-medium text-text-primary tabular-nums text-small align-middle pb-4">
                                            ${{ number_format($itemSubtotal, 2, '.', ',') }}
                                        </td>

                                        {{-- Total con IVA --}}
                                        <td
                                            class="text-right font-semibold text-text-primary tabular-nums text-small align-middle pb-4">
                                            ${{ number_format($itemTotal, 2, '.', ',') }}
                                        </td>

                                        {{-- Delete --}}
                                        <td class="text-center pb-4">
                                            <x-button type="button" wire:click="removeItem({{ $i }})" variant="icon-danger" icon="trash-2" class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totales externos alineados a la derecha --}}
                    @php
                        $subtotalSinIva = $this->subtotalSinIva();
                        $totalConIva = $this->totalConIva();
                        $totalIva = $this->totalIva();
                        $totalDescuento = $this->totalDescuento();
                        $hasAnyDiscount = $this->hasAnyDiscount();
                        $subtotalBruto = $this->subtotalBruto();
                    @endphp
                    <div class="flex justify-end mt-6">
                        <x-totals-summary>
                            @if($hasAnyDiscount)
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-text-muted">Subtotal bruto</span>
                                    <span
                                        class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($subtotalBruto, 2, '.', ',') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-success flex items-center gap-1">
                                        <i data-lucide="tag" class="w-3.5 h-3.5" wire:ignore></i>
                                        Descuento total
                                    </span>
                                    <span
                                        class="text-small font-semibold text-success tabular-nums">-${{ number_format($totalDescuento, 2, '.', ',') }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-6">
                                <span class="text-small text-text-muted">Subtotal s/IVA</span>
                                <span
                                    class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($subtotalSinIva, 2, '.', ',') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-6">
                                <span class="text-small text-text-muted">IVA (16%)</span>
                                <span class="text-small font-medium text-text-muted tabular-nums">
                                    @if($totalIva > 0) ${{ number_format($totalIva, 2, '.', ',') }}
                                    @else <span class="text-warning">Pendiente</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                                <span class="text-body font-semibold text-text-primary">Total c/IVA</span>
                                <span
                                    class="text-h3 font-bold text-text-primary tabular-nums">${{ number_format($totalConIva, 2, '.', ',') }}</span>
                            </div>
                        </x-totals-summary>
                    </div>
                @else
                    <x-empty-state icon="package-open" title="Sin productos detectados"
                        message="Agrégalos manualmente con el botón Agregar."
                        class="border border-dashed border-border rounded-xl py-10" />
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex justify-end pt-6">
                <x-button type="submit" variant="primary" target="saveRequisition">Guardar Requisición</x-button>
            </div>
        </form>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>
