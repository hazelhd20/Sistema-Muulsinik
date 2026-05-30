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
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('requisiciones.index') }}" class="p-2 rounded-xl hover:bg-surface-hover transition"
                title="Volver">
                <i data-lucide="arrow-left" class="w-5 h-5 text-text-muted"></i>
            </a>
            <div>
                <h1 class="text-h1 text-text-primary">Subir Cotización</h1>
                <p class="text-body text-text-muted">Carga un archivo y el sistema extraerá la información
                    automáticamente
                </p>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-2 mt-6">
            @foreach([1 => 'Subir archivo', 2 => 'Procesando', 3 => 'Revisar y guardar'] as $num => $label)
                <div class="flex items-center gap-2 {{ $num < 3 ? 'flex-1' : '' }}">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-small font-bold transition-all duration-300
                                        {{ $step > $num ? 'bg-green-500 text-white' : ($step === $num ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-gray-200 text-text-muted') }}">
                            @if($step > $num)
                                <i data-lucide="check" class="w-4 h-4"></i>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span
                            class="text-body font-medium {{ $step >= $num ? 'text-text-primary' : 'text-text-muted' }}">{{ $label }}</span>
                    </div>
                    @if($num < 3)
                        <div
                            class="flex-1 h-0.5 mx-2 rounded {{ $step > $num ? 'bg-green-500' : 'bg-gray-200' }} transition-all duration-500">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════ PASO 1: UPLOAD ═══════ --}}
    @if($step === 1)
        <div class="card max-w-2xl mx-auto">
            <div class="p-8">
                <x-file-input
                    wire:model="file"
                    variant="dropzone"
                    accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls"
                    maxSize="20 MB"
                    :formats="['PDF', 'XLSX', 'JPG', 'PNG']"
                    title="Arrastra tu cotización aquí"
                    subtitle="o haz clic para seleccionar un archivo"
                    inputId="file-upload-input"
                />

                {{-- Process Button --}}
                @if($file && !$errors->has('file'))
                    <button wire:key="process-btn"
                        x-data="{ visible: true }"
                        x-show="visible"
                        @file-removed.window="visible = false"
                        x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })" type="button"
                        wire:click="processUpload" wire:loading.attr="disabled" wire:target="processUpload"
                        class="btn-primary relative w-full mt-6 py-3 text-body">
                        <span wire:loading.class="opacity-0" wire:target="processUpload"
                            class="flex items-center justify-center gap-2 transition-opacity">
                            <i data-lucide="scan-line" class="w-5 h-5" wire:ignore></i>
                            Procesar Cotización
                        </span>
                        <span wire:loading wire:target="processUpload"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                @endif

                {{-- Botón de Simulación para Pruebas y Verificaciones --}}
                <div class="mt-6 pt-6 border-t border-border flex flex-col items-center gap-2">
                    <p class="text-xs-fluid text-text-muted">¿Deseas verificar la interfaz sin procesar un archivo?</p>
                    <button type="button" wire:click="loadMockDataForTesting"
                        class="px-4 py-2 rounded-xl bg-surface-hover hover:bg-surface-hover/80 text-text-primary text-xs-fluid font-medium flex items-center gap-1.5 transition">
                        <i data-lucide="beaker" class="w-4 h-4 text-primary-600"></i>
                        Simular datos de prueba (Paso 3)
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════ PASO 2: PROCESAMIENTO ═══════ --}}
    @if($step === 2)
        <div class="card max-w-lg mx-auto" wire:poll.2s="checkProcessingStatus" x-data
            x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })">
            <div class="p-8 text-center">
                @if($processingStatus === 'processing' || $processingStatus === 'pending')
                    {{-- Animación de procesamiento --}}
                    <div class="mb-6">
                        <div class="w-20 h-20 mx-auto rounded-full bg-primary-50 flex items-center justify-center mb-4">
                            <svg class="animate-spin h-10 w-10 text-primary-600" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </div>
                        <h2 class="text-h1 text-text-primary mb-2">Procesando tu cotización</h2>
                        <p class="text-body text-text-muted">
                            Estamos extrayendo la información del documento mediante Inteligencia Artificial.<br>
                            Esto puede tomar hasta 30 segundos...
                        </p>
                    </div>

                    <p class="text-xs-fluid text-text-muted mt-4 flex items-center justify-center gap-1.5">
                        <i data-lucide="info" class="w-3 h-3 shrink-0"></i>
                        No cierres esta página, el resultado aparecerá automáticamente.
                    </p>

                @elseif($processingStatus === 'failed')
                    {{-- Error state --}}
                    <div class="mb-6">
                        <div class="w-20 h-20 mx-auto rounded-full bg-red-50 flex items-center justify-center mb-4">
                            <i data-lucide="alert-triangle" class="w-10 h-10 text-red-500"></i>
                        </div>
                        <h2 class="text-h1 text-text-primary mb-2">Error al procesar</h2>
                        <p class="text-body text-red-600 bg-red-50 p-3 rounded-xl">
                            {{ $errorMessage ?? 'Ocurrió un error inesperado.' }}
                        </p>
                    </div>

                    <div class="flex items-center justify-center gap-3">
                        <button wire:click="retryProcessing" class="btn-primary">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            Reintentar
                        </button>
                        <button wire:click="resetWizard" class="btn-secondary">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Subir otro archivo
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══════ PASO 3: FORMULARIO EDITABLE ═══════ --}}
    @if($step === 3)
        <form wire:submit="saveRequisition" x-data
            x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })">

            {{-- RF-REQ-06: Alertas tipificadas (3 niveles) --}}
            @if(!empty($alerts['errors']))
                <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-start gap-3">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="text-small font-semibold text-red-800 mb-1">Campos obligatorios</h3>
                            <ul class="text-body text-red-700 space-y-0.5">
                                @foreach($alerts['errors'] as $error)
                                    <li class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 bg-red-500 rounded-full shrink-0"></span>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($alerts['warnings']))
                <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="text-small font-semibold text-amber-800 mb-1">Campos pendientes de revisar</h3>
                            <ul class="text-body text-amber-700 space-y-0.5">
                                @foreach($alerts['warnings'] as $warning)
                                    <li class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 bg-amber-500 rounded-full shrink-0"></span>
                                        {{ $warning }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($alerts['info']))
                <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="text-small font-semibold text-blue-800 mb-1">Resumen de detección</h3>
                            <ul class="text-body text-blue-700 space-y-0.5">
                                @foreach($alerts['info'] as $infoMsg)
                                    <li class="flex items-center gap-1.5">
                                        <span class="w-1 h-1 bg-blue-500 rounded-full shrink-0"></span>
                                        {{ $infoMsg }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- General Info --}}
            <div class="card mb-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-h2 text-text-primary">Información General</h2>
                    @php
                        $quotation = $quotationId ? \App\Models\Quotation::find($quotationId) : null;
                    @endphp
                    @if($quotation)
                        <button type="button"
                            @click="openServerPreview('{{ route('file.preview', ['path' => $quotation->file_path]) }}', '{{ str_ends_with(strtolower($quotation->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg' }}')"
                            class="btn-secondary">
                            <i data-lucide="file-search" class="w-4 h-4"></i>
                            Ver documento
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="label">Proyecto *</label>
                        <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
                            placeholder="Seleccionar proyecto..." />
                        @error('projectId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Proveedor</label>
                        <input type="text" wire:model.live="supplierName" list="suppliers-list" class="input"
                            placeholder="Nombre del proveedor...">
                        <datalist id="suppliers-list">
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->trade_name }}"></option>
                            @endforeach
                        </datalist>
                        @if(($supplierMatch['status'] ?? '') === 'new')
                            <p class="mt-1 text-xs-fluid text-amber-600">Se creará como nuevo proveedor</p>
                        @elseif(($supplierMatch['status'] ?? '') === 'fuzzy')
                            <p class="mt-1 text-xs-fluid text-primary-600">Similitud {{ round(($supplierMatch['confidence'] ?? 0) * 100) }}%</p>
                        @elseif(($supplierMatch['status'] ?? '') === 'exact')
                            <p class="mt-1 text-xs-fluid text-emerald-600">Proveedor existente</p>
                        @endif
                    </div>

                    <div>
                        <label class="label">Tienda / Sucursal</label>
                        <input wire:model="storeName" type="text" class="input" placeholder="Sucursal (opcional)">
                    </div>

                    <div>
                        <label class="label">Fecha *</label>
                        <input wire:model="date" type="date" class="input">
                        @error('date') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Vendedor</label>
                        <input wire:model.live="vendorName" type="text" list="vendors-list" class="input"
                            placeholder="Nombre del vendedor...">
                        <datalist id="vendors-list">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->name }}"></option>
                            @endforeach
                        </datalist>
                        @if(($vendorMatch['status'] ?? '') === 'new')
                            <p class="mt-1 text-xs-fluid text-amber-600">Se creará como nuevo vendedor</p>
                        @elseif(($vendorMatch['status'] ?? '') === 'fuzzy')
                            <p class="mt-1 text-xs-fluid text-primary-600">Similitud {{ round(($vendorMatch['confidence'] ?? 0) * 100) }}%</p>
                        @elseif(($vendorMatch['status'] ?? '') === 'exact')
                            <p class="mt-1 text-xs-fluid text-emerald-600">Vendedor existente</p>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="label">Anotaciones</label>
                    <textarea wire:model="annotations" class="input" rows="2"
                        placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                    @error('annotations') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- IVA Toggle — visible cuando la IA no pudo detectar --}}
            @if($quotationIncludesTax === null && count($items) > 0)
                <div class="mb-6 p-4 rounded-xl border border-amber-200 bg-amber-50 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="receipt" class="w-4 h-4 text-amber-700" wire:ignore></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-small font-semibold text-amber-900 mb-0.5">¿Los precios incluyen IVA?</p>
                        <p class="text-xs-fluid text-amber-700 mb-3">No se detectó información de IVA. Indica si los precios ya incluyen el 16%.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="setTaxInclusion(false)" class="btn-secondary">
                                Sin IVA incluido
                            </button>
                            <button type="button" wire:click="setTaxInclusion(true)" class="btn-secondary">
                                IVA ya incluido
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($quotationIncludesTax !== null && count($items) > 0)
                <div class="mb-6 px-4 py-3 rounded-xl border border-border bg-surface-card flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="receipt" class="w-4 h-4 text-emerald-600" wire:ignore></i>
                        <span class="text-small font-medium text-text-primary">IVA:</span>
                        @if($quotationIncludesTax)
                            <span class="badge badge-primary">Con IVA incluido — se desglosa automáticamente</span>
                        @else
                            <span class="badge badge-success">Sin IVA — se calcula al 16%</span>
                        @endif
                        @if($taxDetectedByAI)
                            <span class="text-xs-fluid text-text-muted">· detectado por IA</span>
                        @endif
                    </div>
                    <button type="button" wire:click="$set('quotationIncludesTax', null)"
                        class="text-xs-fluid text-text-muted hover:text-primary-600 transition">
                        Cambiar
                    </button>
                </div>
            @endif

            {{-- Products Table --}}
            <div class="card mb-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <h2 class="text-h2 text-text-primary">Productos</h2>
                        <span class="badge badge-secondary">{{ count($items) }} {{ count($items) === 1 ? 'producto' : 'productos' }}</span>
                    </div>
                    <button type="button" wire:click="addItem" class="btn-secondary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Agregar
                    </button>
                </div>

                @error('items') <p class="mb-3 text-xs-fluid text-danger">{{ $message }}</p> @enderror

                <datalist id="measures-list">
                    @foreach($measures as $measure)
                        <option value="{{ $measure->name }}">{{ $measure->abbreviation ? '(' . $measure->abbreviation . ')' : '' }}</option>
                    @endforeach
                </datalist>
                <datalist id="categories-list">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->name }}"></option>
                    @endforeach
                </datalist>

                @if(count($items) > 0)
                     {{-- Tabla de productos --}}
                        <div class="table-embedded md:!overflow-visible">
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
                                            $productBorder = match(true) {
                                                $productStatus === 'exact'  => 'border-emerald-500/30 bg-emerald-50/5',
                                                $productStatus === 'fuzzy'  => 'border-primary-500/30 bg-primary-50/5',
                                                $productStatus === 'new'    => 'border-amber-500/30 bg-amber-50/5',
                                                default                     => '',
                                            };
                                        @endphp
                                        <tr class="align-top hover:bg-surface-hover/30 transition-all duration-200" wire:key="item-row-{{ $i }}">
                                            {{-- Nombre / Producto --}}
                                            <td class="pb-4">
                                                <div class="relative" x-data="{ open: false }">
                                                    @php
                                                        $isFuzzyPending = isset($item['product_confirmed']) && !$item['product_confirmed'] && ($item['_match']['product']['status'] ?? '') === 'fuzzy';
                                                    @endphp
                                                    <div class="flex items-center gap-1.5">
                                                        <div class="flex-1 min-w-0">
                                                            <input wire:model.live.debounce.600ms="items.{{ $i }}.name" type="text"
                                                                class="input {{ $productBorder }} text-small"
                                                                placeholder="Nombre del producto">
                                                        </div>
                                                        @if($isFuzzyPending)
                                                            <button type="button" @click="open = !open" 
                                                                class="p-1 rounded-lg hover:bg-primary-100/50 text-primary-600 transition shrink-0 animate-pulse"
                                                                title="Coincidencia difusa detectada">
                                                                <i data-lucide="sparkles" class="w-4 h-4" wire:ignore></i>
                                                            </button>
                                                        @elseif(($item['_match']['product']['status'] ?? '') === 'exact')
                                                            <div class="p-1 text-emerald-600 shrink-0" title="Confirmado en catálogo">
                                                                <i data-lucide="check-circle-2" class="w-4 h-4" wire:ignore></i>
                                                            </div>
                                                        @elseif(($item['_match']['product']['status'] ?? '') === 'new')
                                                            <div class="p-1 text-amber-600 shrink-0" title="Se creará como nuevo">
                                                                <i data-lucide="plus-circle" class="w-4 h-4" wire:ignore></i>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if($isFuzzyPending)
                                                        {{-- Popover flotante absoluto de confirmación fuzzy --}}
                                                        <div x-show="open" @click.outside="open = false" x-cloak
                                                            class="absolute z-[95] left-0 top-[38px] mt-1 w-72 p-3 rounded-xl border border-primary-200 bg-white shadow-xl animate-scale-in text-xs"
                                                            x-transition>
                                                            <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-gray-100">
                                                                <i data-lucide="sparkles" class="w-4 h-4 text-primary-600 shrink-0 mt-0.5" wire:ignore></i>
                                                                <div>
                                                                    <p class="font-semibold text-gray-900">Coincidencia detectada</p>
                                                                    <p class="text-[10px] text-gray-500">¿El producto corresponde a la base de datos?</p>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-1.5 mb-3 text-[11px]">
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">En cotización:</span>
                                                                    <span class="font-medium text-gray-800 text-right">{{ $item['name'] }}</span>
                                                                </div>
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">En catálogo:</span>
                                                                    <span class="font-bold text-primary-950 text-right">"{{ $item['_match']['product']['catalog_name'] }}"</span>
                                                                </div>
                                                                <div class="flex justify-between gap-2 pt-1 border-t border-gray-50 text-[10px]">
                                                                    <span class="text-gray-400">Nivel de confianza:</span>
                                                                    <span class="font-semibold text-primary-600">{{ round(($item['_match']['product']['confidence'] ?? 0) * 100) }}% de similitud</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-col gap-1">
                                                                <button type="button" wire:click="confirmProductAssociation({{ $i }})" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-primary-600 text-white text-[10px] font-semibold hover:bg-primary-700 transition">
                                                                    Confirmar y vincular (✓)
                                                                </button>
                                                                <button type="button" wire:click="rejectProductAssociation({{ $i }})" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-semibold hover:bg-gray-100 transition">
                                                                    Crear como producto nuevo
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Categoría --}}
                                            <td class="pb-4">
                                                <div class="relative" x-data="{ open: false }">
                                                    @php
                                                        $hasCatConflict = isset($item['conflict']['category']);
                                                    @endphp
                                                    <div class="flex items-center gap-1">
                                                        <div class="flex-1 min-w-0">
                                                            <x-custom-select wire:model.live="items.{{ $i }}.category_id" :options="$categories->pluck('name', 'id')->toArray()" placeholder="Sin categoría" />
                                                        </div>
                                                        @if($hasCatConflict)
                                                            <button type="button" @click="open = !open" 
                                                                class="p-1 rounded-lg hover:bg-amber-100/50 text-amber-600 transition shrink-0 animate-pulse"
                                                                title="Discrepancia en categoría">
                                                                <i data-lucide="alert-triangle" class="w-4 h-4" wire:ignore></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    @if($hasCatConflict)
                                                        <div x-show="open" @click.outside="open = false" x-cloak
                                                            class="absolute z-[90] right-0 mt-1 w-64 p-3 rounded-xl border border-amber-200 bg-white shadow-xl animate-scale-in text-xs"
                                                            x-transition>
                                                            <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-gray-100">
                                                                <i data-lucide="help-circle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" wire:ignore></i>
                                                                <div>
                                                                    <p class="font-semibold text-gray-900">¿Categoría diferente?</p>
                                                                    <p class="text-[10px] text-gray-500">La IA sugirió una categoría diferente.</p>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-1.5 mb-3 text-[11px]">
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">Registrada:</span>
                                                                    <span class="font-medium text-gray-800 text-right">{{ $item['conflict']['category']['registered'] }}</span>
                                                                </div>
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">Propuesta IA:</span>
                                                                    <span class="font-bold text-amber-600 text-right">{{ $item['conflict']['category']['suggested'] }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-col gap-1">
                                                                <button type="button" wire:click="resolveProductConflict({{ $i }}, 'category')" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-amber-600 text-white text-[10px] font-semibold hover:bg-amber-700 transition">
                                                                    Actualizar catálogo maestro
                                                                </button>
                                                                <button type="button" wire:click="dismissProductConflict({{ $i }})" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-semibold hover:bg-gray-100 transition">
                                                                    Conservar catálogo
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Cantidad --}}
                                            <td class="pb-4">
                                                <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number" step="0.01"
                                                    class="input text-center tabular-nums text-small" placeholder="0">
                                            </td>

                                            {{-- Unidad --}}
                                            <td class="pb-4">
                                                <div class="relative" x-data="{ open: false }">
                                                    @php
                                                        $hasUnitConflict = isset($item['conflict']['unit']);
                                                    @endphp
                                                    <div class="flex items-center gap-1">
                                                        <div class="flex-1 min-w-0">
                                                            <input type="text" wire:model.live.debounce.400ms="items.{{ $i }}.unit" list="measures-list"
                                                                class="input text-center text-small {{ $hasUnitConflict ? 'border-amber-300 focus:border-amber-500 focus:ring-amber-200 bg-amber-50/10' : '' }}" 
                                                                placeholder="Unidad">
                                                        </div>
                                                        @if($hasUnitConflict)
                                                            <button type="button" @click="open = !open" 
                                                                class="p-1 rounded-lg hover:bg-amber-100/50 text-amber-600 transition shrink-0 animate-pulse"
                                                                title="Discrepancia en unidad">
                                                                <i data-lucide="alert-triangle" class="w-4 h-4" wire:ignore></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    @if($hasUnitConflict)
                                                        <div x-show="open" @click.outside="open = false" x-cloak
                                                            class="absolute z-[90] right-0 mt-1 w-64 p-3 rounded-xl border border-amber-200 bg-white shadow-xl animate-scale-in text-xs"
                                                            x-transition>
                                                            <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-gray-100">
                                                                <i data-lucide="help-circle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" wire:ignore></i>
                                                                <div>
                                                                    <p class="font-semibold text-gray-900">¿Unidad diferente?</p>
                                                                    <p class="text-[10px] text-gray-500">La IA sugirió una unidad de medida diferente.</p>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-1.5 mb-3 text-[11px]">
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">Registrada:</span>
                                                                    <span class="font-medium text-gray-800 text-right">{{ $item['conflict']['unit']['registered'] }}</span>
                                                                </div>
                                                                <div class="flex justify-between gap-2">
                                                                    <span class="text-gray-500">Propuesta IA:</span>
                                                                    <span class="font-bold text-amber-600 text-right">{{ $item['conflict']['unit']['suggested'] }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-col gap-1">
                                                                <button type="button" wire:click="resolveProductConflict({{ $i }}, 'unit')" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-amber-600 text-white text-[10px] font-semibold hover:bg-amber-700 transition">
                                                                    Actualizar catálogo maestro
                                                                </button>
                                                                <button type="button" wire:click="dismissProductConflict({{ $i }})" @click="open = false"
                                                                    class="w-full py-1.5 rounded bg-gray-50 border border-gray-200 text-gray-700 text-[10px] font-semibold hover:bg-gray-100 transition">
                                                                    Conservar catálogo
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Precio Unitario --}}
                                            <td class="pb-4">
                                                <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number" step="0.01"
                                                    class="input text-right tabular-nums text-small" placeholder="0.00">
                                            </td>

                                            {{-- Subtotal --}}
                                            <td class="text-right font-medium text-text-primary tabular-nums text-small pt-2.5 pb-4">
                                                ${{ number_format($itemSubtotal, 2, '.', ',') }}
                                            </td>

                                            {{-- Total con IVA --}}
                                            <td class="text-right font-semibold text-text-primary tabular-nums text-small pt-2.5 pb-4">
                                                ${{ number_format($itemTotal, 2, '.', ',') }}
                                            </td>

                                            {{-- Delete --}}
                                            <td class="text-center pb-4">
                                                <button type="button" wire:click="removeItem({{ $i }})" class="btn-icon-danger mt-1">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totales externos alineados a la derecha --}}
                        @php
                            $subtotalSinIva = collect($items)->sum(fn($item) => $item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)));
                            $totalConIva = collect($items)->sum(fn($item) => $item['line_total'] ?? (($item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0))) + ($item['tax_amount'] ?? 0)));
                            $totalIva = $totalConIva - $subtotalSinIva;
                        @endphp
                        <div class="flex justify-end mt-3">
                            <div class="min-w-[260px] space-y-1.5">
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-text-muted">Subtotal s/IVA</span>
                                    <span class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($subtotalSinIva, 2, '.', ',') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-text-muted">IVA (16%)</span>
                                    <span class="text-small font-medium text-text-muted tabular-nums">
                                        @if($totalIva > 0) ${{ number_format($totalIva, 2, '.', ',') }}
                                        @else <span class="text-amber-500">Pendiente</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-6 pt-1.5 border-t border-border">
                                    <span class="text-small font-semibold text-text-primary">Total c/IVA</span>
                                    <span class="text-body font-bold text-text-primary tabular-nums">${{ number_format($totalConIva, 2, '.', ',') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-10 border-2 border-dashed border-border rounded-xl">
                            <div class="w-10 h-10 rounded-xl bg-surface-hover flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="package-open" class="w-5 h-5 text-text-muted"></i>
                            </div>
                            <p class="text-small font-medium text-text-primary mb-0.5">Sin productos detectados</p>
                            <p class="text-xs-fluid text-text-muted">Agrégalos manualmente con el botón Agregar.</p>
                        </div>
                    @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-2">
                <button type="button" wire:click="resetWizard" class="btn-secondary">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Subir otro archivo
                </button>

                <div class="flex items-center gap-3">
                    <a href="{{ route('requisiciones.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary relative" wire:loading.attr="disabled"
                        wire:target="saveRequisition">
                        <span wire:loading.class="opacity-0" wire:target="saveRequisition"
                            class="flex items-center gap-2 transition-opacity">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Guardar Requisición
                        </span>
                        <span wire:loading wire:target="saveRequisition"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <div x-show="showPreviewModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="showPreviewModal = false">
        <div class="absolute inset-0 bg-black/60" @click="showPreviewModal = false"></div>
        <div class="modal-card w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden animate-scale-in"
            x-transition>
            <div class="px-4 py-3 border-b border-border flex items-center justify-between">
                <h3 class="text-h2 text-text-primary flex items-center gap-2">
                    <i data-lucide="file-search" class="w-5 h-5 text-primary-600"></i> Vista Previa del Documento
                </h3>
                <button @click="showPreviewModal = false"
                    class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden bg-surface-main p-4 relative">
                <template x-if="isImage()">
                    <img :src="previewUrl" class="w-full h-full object-contain rounded-lg">
                </template>
                <template x-if="isPdf()">
                    <iframe :src="previewUrl"
                        class="w-full h-full border border-border rounded-lg bg-surface-card"></iframe>
                </template>
                <template x-if="!isImage() && !isPdf()">
                    <div class="flex flex-col items-center justify-center h-full text-text-muted gap-3">
                        <i data-lucide="file-question" class="w-12 h-12 opacity-50"></i>
                        <p class="font-medium text-body">Vista previa no disponible para este tipo de archivo.</p>
                        <a :href="previewUrl" target="_blank" class="btn-secondary text-small mt-2">
                            <i data-lucide="download" class="w-4 h-4"></i> Descargar
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>