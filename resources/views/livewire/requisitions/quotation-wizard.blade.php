<div x-data="{}">
    {{-- ═══════ WIZARD HEADER ═══════ --}}
    @php
        $breadcrumbs = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Requisiciones', 'url' => route('requisiciones.index')],
            ['label' => 'Subir Cotización']
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" title="Subir Cotización" />

    {{-- Sequence-style Step Indicator --}}
    <div class="flex justify-center mb-10 mt-4" wire:ignore.self>
        <div class="flex items-center w-full max-w-2xl relative">
            {{-- Background track line --}}
            <div class="absolute bottom-[11px] left-[16.66%] right-[16.66%] h-[2px] bg-border/40 z-0 rounded-full">
            </div>

            {{-- Active track line (dynamic width based on step) --}}
            <div class="absolute bottom-[11px] left-[16.66%] h-[2px] bg-primary-600 z-0 rounded-full transition-all duration-700 ease-in-out"
                style="width: {{ ($step - 1) * 33.33 }}%;"></div>

            @foreach([1 => 'Subir archivo', 2 => 'Procesando', 3 => 'Revisar y guardar'] as $num => $label)
                    <div wire:key="step-indicator-{{ $num }}" class="relative z-10 flex flex-col items-center flex-1">
                        {{-- Label (Top) --}}
                        <span
                            class="text-xs font-semibold tracking-wider uppercase mb-3 transition-colors duration-300 {{ $step === $num ? 'text-primary-600' : ($step > $num ? 'text-text-primary' : 'text-text-muted') }}">
                            {{ $label }}
                        </span>

                        {{-- Dot Container (Center) - acts as a mask over the lines --}}
                        <div class="w-6 h-6 rounded-full bg-surface-main flex items-center justify-center">
                            {{-- Dot / Icon --}}
                            <div class="transition-all duration-500 ease-out flex items-center justify-center 
                                                                           {{ $step > $num ? 'w-5 h-5 rounded-full bg-primary-600 text-white shadow-sm' :
                ($step === $num ? 'w-3.5 h-3.5 rounded-full bg-primary-600 ring-4 ring-primary-50' :
                    'w-2 h-2 rounded-full bg-border/60') }}">
                                @if($step > $num)
                                    <x-lucide-check class="w-3 h-3" stroke-width="3" wire:ignore />
                                @endif
                            </div>
                        </div>
                    </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════ PASO 1: UPLOAD ═══════ --}}
    @if($step === 1)
        <div class="max-w-2xl mx-auto">
            <x-card class="p-8 transition-all duration-300">
                <x-file-input wire:model="uploadQueue" :multiple="true" variant="dropzone"
                    accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls" maxSize="20 MB" :formats="['PDF', 'XLSX', 'JPG', 'PNG']"
                    title="Arrastra tus cotizaciones aquí" subtitle="o haz clic para seleccionar archivos"
                    inputId="file-upload-input" />

                {{-- Process Button --}}
                @if(!empty($files) && !$errors->has('files'))
                    <div class="mt-8 animate-fade-in-up">
                        <x-button wire:key="process-btn" x-data="{ visible: true }" x-show="visible"
                            @file-removed.window="visible = false" type="button" wire:click="processUpload"
                            wire:loading.attr="disabled" target="processUpload" variant="primary"
                            class="w-full text-small font-medium tracking-wide py-2.5 rounded-xl shadow-sm" icon="scan-line">
                            Procesar Documentos
                        </x-button>
                    </div>
                @endif
                @error('files') <span class="text-xs text-danger mt-2 block">{{ $message }}</span> @enderror
            </x-card>
        </div>
    @endif

    {{-- ═══════ PASO 2: PROCESAMIENTO ═══════ --}}
    @if($step === 2)
        <div @if($processingStatus === 'processing' || $processingStatus === 'pending')
        wire:poll.2s.visible="checkProcessingStatus" @endif>
            <x-card class="max-w-lg mx-auto p-10 text-center transition-all duration-300" x-data>
                @if($processingStatus === 'processing' || $processingStatus === 'pending')
                    {{-- Minimalist Premium Loader --}}
                    <div class="mb-8">
                        <div class="relative w-16 h-16 mx-auto mb-8">
                            {{-- Outer breathing ring --}}
                            <div class="absolute inset-0 rounded-full border-2 border-primary-200 bg-primary-50/30 animate-ping opacity-60"
                                style="animation-duration: 2s;"></div>
                            {{-- Inner Track --}}
                            <div class="absolute inset-0 rounded-full border-2 border-border/40"></div>
                            {{-- Spinning Arch --}}
                            <div class="absolute inset-0 rounded-full border-2 border-primary-600 border-r-transparent border-b-transparent animate-spin"
                                style="animation-duration: 1s;"></div>

                            {{-- Central icon --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <x-lucide-file-search class="w-6 h-6 text-primary-600" />
                            </div>
                        </div>
                        <h2 class="text-h3 font-medium text-text-primary mb-2 tracking-tight">Analizando documento</h2>
                        <p class="text-small text-text-muted max-w-xs mx-auto leading-relaxed mb-4">
                            Extrayendo datos con inteligencia artificial.<br>
                            Puede tomar hasta 30 segundos.
                        </p>
                    </div>

                    <x-status-chip icon="sparkles" color="primary">Procesamiento en segundo plano</x-status-chip>

                    <div class="mt-8 flex justify-center">
                        <x-button
                            href="{{ $source === 'borradores' ? route('requisiciones.index', ['tab' => 'borradores']) : route('requisiciones.index') }}"
                            variant="ghost" icon="arrow-left"
                            class="text-xs text-text-muted hover:text-text-primary shadow-none border-transparent hover:bg-surface-hover/50"
                            wire:navigate>
                            Volver mientras se procesa
                        </x-button>
                    </div>

                @elseif($processingStatus === 'failed')
                    {{-- Error state --}}
                    <div class="flex flex-col items-center max-w-sm mx-auto text-center py-2">
                        {{-- Ícono de error con borde semántico --}}
                        <div
                            class="w-14 h-14 rounded-full bg-danger-light/50 flex items-center justify-center mb-6 shrink-0 shadow-sm">
                            <x-lucide-alert-triangle class="w-6 h-6 text-danger" stroke-width="1.5" wire:ignore />
                        </div>

                        {{-- Título y descripción --}}
                        <h2 class="text-h3 font-medium text-text-primary mb-2 tracking-tight">Error al procesar</h2>
                        <p class="text-small text-text-muted mb-6">No fue posible extraer los datos del documento
                            automáticamente.</p>

                        {{-- Caja de mensaje de error con color semántico --}}
                        <x-feedback-alert variant="danger" icon="info" class="mb-8">
                            {{ $errorMessage ?? 'Ocurrió un error inesperado durante el procesamiento.' }}
                        </x-feedback-alert>

                        {{-- Botones con jerarquía clara --}}
                        <div class="flex flex-col w-full gap-3">
                            {{-- Acción principal --}}
                            <x-button wire:click="retryProcessing" variant="primary"
                                class="w-full group shadow-sm rounded-xl py-2.5" icon="refresh-cw"
                                iconClass="transition-transform group-hover:rotate-180 duration-500">
                                Reintentar extracción
                            </x-button>
                            {{-- Separador visual --}}
                            <div class="flex items-center gap-3 my-1">
                                <div class="flex-1 h-px bg-border/50"></div>
                                <span
                                    class="text-xs font-medium tracking-wide uppercase text-text-muted whitespace-nowrap">opciones
                                    manuales</span>
                                <div class="flex-1 h-px bg-border/50"></div>
                            </div>
                            {{-- Acciones secundarias --}}
                            <div class="grid grid-cols-2 gap-3">
                                <x-button wire:click="continueManually" variant="soft" class="w-full rounded-xl">
                                    Llenar a mano
                                </x-button>
                                <x-button wire:click="resetWizard" variant="soft" class="w-full rounded-xl">
                                    Otro archivo
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    @endif


    {{-- ═══════ PASO 3: FORMULARIO EDITABLE ═══════ --}}
    @if($step === 3)
        @php
            $currentIndex = array_search($activeQuotationId, $quotationIds);
            $totalCount = count($quotationIds);
            $prevId = $currentIndex !== false && $currentIndex > 0 ? $quotationIds[$currentIndex - 1] : null;
            $nextId = $currentIndex !== false && $currentIndex < $totalCount - 1 ? $quotationIds[$currentIndex + 1] : null;
            $completedCount = count($completedQuotationIds);
        @endphp

        @if($totalCount > 1)
            <div class="mb-6 border-b border-border/40 flex items-center justify-between pb-4">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                        Progreso de revisión
                    </span>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-base font-semibold text-text-primary">
                            Cotización {{ $currentIndex !== false ? $currentIndex + 1 : 1 }} <span
                                class="text-text-muted font-normal">de {{ $totalCount }}</span>
                        </span>
                        @if($completedCount > 0)
                            <span class="badge badge-success">
                                {{ $completedCount }} completadas
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($prevId)
                        <x-button type="button" wire:click="setActiveTab({{ $prevId }})" variant="secondary" icon="chevron-left"
                            class="text-xs">
                            Anterior
                        </x-button>
                    @else
                        <x-button type="button" disabled variant="secondary" icon="chevron-left" class="text-xs">
                            Anterior
                        </x-button>
                    @endif

                    @if($nextId)
                        <x-button type="button" wire:click="setActiveTab({{ $nextId }})" variant="secondary"
                            iconRight="chevron-right" class="text-xs">
                            Siguiente
                        </x-button>
                    @else
                        <x-button type="button" disabled variant="secondary" iconRight="chevron-right" class="text-xs">
                            Siguiente
                        </x-button>
                    @endif
                </div>
            </div>
        @endif

        <form wire:submit="saveRequisition" x-data wire:key="form-{{ $activeQuotationId }}"> {{-- General Info --}}
            <x-card class="mb-6">
                <div class="px-6 py-4 border-b border-border/40 flex items-center justify-between">
                    <h3 class="font-medium text-text-primary tracking-tight">Información General</h3>
                    {{-- $quotation se resuelve en QuotationWizard::render() --}}
                    @if($quotation)
                        <x-button type="button"
                            @click="$dispatch('open-preview', { url: '{{ route('file.preview', ['path' => $quotation->file_path]) }}', type: '{{ str_ends_with(strtolower($quotation->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg' }}' })"
                            variant="soft" icon="file-search" class="text-xs">
                            Ver documento original
                        </x-button>
                    @endif
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <x-form-field label="Proyecto" :required="true" :error="$errors->first('projectId')">
                            <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
                                placeholder="Seleccionar proyecto..." />
                        </x-form-field>

                        <x-form-field label="Proveedor" :error="$errors->first('supplierName')">
                            <div class="relative w-full">
                                <x-custom-combobox wire:model.live="supplierName"
                                    :options="$suppliers->pluck('trade_name')->toArray()"
                                    placeholder="Nombre del proveedor..." class="w-full"
                                    inputClass="{{ isset($supplierMatch['status']) ? 'pr-8' : '' }}">
                                </x-custom-combobox>
                                @if(($supplierMatch['status'] ?? '') === 'new')
                                    <x-lucide-plus-circle
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-warning pointer-events-none"
                                        title="Se creará como nuevo proveedor" wire:ignore />
                                @elseif(($supplierMatch['status'] ?? '') === 'fuzzy')
                                    <x-lucide-sparkles
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                        title="Similitud {{ round(($supplierMatch['confidence'] ?? 0) * 100) }}%" wire:ignore />
                                @elseif(($supplierMatch['status'] ?? '') === 'exact')
                                    <x-lucide-check-circle-2
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-success pointer-events-none"
                                        title="Proveedor existente" wire:ignore />
                                @endif
                            </div>
                        </x-form-field>

                        <x-form-field label="Tienda / Sucursal" :error="$errors->first('storeName')">
                            <input wire:model="storeName" type="text" class="input" placeholder="Sucursal (opcional)">
                        </x-form-field>

                        <x-form-field label="Fecha" :required="true" :error="$errors->first('date')">
                            <x-date-picker wire:model="date" />
                        </x-form-field>

                        <x-form-field label="Vendedor" :error="$errors->first('vendorName')">
                            <div class="relative w-full">
                                <x-custom-combobox wire:model.live="vendorName"
                                    :options="$vendors->pluck('name')->toArray()" placeholder="Nombre del vendedor..."
                                    class="w-full" inputClass="{{ isset($vendorMatch['status']) ? 'pr-8' : '' }}">
                                </x-custom-combobox>
                                @if(($vendorMatch['status'] ?? '') === 'new')
                                    <x-lucide-plus-circle
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-warning pointer-events-none"
                                        title="Se creará como nuevo vendedor" wire:ignore />
                                @elseif(($vendorMatch['status'] ?? '') === 'fuzzy')
                                    <x-lucide-sparkles
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                                        title="Similitud {{ round(($vendorMatch['confidence'] ?? 0) * 100) }}%" wire:ignore />
                                @elseif(($vendorMatch['status'] ?? '') === 'exact')
                                    <x-lucide-check-circle-2
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-success pointer-events-none"
                                        title="Vendedor existente" wire:ignore />
                                @endif
                            </div>
                        </x-form-field>
                    </div>

                    <div class="md:col-span-3">
                        <x-form-field label="Anotaciones" :error="$errors->first('annotations')" class="mt-4">
                            <textarea wire:model="annotations" class="input w-full" rows="2"
                                placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                        </x-form-field>
                    </div>
                </div>
            </x-card>



            <x-card class="mb-6 overflow-hidden">
                <div class="px-6 py-4 border-b border-border/40 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="font-medium text-text-primary tracking-tight">Productos</h3>
                        @if(count($items) > 0)
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-surface-main border border-border/60 text-xs font-medium text-text-muted">
                                <x-lucide-package class="w-3.5 h-3.5" />
                                {{ count($items) }} {{ count($items) === 1 ? 'artículo' : 'artículos' }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <x-button wire:click="addItem" variant="soft" icon="plus" class="text-xs">
                            Agregar producto
                        </x-button>
                    </div>
                </div>

                @if(count($items) > 0)
                    {{-- Tabla de productos (Desktop) --}}
                    <div class="hidden md:block w-full overflow-x-auto">
                        <table class="w-full text-left table-inputs-compact">
                            <thead>
                                <tr
                                    class="bg-surface-main border-b border-border/40 text-xs font-semibold text-text-muted uppercase tracking-wider">
                                    <th class="pl-6 pr-4 py-3 whitespace-nowrap w-[26%]">Producto</th>
                                    <th class="px-4 py-3 whitespace-nowrap w-[13%]">Categoría</th>
                                    <th class="px-4 py-3 text-center whitespace-nowrap w-[8%]">Cant.</th>
                                    <th class="px-4 py-3 text-center whitespace-nowrap w-[9%]">Unidad</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap w-[11%]">P.U. s/IVA</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap w-[13%]">Subtotal</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap w-[12%]">Total c/IVA</th>
                                    <th class="pr-6 pl-4 py-3 w-[6%]"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/40">
                                @foreach($items as $i => $item)
                                    @php
                                        $productStatus = $item['_match']['product']['status'] ?? '';
                                        $productBorder = match (true) {
                                            $productStatus === 'exact' => 'bg-success-light',
                                            $productStatus === 'fuzzy' => 'bg-primary-50',
                                            $productStatus === 'new' => 'bg-warning-light',
                                            default => '',
                                        };
                                    @endphp
                                    <tr class="align-middle hover:bg-surface-hover/30 transition-colors group"
                                        wire:key="item-row-{{ $item['id'] ?? $i }}">
                                        {{-- Nombre / Producto --}}
                                        <td class="pl-6 pr-4 py-4">
                                            @php
                                                $isFuzzyPending = isset($item['product_confirmed'])
                                                    && !$item['product_confirmed']
                                                    && ($item['_match']['product']['status'] ?? '') === 'fuzzy';
                                                $productStatus = $item['_match']['product']['status'] ?? '';
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
                                                            <x-lucide-check-circle-2 class="w-4 h-4" wire:ignore />
                                                        </div>
                                                    @elseif($productStatus === 'new')
                                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-warning shrink-0 z-10"
                                                            title="Se creará como nuevo producto">
                                                            <x-lucide-plus-circle class="w-4 h-4" wire:ignore />
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Categoría --}}
                                        <td class="px-4 py-4">
                                            @php $hasCatConflict = isset($item['conflict']['category']); @endphp
                                            <x-conflict-popover type="category-conflict" :item="$item" :index="$i"
                                                triggerRight="right-8">
                                                <x-custom-select wire:model.live="items.{{ $i }}.category_id"
                                                    :options="$categories->pluck('name', 'id')->toArray()"
                                                    placeholder="Sin categoría" textClass="{{ $hasCatConflict ? 'pr-6' : '' }}" />
                                            </x-conflict-popover>
                                        </td>

                                        {{-- Cantidad --}}
                                        <td class="px-4 py-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number"
                                                step="0.01" class="input input-inline text-center tabular-nums text-small w-full"
                                                placeholder="0">
                                        </td>

                                        {{-- Unidad --}}
                                        <td class="px-4 py-4">
                                            @php $hasUnitConflict = isset($item['conflict']['unit']); @endphp
                                            <x-conflict-popover type="unit-conflict" :item="$item" :index="$i">
                                                <x-custom-combobox wire:model.live.debounce.400ms="items.{{ $i }}.unit"
                                                    :options="$measures->mapWithKeys(fn($m) => [($m->abbreviation ?: $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')])->toArray()"
                                                    placeholder="Unidad"
                                                    inputClass="input-inline {{ $hasUnitConflict ? 'pr-8' : '' }}">
                                                </x-custom-combobox>
                                            </x-conflict-popover>
                                        </td>

                                        {{-- Precio Unitario --}}
                                        <td class="px-4 py-4">
                                            <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number"
                                                step="0.01" class="input input-inline text-right tabular-nums text-small w-full"
                                                placeholder="0.00">
                                            @if(($item['discount_percent'] ?? 0) > 0)
                                                <div class="mt-0.5 flex items-center justify-end gap-1.5 text-xs">
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
                                            class="px-4 py-4 text-right font-medium text-text-primary tabular-nums text-small align-middle">
                                            ${{ number_format($item['line_subtotal'], 2, '.', ',') }}
                                        </td>

                                        {{-- Total con IVA --}}
                                        <td
                                            class="px-4 py-4 text-right font-semibold text-text-primary tabular-nums text-small align-middle">
                                            ${{ number_format($item['line_total'], 2, '.', ',') }}
                                        </td>

                                        {{-- Delete --}}
                                        <td class="pr-6 pl-4 py-4 text-center">
                                            <x-button type="button" wire:click="removeItem({{ $i }})" variant="icon-danger"
                                                icon="trash-2"
                                                class="mt-1 opacity-40 hover:opacity-100 focus:opacity-100 transition-opacity" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Formulario de productos (Mobile) --}}
                    <div class="md:hidden flex flex-col gap-4 px-6 pt-6">
                        @foreach($items as $i => $item)
                            @php
                                $productStatus = $item['_match']['product']['status'] ?? '';
                                $productBorder = match (true) {
                                    $productStatus === 'exact' => 'border-success/30 bg-success/5',
                                    $productStatus === 'fuzzy' => 'border-primary-500/30 bg-primary-50/5',
                                    $productStatus === 'new' => 'border-warning/30 bg-warning/5',
                                    default => '',
                                };
                                $isFuzzyPending = isset($item['product_confirmed']) && !$item['product_confirmed'] && ($item['_match']['product']['status'] ?? '') === 'fuzzy';
                                $hasProductIndicator = $isFuzzyPending || in_array($productStatus, ['exact', 'new']);
                                $hasCatConflict = isset($item['conflict']['category']);
                                $hasUnitConflict = isset($item['conflict']['unit']);
                            @endphp
                            <div class="bg-surface-main rounded-xl p-5 relative flex flex-col gap-3"
                                wire:key="mobile-item-{{ $item['id'] ?? $i }}">

                                {{-- Card Header --}}
                                <div class="flex justify-between items-center border-b border-border/40 pb-2 mb-1">
                                    <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                                        Artículo {{ $i + 1 }}
                                    </span>
                                    <button type="button" wire:click="removeItem({{ $i }})"
                                        class="text-danger opacity-70 hover:opacity-100 p-1 -mr-1 transition-opacity">
                                        <x-lucide-trash-2 class="w-4 h-4" />
                                    </button>
                                </div>

                                <x-form-field label="Producto">
                                    <div class="relative">
                                        <x-conflict-popover type="fuzzy-product" :item="$item" :index="$i">
                                            <input wire:model.live.debounce.600ms="items.{{ $i }}.name" type="text"
                                                class="input w-full {{ $productBorder }} {{ $hasProductIndicator ? 'pr-8' : '' }}"
                                                placeholder="Nombre del producto">
                                        </x-conflict-popover>
                                        @if(!$isFuzzyPending)
                                            @if($productStatus === 'exact')
                                                <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-success shrink-0 z-10"
                                                    title="Confirmado">
                                                    <x-lucide-check-circle-2 class="w-4 h-4" wire:ignore />
                                                </div>
                                            @elseif($productStatus === 'new')
                                                <div class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-warning shrink-0 z-10"
                                                    title="Nuevo producto">
                                                    <x-lucide-plus-circle class="w-4 h-4" wire:ignore />
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </x-form-field>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-form-field label="Categoría">
                                        <x-conflict-popover type="category-conflict" :item="$item" :index="$i"
                                            triggerRight="right-8">
                                            <x-custom-select wire:model.live="items.{{ $i }}.category_id"
                                                :options="$categories->pluck('name', 'id')->toArray()" placeholder="Sin categoría"
                                                textClass="{{ $hasCatConflict ? 'pr-6' : '' }}" />
                                        </x-conflict-popover>
                                    </x-form-field>
                                    <x-form-field label="Unidad">
                                        <x-conflict-popover type="unit-conflict" :item="$item" :index="$i">
                                            <x-custom-combobox wire:model.live.debounce.400ms="items.{{ $i }}.unit"
                                                :options="$measures->mapWithKeys(fn($m) => [($m->abbreviation ?: $m->name) => $m->name . ($m->abbreviation ? ' (' . $m->abbreviation . ')' : '')])->toArray()"
                                                placeholder="Unidad" inputClass="{{ $hasUnitConflict ? 'pr-8' : '' }}">
                                            </x-custom-combobox>
                                        </x-conflict-popover>
                                    </x-form-field>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <x-form-field label="Cantidad">
                                        <input wire:model.live.debounce.400ms="items.{{ $i }}.quantity" type="number" step="0.01"
                                            class="input w-full" placeholder="0">
                                    </x-form-field>
                                    <x-form-field label="Precio U.">
                                        <input wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" type="number" step="0.01"
                                            class="input w-full" placeholder="0.00">
                                    </x-form-field>
                                </div>

                                <div class="mt-2 flex flex-col gap-1 pt-3 border-t border-border/50">
                                    <div class="flex justify-between items-center">
                                        <span class="text-small text-text-muted">Subtotal</span>
                                        <span
                                            class="text-small font-medium">${{ number_format($item['line_subtotal'], 2, '.', ',') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-small font-medium text-text-secondary">Total c/IVA</span>
                                        <span
                                            class="font-bold text-text-primary tabular-nums">${{ number_format($item['line_total'], 2, '.', ',') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Totales externos alineados a la derecha (Estilo Recibo) --}}
                    @php
                        $subtotalSinIva = $this->subtotalSinIva();
                        $totalConIva = $this->totalConIva();
                        $totalIva = $this->totalIva();
                        $totalDescuento = $this->totalDescuento();
                        $hasAnyDiscount = $this->hasAnyDiscount();
                        $subtotalBruto = $this->subtotalBruto();
                    @endphp
                    <div class="flex justify-end px-8 pt-8 pb-8 border-t border-border/40">
                        <x-totals-summary class="w-full sm:w-1/2 md:w-1/3 min-w-[280px]">
                            <div class="flex flex-col gap-3">
                                @if($hasAnyDiscount)
                                    <div class="flex items-center justify-between text-small">
                                        <span class="text-text-muted">Subtotal bruto</span>
                                        <span
                                            class="font-medium text-text-secondary tabular-nums">${{ number_format($subtotalBruto, 2, '.', ',') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-small text-success">
                                        <span class="flex items-center gap-1.5 font-medium">
                                            <x-lucide-tag class="w-3.5 h-3.5" wire:ignore />
                                            Descuento total
                                        </span>
                                        <span
                                            class="font-semibold tabular-nums">-${{ number_format($totalDescuento, 2, '.', ',') }}</span>
                                    </div>
                                    <div class="h-px bg-border/40 my-1"></div>
                                @endif
                                <div class="flex items-center justify-between text-small">
                                    <span class="text-text-muted">Subtotal s/IVA</span>
                                    <span
                                        class="font-medium text-text-secondary tabular-nums">${{ number_format($subtotalSinIva, 2, '.', ',') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-small">
                                    <span class="text-text-muted">IVA (16%)</span>
                                    <span class="font-medium text-text-muted tabular-nums">
                                        @if($totalIva > 0) ${{ number_format($totalIva, 2, '.', ',') }}
                                        @else <span class="text-warning">Pendiente</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-border/60">
                                <span class="text-body font-semibold text-text-primary">Total final</span>
                                <span
                                    class="text-2xl font-bold text-text-primary tabular-nums tracking-tight">${{ number_format($totalConIva, 2, '.', ',') }}</span>
                            </div>

                            {{-- Botón de Acción --}}
                            <div class="pt-6 mt-6">
                                <x-button type="submit" variant="primary"
                                    class="w-full py-3 rounded-xl shadow-sm text-small tracking-wide" target="saveRequisition"
                                    icon="check-circle">
                                    Confirmar y Crear Requisición
                                </x-button>
                            </div>
                        </x-totals-summary>
                    </div>
                @else
                    <div class="px-6 pb-6 pt-2">
                        <x-empty-state icon="package-open" title="Sin productos detectados"
                            message="Agrégalos manualmente con el botón Agregar." class="py-12" />
                    </div>
                @endif
            </x-card>
        </form>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>