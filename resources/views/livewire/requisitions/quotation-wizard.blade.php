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
            <a href="{{ route('requisiciones.index') }}" class="btn-secondary" wire:navigate>
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Volver
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8" wire:ignore.self>
        @foreach([1 => 'Subir archivo', 2 => 'Procesando', 3 => 'Revisar y guardar'] as $num => $label)
            <div wire:key="step-indicator-{{ $num }}" class="flex items-center gap-2 {{ $num < 3 ? 'flex-1' : '' }}">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-small font-semibold transition-all duration-300 border
                                            {{ $step > $num ? 'bg-emerald-600 text-white border-emerald-600' : ($step === $num ? 'bg-primary-600 text-white border-primary-600 shadow-sm' : 'bg-surface-card text-text-muted border-border') }}">
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
                        class="flex-1 h-0.5 mx-4 rounded-full {{ $step > $num ? 'bg-emerald-600' : 'bg-border' }} transition-all duration-500">
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
                    <button wire:key="process-btn" x-data="{ visible: true }" x-show="visible"
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
                            <span class="spinner spinner-sm"></span>
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
        <div class="card max-w-lg mx-auto" @if($processingStatus === 'processing' || $processingStatus === 'pending')
        wire:poll.2s="checkProcessingStatus" @endif x-data
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
                    <div class="flex flex-col items-center max-w-lg mx-auto text-center py-4">
                        {{-- Ícono premium --}}
                        <div
                            class="w-12 h-12 rounded-xl bg-danger-light text-danger flex items-center justify-center mb-4 shrink-0">
                            <i data-lucide="alert-triangle" class="w-6 h-6" wire:ignore></i>
                        </div>

                        {{-- Título y Mensaje --}}
                        <h2 class="text-h2 font-semibold text-text-primary mb-2">Error al procesar el archivo</h2>
                        <p class="text-small text-text-muted mb-6">No hemos podido estructurar los datos del documento
                            automáticamente.</p>

                        <div class="w-full bg-surface-hover border border-border p-4 rounded-xl mb-6 text-left">
                            <p class="text-xs-fluid font-medium text-text-secondary leading-relaxed">
                                {{ $errorMessage ?? 'Ocurrió un error inesperado durante el procesamiento.' }}
                            </p>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="flex flex-col sm:flex-row w-full gap-2.5 justify-center">
                            <button wire:click="retryProcessing" class="btn-primary group text-small">
                                <i data-lucide="refresh-cw"
                                    class="w-4 h-4 transition-transform group-hover:rotate-180 duration-500"></i>
                                Reintentar
                            </button>
                            <button wire:click="continueManually" class="btn-secondary text-small">
                                <i data-lucide="edit-3" class="w-4 h-4 text-text-muted"></i>
                                Llenar manualmente
                            </button>
                            <button wire:click="resetWizard" class="btn-secondary text-small">
                                <i data-lucide="file-up" class="w-4 h-4 text-text-muted"></i>
                                Cambiar archivo
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══════ PASO 3: FORMULARIO EDITABLE ═══════ --}}
    @if($step === 3)
        <form wire:submit="saveRequisition" x-data
            x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })">



            {{-- General Info --}}
            <div class="card mb-6">
                <div class="flex items-center justify-between mb-4">
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
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-amber-500 pointer-events-none"
                                    title="Se creará como nuevo proveedor" wire:ignore></i>
                            @elseif(($supplierMatch['status'] ?? '') === 'fuzzy')
                                <i data-lucide="sparkles"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                    title="Similitud {{ round(($supplierMatch['confidence'] ?? 0) * 100) }}%" wire:ignore></i>
                            @elseif(($supplierMatch['status'] ?? '') === 'exact')
                                <i data-lucide="check-circle-2"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald-500 pointer-events-none"
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
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-amber-500 pointer-events-none"
                                    title="Se creará como nuevo vendedor" wire:ignore></i>
                            @elseif(($vendorMatch['status'] ?? '') === 'fuzzy')
                                <i data-lucide="sparkles"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                    title="Similitud {{ round(($vendorMatch['confidence'] ?? 0) * 100) }}%" wire:ignore></i>
                            @elseif(($vendorMatch['status'] ?? '') === 'exact')
                                <i data-lucide="check-circle-2"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald-500 pointer-events-none"
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
                            <span class="badge badge-secondary">{{ count($items) }}
                                {{ count($items) === 1 ? 'producto' : 'productos' }}</span>
                        @endif
                    </div>
                    <button type="button" wire:click="addItem" class="btn-secondary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Agregar
                    </button>
                </div>

                @error('items') <p class="mb-3 text-xs-fluid text-danger">{{ $message }}</p> @enderror


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
                                        $productBorder = match (true) {
                                            $productStatus === 'exact' => 'border-emerald-500/30 bg-emerald-50/5',
                                            $productStatus === 'fuzzy' => 'border-primary-500/30 bg-primary-50/5',
                                            $productStatus === 'new' => 'border-amber-500/30 bg-amber-50/5',
                                            default => '',
                                        };
                                    @endphp
                                    <tr class="align-top hover:bg-surface-hover/30 transition-all duration-200 group"
                                        wire:key="item-row-{{ $i }}">
                                        {{-- Nombre / Producto --}}
                                        <td class="pb-4">
                                            <div class="relative" x-data="{ open: false }">
                                                @php
                                                    $isFuzzyPending = isset($item['product_confirmed']) && !$item['product_confirmed'] && ($item['_match']['product']['status'] ?? '') === 'fuzzy';
                                                    $hasProductIndicator = $isFuzzyPending || ($item['_match']['product']['status'] ?? '') === 'exact' || ($item['_match']['product']['status'] ?? '') === 'new';
                                                @endphp
                                                <div class="relative w-full">
                                                    <input wire:model.live.debounce.600ms="items.{{ $i }}.name" type="text"
                                                        class="input {{ $productBorder }} text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white w-full {{ $hasProductIndicator ? 'pr-8' : '' }}"
                                                        placeholder="Nombre del producto">
                                                    @if($isFuzzyPending)
                                                        <button type="button" @click="open = !open"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-lg hover:bg-primary-100/50 text-primary-600 transition shrink-0 animate-pulse z-10"
                                                            title="Coincidencia difusa detectada">
                                                            <i data-lucide="sparkles" class="w-4 h-4" wire:ignore></i>
                                                        </button>
                                                    @elseif(($item['_match']['product']['status'] ?? '') === 'exact')
                                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-emerald-600 shrink-0 z-10"
                                                            title="Confirmado en catálogo">
                                                            <i data-lucide="check-circle-2" class="w-4 h-4" wire:ignore></i>
                                                        </div>
                                                    @elseif(($item['_match']['product']['status'] ?? '') === 'new')
                                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-amber-600 shrink-0 z-10"
                                                            title="Se creará como nuevo">
                                                            <i data-lucide="plus-circle" class="w-4 h-4" wire:ignore></i>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($isFuzzyPending)
                                                    {{-- Popover flotante absoluto de confirmación fuzzy --}}
                                                    <div x-show="open" @click.outside="open = false" x-cloak
                                                        class="absolute z-[95] left-0 top-[38px] mt-1 w-72 p-3.5 rounded-xl border border-border bg-surface-card shadow-lg animate-scale-in text-xs"
                                                        x-transition>
                                                        <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-border">
                                                            <i data-lucide="sparkles" class="w-4 h-4 text-primary-600 shrink-0 mt-0.5"
                                                                wire:ignore></i>
                                                            <div>
                                                                <p class="font-semibold text-text-primary">Coincidencia detectada</p>
                                                                <p class="text-[10px] text-text-muted">¿El producto corresponde a la
                                                                    base de datos?</p>
                                                            </div>
                                                        </div>
                                                        <div class="space-y-1.5 mb-3 text-[11px]">
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">En cotización:</span>
                                                                <span
                                                                    class="font-medium text-text-primary text-right">{{ $item['name'] }}</span>
                                                            </div>
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">En catálogo:</span>
                                                                <span
                                                                    class="font-semibold text-text-primary text-right">"{{ $item['_match']['product']['catalog_name'] }}"</span>
                                                            </div>
                                                            <div
                                                                class="flex justify-between gap-2 pt-1 border-t border-border text-[10px]">
                                                                <span class="text-text-muted">Nivel de confianza:</span>
                                                                <span
                                                                    class="font-semibold text-primary-600">{{ round(($item['_match']['product']['confidence'] ?? 0) * 100) }}%
                                                                    de similitud</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col gap-1">
                                                            <button type="button" wire:click="confirmProductAssociation({{ $i }})"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-primary-600 text-white text-[10px] font-semibold hover:bg-primary-700 transition">
                                                                Confirmar y vincular (✓)
                                                            </button>
                                                            <button type="button" wire:click="rejectProductAssociation({{ $i }})"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-surface-main border border-border text-text-primary text-[10px] font-semibold hover:bg-surface-hover transition">
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
                                                <div class="relative w-full">
                                                    <x-custom-select wire:model.live="items.{{ $i }}.category_id"
                                                        :options="$categories->pluck('name', 'id')->toArray()"
                                                        placeholder="Sin categoría"
                                                        textClass="{{ $hasCatConflict ? 'pr-6' : '' }}" />
                                                    @if($hasCatConflict)
                                                        <button type="button" @click="open = !open"
                                                            class="absolute right-8 top-1/2 -translate-y-1/2 p-1 rounded-lg hover:bg-amber-100/50 text-amber-600 transition shrink-0 animate-pulse z-10"
                                                            title="Discrepancia en categoría">
                                                            <i data-lucide="alert-triangle" class="w-4 h-4" wire:ignore></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($hasCatConflict)
                                                    <div x-show="open" @click.outside="open = false" x-cloak
                                                        class="absolute z-[90] right-0 mt-1 w-64 p-3.5 rounded-xl border border-border bg-surface-card shadow-lg animate-scale-in text-xs"
                                                        x-transition>
                                                        <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-border">
                                                            <i data-lucide="help-circle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"
                                                                wire:ignore></i>
                                                            <div>
                                                                <p class="font-semibold text-text-primary">¿Categoría diferente?</p>
                                                                <p class="text-[10px] text-text-muted">La IA sugirió una categoría
                                                                    diferente.</p>
                                                            </div>
                                                        </div>
                                                        <div class="space-y-1.5 mb-3 text-[11px]">
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">Registrada:</span>
                                                                <span
                                                                    class="font-medium text-text-primary text-right">{{ $item['conflict']['category']['registered'] }}</span>
                                                            </div>
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">Propuesta IA:</span>
                                                                <span
                                                                    class="font-bold text-amber-600 text-right">{{ $item['conflict']['category']['suggested'] }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col gap-1">
                                                            <button type="button"
                                                                wire:click="resolveProductConflict({{ $i }}, 'category')"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-amber-600 text-white text-[10px] font-semibold hover:bg-amber-700 transition">
                                                                Actualizar catálogo maestro
                                                            </button>
                                                            <button type="button" wire:click="dismissProductConflict({{ $i }})"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-surface-main border border-border text-text-primary text-[10px] font-semibold hover:bg-surface-hover transition">
                                                                Conservar catálogo
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Cantidad --}}
                                        <td class="pb-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number"
                                                step="0.01"
                                                class="input text-center tabular-nums text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white"
                                                placeholder="0">
                                        </td>

                                        {{-- Unidad --}}
                                        <td class="pb-4">
                                            <div class="relative" x-data="{ open: false }">
                                                @php
                                                    $hasUnitConflict = isset($item['conflict']['unit']);
                                                @endphp
                                                <div class="relative w-full">
                                                    <x-custom-combobox wire:model.live.debounce.400ms="items.{{ $i }}.unit"
                                                        :options="$measures->mapWithKeys(fn($m) => [($m->abbreviation ?: $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')])->toArray()" placeholder="Unidad"
                                                        inputClass="{{ $hasUnitConflict ? 'pr-8' : '' }}">
                                                    </x-custom-combobox>
                                                    @if($hasUnitConflict)
                                                        <button type="button" @click="open = !open"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-lg hover:bg-amber-100/50 text-amber-600 transition shrink-0 animate-pulse z-10"
                                                            title="Discrepancia en unidad">
                                                            <i data-lucide="alert-triangle" class="w-4 h-4" wire:ignore></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($hasUnitConflict)
                                                    <div x-show="open" @click.outside="open = false" x-cloak
                                                        class="absolute z-[90] right-0 mt-1 w-64 p-3.5 rounded-xl border border-border bg-surface-card shadow-lg animate-scale-in text-xs"
                                                        x-transition>
                                                        <div class="flex items-start gap-2 mb-2 pb-1.5 border-b border-border">
                                                            <i data-lucide="help-circle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"
                                                                wire:ignore></i>
                                                            <div>
                                                                <p class="font-semibold text-text-primary">¿Unidad diferente?</p>
                                                                <p class="text-[10px] text-text-muted">La IA sugirió una unidad de
                                                                    medida diferente.</p>
                                                            </div>
                                                        </div>
                                                        <div class="space-y-1.5 mb-3 text-[11px]">
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">Registrada:</span>
                                                                <span
                                                                    class="font-medium text-text-primary text-right">{{ $item['conflict']['unit']['registered'] }}</span>
                                                            </div>
                                                            <div class="flex justify-between gap-2">
                                                                <span class="text-text-muted">Propuesta IA:</span>
                                                                <span
                                                                    class="font-bold text-amber-600 text-right">{{ $item['conflict']['unit']['suggested'] }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col gap-1">
                                                            <button type="button" wire:click="resolveProductConflict({{ $i }}, 'unit')"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-amber-600 text-white text-[10px] font-semibold hover:bg-amber-700 transition">
                                                                Actualizar catálogo maestro
                                                            </button>
                                                            <button type="button" wire:click="dismissProductConflict({{ $i }})"
                                                                @click="open = false"
                                                                class="w-full py-1.5 rounded bg-surface-main border border-border text-text-primary text-[10px] font-semibold hover:bg-surface-hover transition">
                                                                Conservar catálogo
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Precio Unitario --}}
                                        <td class="pb-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number"
                                                step="0.01"
                                                class="input text-right tabular-nums text-small border-transparent bg-transparent hover:border-border focus:border-primary-500 focus:bg-white"
                                                placeholder="0.00">
                                            @if(($item['discount_percent'] ?? 0) > 0)
                                                <div class="mt-0.5 flex items-center justify-end gap-1.5 text-xs-fluid">
                                                    <span class="text-text-muted line-through tabular-nums">
                                                        ${{ number_format($item['unit_price_original'] ?? 0, 2, '.', ',') }}
                                                    </span>
                                                    <span class="text-emerald-600 font-medium">
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

                    {{-- Totales externos alineados a la derecha --}}
                    @php
                        $subtotalSinIva = collect($items)->sum(fn($item) => $item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)));
                        $totalConIva = collect($items)->sum(fn($item) => $item['line_total'] ?? (($item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0))) + ($item['tax_amount'] ?? 0)));
                        $totalIva = $totalConIva - $subtotalSinIva;
                        $totalDescuento = collect($items)->sum(function ($item) {
                            $original = (float) ($item['unit_price_original'] ?? 0);
                            $net = (float) ($item['unit_price'] ?? 0);
                            $qty = (float) ($item['quantity'] ?? 0);
                            if ($original <= 0 || $net <= 0 || $original <= $net) {
                                return 0;
                            }
                            return round(($original - $net) * $qty, 2);
                        });
                        $hasAnyDiscount = $totalDescuento > 0;
                        $subtotalBruto = $hasAnyDiscount ? ($subtotalSinIva + $totalDescuento) : 0;
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
                                    <span class="text-small text-emerald-600 flex items-center gap-1">
                                        <i data-lucide="tag" class="w-3.5 h-3.5" wire:ignore></i>
                                        Descuento total
                                    </span>
                                    <span
                                        class="text-small font-semibold text-emerald-600 tabular-nums">-${{ number_format($totalDescuento, 2, '.', ',') }}</span>
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
                                    @else <span class="text-amber-500">Pendiente</span>
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
                <x-submit-button target="saveRequisition">Guardar Requisición</x-submit-button>
            </div>
        </form>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>