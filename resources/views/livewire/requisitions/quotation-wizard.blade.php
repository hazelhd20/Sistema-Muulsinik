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
    openLocalPreview() {
        const fileInput = document.getElementById('file-upload-input');
        if (fileInput && fileInput.files.length > 0) {
            this.previewUrl = URL.createObjectURL(fileInput.files[0]);
            this.previewType = fileInput.files[0].type;
            this.showPreviewModal = true;
        }
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
                <div class="flex items-center gap-2 {{ $num < count([1, 2, 3]) ? 'flex-1' : '' }}">
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
                {{-- Drag & Drop Zone --}}
                <div wire:key="upload-zone" x-data="{ isDragging: false }"
                    x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })"
                    x-on:dragover.prevent="isDragging = true" x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    class="relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300 cursor-pointer"
                    :class="isDragging ? 'border-primary-500 bg-primary-50/50 scale-[1.02]' : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20'"
                    @click="$refs.fileInput.click()">
                    <input id="file-upload-input" x-ref="fileInput" type="file" wire:model="file"
                        accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls" class="hidden">

                    <div class="flex flex-col items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-primary-50 flex items-center justify-center">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-primary-600" wire:ignore></i>
                        </div>

                        <div>
                            <p class="text-h3 text-text-primary">
                                Arrastra tu cotización aquí
                            </p>
                            <p class="text-body text-text-muted mt-1">
                                o haz clic para seleccionar un archivo
                            </p>
                        </div>

                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-3 py-1 rounded-lg bg-red-50 text-red-600 text-xs-fluid font-medium">PDF</span>
                            <span
                                class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs-fluid font-medium">XLSX</span>
                            <span
                                class="px-3 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs-fluid font-medium">JPG</span>
                            <span
                                class="px-3 py-1 rounded-lg bg-green-50 text-green-600 text-xs-fluid font-medium">PNG</span>
                        </div>

                        <p class="text-xs-fluid text-text-muted mt-1">Máximo 20 MB</p>
                    </div>
                </div>

                {{-- Loading indicator while uploading --}}
                <div x-data="{ uploading: false }" x-on:livewire-upload-start.window="uploading = true"
                    x-on:livewire-upload-finish.window="uploading = false"
                    x-on:livewire-upload-error.window="uploading = false" x-show="uploading" x-cloak class="mt-4 w-full">
                    <div
                        class="flex items-center justify-center gap-3 p-4 rounded-xl bg-primary-50 border border-primary-100">
                        <svg class="animate-spin h-5 w-5 text-primary-600" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span class="text-body font-medium text-primary-700">Subiendo archivo...</span>
                    </div>
                </div>

                {{-- File selected preview --}}
                @if($file && !$errors->has('file'))
                    @php
                        $ext = strtolower($file->getClientOriginalExtension());
                        $iconData = match (true) {
                            in_array($ext, ['jpg', 'jpeg', 'png']) => ['icon' => 'image', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                            in_array($ext, ['xlsx', 'xls']) => ['icon' => 'file-spreadsheet', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
                            $ext === 'pdf' => ['icon' => 'file-text', 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
                            default => ['icon' => 'file', 'color' => 'text-primary-600', 'bg' => 'bg-primary-50'],
                        };
                    @endphp
                    <div wire:key="file-preview-{{ md5($file->getClientOriginalName()) }}" x-data
                        x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })"
                        class="mt-4 p-4 rounded-xl bg-surface-main border border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg {{ $iconData['bg'] }} flex items-center justify-center">
                                <i data-lucide="{{ $iconData['icon'] }}" class="w-5 h-5 {{ $iconData['color'] }}"
                                    wire:ignore></i>
                            </div>
                            <div>
                                <p class="text-body font-medium text-text-primary">{{ $file->getClientOriginalName() }}</p>
                                @php
                                    $fileSize = 0;
                                    try {
                                        $fileSize = $file->getSize();
                                    } catch (\Exception $e) {
                                    }
                                @endphp
                                <p class="text-xs-fluid text-text-muted">{{ number_format($fileSize / 1024, 1) }} KB</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openLocalPreview"
                                class="p-1.5 rounded-lg hover:bg-primary-50 text-primary-600 transition" title="Vista previa">
                                <i data-lucide="eye" class="w-4 h-4" wire:ignore></i>
                            </button>
                            <button wire:key="btn-remove-file" type="button" wire:click="$set('file', null)"
                                @click="document.getElementById('file-upload-input').value = ''"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                                <i data-lucide="x" class="w-4 h-4" wire:ignore></i>
                            </button>
                        </div>
                    </div>
                @endif

                @error('file')
                    <div wire:key="upload-error" x-data
                        x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })"
                        class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-body flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0" wire:ignore></i>
                        {{ $message }}
                    </div>
                @enderror

                {{-- Process Button --}}
                @if($file && !$errors->has('file'))
                    <button wire:key="process-btn" x-data
                        x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })" type="button"
                        wire:click="processUpload" wire:loading.attr="disabled" wire:target="processUpload"
                        class="btn-primary relative w-full mt-6 py-3 text-body">
                        <span wire:loading.class="opacity-0" wire:target="processUpload"
                            class="flex items-center justify-center gap-2 transition-opacity">
                            <i data-lucide="scan-line" class="w-5 h-5" wire:ignore></i>
                            Procesar Cotización
                        </span>
                        <span wire:loading wire:target="processUpload"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                @endif
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

                    {{-- Animated progress bar --}}
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full animate-pulse"
                            style="width: 70%; animation: progress-indeterminate 2s ease-in-out infinite"></div>
                    </div>

                    <p class="text-xs-fluid text-text-muted mt-4">
                        <i data-lucide="info" class="w-3 h-3 inline"></i>
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
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-h2 text-text-primary flex items-center gap-2">
                            <i data-lucide="info" class="w-5 h-5 text-primary-600"></i>
                            Información General
                        </h2>
                        @php
                            $quotation = $quotationId ? \App\Models\Quotation::find($quotationId) : null;
                        @endphp
                        @if($quotation)
                            <button type="button"
                                @click="openServerPreview('{{ route('file.preview', ['path' => $quotation->file_path]) }}', '{{ str_ends_with(strtolower($quotation->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg' }}')"
                                class="btn-secondary text-small">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                Ver Documento Original
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-body font-medium text-text-primary mb-1.5">Proyecto *</label>
                            <x-custom-select wire:model="projectId" :options="$projects->pluck('name', 'id')->toArray()"
                                placeholder="Seleccionar proyecto..." />
                            @error('projectId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-body font-medium text-text-primary mb-1.5">Fecha de creación *</label>
                            <input wire:model="date" type="date" class="input">
                            @error('date') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-body font-medium text-text-primary mb-1.5">Proveedor</label>
                            <div class="flex flex-col gap-1">
                                <input type="text" wire:model.live="supplierName" list="suppliers-list" class="input"
                                    placeholder="Seleccionar o escribir nuevo proveedor...">
                                <datalist id="suppliers-list">
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->trade_name }}"></option>
                                    @endforeach
                                </datalist>
                                @if(($supplierMatch['status'] ?? '') === 'new')
                                    <span class="text-xs-fluid text-amber-600 font-medium">
                                        <i data-lucide="plus-circle" class="w-3 h-3 inline"></i> Se creará como nuevo proveedor.
                                    </span>
                                @elseif(($supplierMatch['status'] ?? '') === 'fuzzy')
                                    <span class="text-xs-fluid text-blue-600 font-medium">
                                        <i data-lucide="search" class="w-3 h-3 inline"></i>
                                        Detectado por similitud ({{ round(($supplierMatch['confidence'] ?? 0) * 100) }}%)
                                    </span>
                                @elseif(($supplierMatch['status'] ?? '') === 'exact')
                                    <span class="text-xs-fluid text-green-600 font-medium">
                                        <i data-lucide="check-circle" class="w-3 h-3 inline"></i> Proveedor existente.
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-body font-medium text-text-primary mb-1.5">Tienda / Sucursal</label>
                            <input wire:model="storeName" type="text" class="input" placeholder="Sucursal (opcional)">
                        </div>

                        <div>
                            <label class="block text-body font-medium text-text-primary mb-1.5">Vendedor (Atiende)</label>
                            <div class="flex flex-col gap-1">
                                <input wire:model.live="vendorName" type="text" list="vendors-list" class="input"
                                    placeholder="Seleccionar o escribir nombre del vendedor...">
                                <datalist id="vendors-list">
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->name }}"></option>
                                    @endforeach
                                </datalist>
                                @if(($vendorMatch['status'] ?? '') === 'new')
                                    <span class="text-xs-fluid text-amber-600 font-medium">
                                        <i data-lucide="plus-circle" class="w-3 h-3 inline"></i> Se creará como nuevo vendedor.
                                    </span>
                                @elseif(($vendorMatch['status'] ?? '') === 'fuzzy')
                                    <span class="text-xs-fluid text-blue-600 font-medium">
                                        <i data-lucide="search" class="w-3 h-3 inline"></i>
                                        Detectado por similitud ({{ round(($vendorMatch['confidence'] ?? 0) * 100) }}%)
                                    </span>
                                @elseif(($vendorMatch['status'] ?? '') === 'exact')
                                    <span class="text-xs-fluid text-green-600 font-medium">
                                        <i data-lucide="check-circle" class="w-3 h-3 inline"></i> Vendedor existente.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-body font-medium text-text-primary mb-1.5">Anotaciones</label>
                        <textarea wire:model="annotations" class="input" rows="2"
                            placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                        @error('annotations') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- IVA Toggle — visible cuando la IA no pudo detectar --}}
            @if($quotationIncludesTax === null && count($items) > 0)
                <div class="card mb-6 border-amber-200 bg-amber-50/50">
                    <div class="p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                                <i data-lucide="receipt" class="w-5 h-5 text-amber-600" wire:ignore></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-small font-semibold text-amber-900 mb-1">¿Los precios incluyen IVA?</h3>
                                <p class="text-xs-fluid text-amber-700 mb-3">No se detectó información de IVA en la cotización.
                                    Indica
                                    si los precios ya incluyen el 16% de IVA.</p>
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="setTaxInclusion(false)" class="btn-secondary">
                                        Precios antes de IVA
                                    </button>
                                    <button type="button" wire:click="setTaxInclusion(true)" class="btn-secondary">
                                        Precios con IVA incluido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($quotationIncludesTax !== null && count($items) > 0)
                {{-- Estado resuelto: badge informativo compacto --}}
                <div class="card mb-6">
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-body">
                            <i data-lucide="receipt" class="w-4 h-4 text-green-600" wire:ignore></i>
                            <span class="text-text-primary font-medium">IVA:</span>
                            @if($quotationIncludesTax)
                                <span class="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-xs-fluid font-medium">Precios con
                                    IVA
                                    incluido — se desglosa automáticamente</span>
                            @else
                                <span class="px-2 py-0.5 rounded-lg bg-green-50 text-green-700 text-xs-fluid font-medium">Precios
                                    sin IVA
                                    — se calcula al 16%</span>
                            @endif
                            @if($taxDetectedByAI)
                                <span class="text-xs-fluid text-text-muted">(detectado por IA)</span>
                            @endif
                        </div>
                        <button type="button" wire:click="$set('quotationIncludesTax', null)"
                            class="text-xs-fluid text-text-muted hover:text-primary-600 transition">
                            Cambiar
                        </button>
                    </div>
                </div>
            @endif

            {{-- Products Table --}}
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-h2 text-text-primary flex items-center gap-2">
                            <i data-lucide="package" class="w-5 h-5 text-primary-600"></i>
                            Productos Extraídos
                            <span class="text-body font-normal text-text-muted">({{ count($items) }}
                                {{ count($items) === 1 ? 'producto' : 'productos' }})</span>
                        </h2>
                        <button type="button" wire:click="addItem" class="btn-secondary text-small">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Agregar producto
                        </button>
                    </div>

                    @error('items') <p class="mb-3 text-xs-fluid text-danger">{{ $message }}</p> @enderror

                    @if(count($items) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <datalist id="measures-list">
                                @foreach($measures as $measure)
                                    <option value="{{ $measure->name }}">
                                        {{ $measure->abbreviation ? '(' . $measure->abbreviation . ')' : '' }}
                                    </option>
                                @endforeach
                            </datalist>
                            <datalist id="categories-list">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}"></option>
                                @endforeach
                            </datalist>
                            <table class="w-full text-body">
                                <thead>
                                    <tr class="bg-surface-main">
                                        <th
                                            class="text-left px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[25%]">
                                            Producto</th>
                                        <th
                                            class="text-left px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[12%]">
                                            Categoría</th>
                                        <th
                                            class="text-center px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[8%]">
                                            Cant.</th>
                                        <th
                                            class="text-center px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[10%]">
                                            Unidad</th>
                                        <th
                                            class="text-right px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[14%]">
                                            P.U. s/IVA</th>
                                        <th
                                            class="text-right px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[14%]">
                                            Subtotal</th>
                                        <th
                                            class="text-right px-3 py-2.5 text-xs-fluid font-semibold text-text-muted uppercase w-[11%]">
                                            Total</th>
                                        <th class="px-3 py-2.5 w-[6%]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                        <tr class="border-t border-gray-50 hover:bg-surface-main/50 transition {{ !empty($item['conflict']) ? 'bg-amber-50/40' : '' }}"
                                            wire:key="item-row-{{ $i }}">
                                            {{-- Nombre --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.name" type="text" class="input text-body"
                                                    placeholder="Nombre del producto">
                                                @if(!empty($item['conflict']))
                                                    <span class="inline-flex items-center gap-1 text-xs-fluid text-amber-700 mt-1">
                                                        <i data-lucide="alert-triangle" class="w-3 h-3" wire:ignore></i>
                                                        Producto registrado — datos diferentes
                                                    </span>
                                                @elseif(($item['_match']['product']['status'] ?? '') === 'exact')
                                                    <span class="inline-flex items-center gap-1 text-xs-fluid text-green-600 mt-1">
                                                        <i data-lucide="check-circle" class="w-3 h-3" wire:ignore></i>
                                                        Existente
                                                    </span>
                                                @elseif(($item['_match']['product']['status'] ?? '') === 'fuzzy')
                                                    <span class="inline-flex items-center gap-1 text-xs-fluid text-blue-600 mt-1">
                                                        <i data-lucide="search" class="w-3 h-3" wire:ignore></i>
                                                        Similar ({{ round(($item['_match']['product']['confidence'] ?? 0) * 100) }}%)
                                                    </span>
                                                @elseif(($item['_match']['product']['status'] ?? '') === 'new')
                                                    <span class="inline-flex items-center gap-1 text-xs-fluid text-text-muted mt-1">
                                                        <i data-lucide="plus-circle" class="w-3 h-3" wire:ignore></i>
                                                        Nuevo
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Categoría --}}
                                            <td class="px-3 py-2">
                                                <select wire:model="items.{{ $i }}.category_id" class="input text-xs-fluid py-1">
                                                    <option value="">Seleccionar...</option>
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if($item['category_name'] && empty($item['category_id']))
                                                    <span class="text-xs-fluid text-amber-600 block mt-1">
                                                        IA: {{ $item['category_name'] }}
                                                    </span>
                                                @elseif($item['category_id'])
                                                    <span class="text-xs-fluid text-green-600 block mt-1">
                                                        Seleccionada del catálogo
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Cantidad --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.quantity" type="number" step="0.01"
                                                    class="input text-body text-center" placeholder="0">
                                            </td>

                                            {{-- Unidad --}}
                                            <td class="px-3 py-2">
                                                <input type="text" wire:model="items.{{ $i }}.unit" list="measures-list"
                                                    class="input text-body" placeholder="Unidad...">
                                                @if(($item['_match']['measure']['status'] ?? '') === 'new')
                                                    <span class="inline-flex items-center gap-1 text-xs-fluid text-amber-600 mt-1">
                                                        <i data-lucide="plus-circle" class="w-3 h-3" wire:ignore></i>
                                                        Nueva
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Precio Unitario (sin IVA) --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01"
                                                    class="input text-body text-right" placeholder="0.00">
                                            </td>



                                            {{-- Subtotal sin IVA (proveedor o calculado) --}}
                                            <td class="px-3 py-2 text-right font-medium text-text-primary">
                                                @php
                                                    $itemSubtotal = $item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
                                                @endphp
                                                ${{ number_format($itemSubtotal, 2, '.', ',') }}
                                            </td>

                                            {{-- Total con IVA (proveedor o calculado) --}}
                                            <td class="px-3 py-2 text-right font-bold text-primary-600">
                                                @php
                                                    $itemTotal = $item['line_total'] ?? ($itemSubtotal + ($item['tax_amount'] ?? 0));
                                                @endphp
                                                ${{ number_format($itemTotal, 2, '.', ',') }}
                                            </td>

                                            {{-- Delete --}}
                                            <td class="px-3 py-2">
                                                <button type="button" wire:click="removeItem({{ $i }})"
                                                    class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        {{-- Fila de conflicto: visible solo cuando hay diferencias con el producto registrado --}}
                                        @if(!empty($item['conflict']))
                                            <tr class="border-t-0 bg-amber-50/60" wire:key="item-conflict-{{ $i }}">
                                                <td colspan="8" class="px-4 py-3">
                                                    <div class="flex items-start gap-3">
                                                        <div
                                                            class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-600"
                                                                wire:ignore></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs-fluid font-semibold text-amber-900 mb-1.5">
                                                                Este producto ya existe en el catálogo con datos diferentes. ¿Actualizar
                                                                el catálogo?
                                                            </p>
                                                            <div class="flex flex-wrap gap-4 mb-2">
                                                                @if(isset($item['conflict']['category']))
                                                                    <div class="text-xs-fluid text-amber-800">
                                                                        <span class="font-medium">Categoría:</span>
                                                                        <span
                                                                            class="line-through text-amber-500 mx-1">{{ $item['conflict']['category']['registered'] }}</span>
                                                                        <i data-lucide="arrow-right" class="w-3 h-3 inline text-amber-600"
                                                                            wire:ignore></i>
                                                                        <span
                                                                            class="font-semibold text-amber-900 ml-1">{{ $item['conflict']['category']['suggested'] }}</span>
                                                                    </div>
                                                                @endif
                                                                @if(isset($item['conflict']['unit']))
                                                                    <div class="text-xs-fluid text-amber-800">
                                                                        <span class="font-medium">Unidad:</span>
                                                                        <span
                                                                            class="line-through text-amber-500 mx-1">{{ $item['conflict']['unit']['registered'] }}</span>
                                                                        <i data-lucide="arrow-right" class="w-3 h-3 inline text-amber-600"
                                                                            wire:ignore></i>
                                                                        <span
                                                                            class="font-semibold text-amber-900 ml-1">{{ $item['conflict']['unit']['suggested'] }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="flex flex-wrap gap-2">
                                                                @if(isset($item['conflict']['category']) && isset($item['conflict']['unit']))
                                                                    <button type="button"
                                                                        wire:click="resolveProductConflict({{ $i }}, 'both')"
                                                                        class="px-3 py-1 rounded-lg bg-amber-600 text-white text-xs-fluid font-medium hover:bg-amber-700 transition">
                                                                        Actualizar ambos
                                                                    </button>
                                                                @endif
                                                                @if(isset($item['conflict']['category']))
                                                                    <button type="button"
                                                                        wire:click="resolveProductConflict({{ $i }}, 'category')"
                                                                        class="btn-secondary text-xs-fluid border-amber-300 text-amber-800 hover:bg-amber-50">
                                                                        Solo categoría
                                                                    </button>
                                                                @endif
                                                                @if(isset($item['conflict']['unit']))
                                                                    <button type="button"
                                                                        wire:click="resolveProductConflict({{ $i }}, 'unit')"
                                                                        class="btn-secondary text-xs-fluid border-amber-300 text-amber-800 hover:bg-amber-50">
                                                                        Solo unidad
                                                                    </button>
                                                                @endif
                                                                <button type="button" wire:click="dismissProductConflict({{ $i }})"
                                                                    class="btn-secondary text-xs-fluid">
                                                                    Conservar datos actuales
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $subtotalSinIva = collect($items)->sum(fn($item) => $item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)));
                                        $totalConIva = collect($items)->sum(fn($item) => $item['line_total'] ?? (($item['line_subtotal'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0))) + ($item['tax_amount'] ?? 0)));
                                        $totalIva = $totalConIva - $subtotalSinIva;
                                    @endphp
                                    <tr class="border-t border-gray-100 bg-surface-main">
                                        <td colspan="5" class="px-3 py-2 text-right text-body text-text-muted">Subtotal (sin
                                            IVA):
                                        </td>
                                        <td class="px-3 py-2 text-right text-body font-medium text-text-primary">
                                            ${{ number_format($subtotalSinIva, 2, '.', ',') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="bg-surface-main">
                                        <td colspan="5" class="px-3 py-2 text-right text-body text-text-muted">IVA (16%):</td>
                                        <td class="px-3 py-2 text-right text-body font-medium text-text-muted">
                                            @if($totalIva > 0)
                                                ${{ number_format($totalIva, 2, '.', ',') }}
                                            @else
                                                <span class="text-xs-fluid text-amber-500">Pendiente</span>
                                            @endif
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="border-t-2 border-gray-200 bg-surface-main">
                                        <td colspan="5" class="px-3 py-3 text-right text-body font-semibold text-text-primary">
                                            Total con IVA:</td>
                                        <td class="px-3 py-3 text-right text-h3 text-primary-600">
                                            ${{ number_format($totalConIva, 2, '.', ',') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-text-muted">
                            <i data-lucide="package-open" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                            <p class="text-body">No se detectaron productos. Agrégalos manualmente.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <button type="button" wire:click="resetWizard" class="btn-secondary">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Subir otro archivo
                </button>

                <div class="flex items-center gap-3">
                    <a href="{{ route('requisiciones.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary py-2.5 px-6" wire:loading.attr="disabled"
                        wire:target="saveRequisition">
                        <span wire:loading.class="opacity-0" wire:target="saveRequisition"
                            class="flex items-center gap-2 transition-opacity">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Guardar Requisición
                        </span>
                        <span wire:loading wire:target="saveRequisition"
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <div x-show="showPreviewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden"
            x-transition>
            <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-surface-card">
                <h3 class="text-h2 text-gray-800 flex items-center gap-2">
                    <i data-lucide="file-search" class="w-5 h-5 text-primary-600"></i> Vista Previa del Documento
                </h3>
                <button @click="showPreviewModal = false"
                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden bg-gray-50/50 p-4 relative">
                <template x-if="isImage()">
                    <img :src="previewUrl" class="w-full h-full object-contain rounded-lg">
                </template>
                <template x-if="isPdf()">
                    <iframe :src="previewUrl"
                        class="w-full h-full border border-gray-200 rounded-lg shadow-sm bg-white"></iframe>
                </template>
                <template x-if="!isImage() && !isPdf()">
                    <div class="flex flex-col items-center justify-center h-full text-gray-500 gap-3">
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