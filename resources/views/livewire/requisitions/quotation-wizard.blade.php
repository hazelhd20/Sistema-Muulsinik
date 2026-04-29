@use('Illuminate\Support\Str')
<div>
    {{-- ═══════ WIZARD HEADER ═══════ --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('requisiciones.index') }}" class="p-2 rounded-xl hover:bg-surface-hover transition" title="Volver">
                <i data-lucide="arrow-left" class="w-5 h-5 text-text-muted"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Subir Cotización</h1>
                <p class="text-sm text-text-muted">Carga un archivo y el sistema extraerá la información automáticamente</p>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-2 mt-6">
            @foreach([1 => 'Subir archivo', 2 => 'Procesando', 3 => 'Revisar y guardar'] as $num => $label)
                <div class="flex items-center gap-2 {{ $num < count([1,2,3]) ? 'flex-1' : '' }}">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300
                            {{ $step > $num ? 'bg-green-500 text-white' : ($step === $num ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-gray-200 text-text-muted') }}">
                            @if($step > $num)
                                <i data-lucide="check" class="w-4 h-4"></i>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="text-sm font-medium {{ $step >= $num ? 'text-text-primary' : 'text-text-muted' }}">{{ $label }}</span>
                    </div>
                    @if($num < 3)
                        <div class="flex-1 h-0.5 mx-2 rounded {{ $step > $num ? 'bg-green-500' : 'bg-gray-200' }} transition-all duration-500"></div>
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
                <div
                    wire:key="upload-zone"
                    x-data="{ isDragging: false }"
                    x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    class="relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300 cursor-pointer"
                    :class="isDragging ? 'border-primary-500 bg-primary-50/50 scale-[1.02]' : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20'"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        id="file-upload-input"
                        x-ref="fileInput"
                        type="file"
                        wire:model="file"
                        accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls"
                        class="hidden"
                    >

                    <div class="flex flex-col items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-primary-100 flex items-center justify-center">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-primary-600" wire:ignore></i>
                        </div>

                        <div>
                            <p class="text-lg font-semibold text-text-primary">
                                Arrastra tu cotización aquí
                            </p>
                            <p class="text-sm text-text-muted mt-1">
                                o haz clic para seleccionar un archivo
                            </p>
                        </div>

                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-3 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-medium">PDF</span>
                            <span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs font-medium">XLSX</span>
                            <span class="px-3 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs font-medium">JPG</span>
                            <span class="px-3 py-1 rounded-lg bg-green-50 text-green-600 text-xs font-medium">PNG</span>
                        </div>

                        <p class="text-xs text-text-muted mt-1">Máximo 20 MB</p>
                    </div>
                </div>

                {{-- Loading indicator while uploading --}}
                <div wire:loading wire:target="file" class="mt-4">
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-primary-50 border border-primary-100">
                        <svg class="animate-spin h-5 w-5 text-primary-600" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="text-sm font-medium text-primary-700">Subiendo archivo...</span>
                    </div>
                </div>

                {{-- File selected preview --}}
                @if($file && !$errors->has('file'))
                    @php
                        $ext = strtolower($file->getClientOriginalExtension());
                        $iconData = match(true) {
                            in_array($ext, ['jpg', 'jpeg', 'png']) => ['icon' => 'image', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
                            in_array($ext, ['xlsx', 'xls']) => ['icon' => 'file-spreadsheet', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
                            $ext === 'pdf' => ['icon' => 'file-text', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
                            default => ['icon' => 'file', 'color' => 'text-primary-600', 'bg' => 'bg-primary-100'],
                        };
                    @endphp
                    <div wire:key="file-preview-{{ md5($file->getClientOriginalName()) }}" x-data x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })" class="mt-4 p-4 rounded-xl bg-surface-main border border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg {{ $iconData['bg'] }} flex items-center justify-center">
                                <i data-lucide="{{ $iconData['icon'] }}" class="w-5 h-5 {{ $iconData['color'] }}" wire:ignore></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-text-primary">{{ $file->getClientOriginalName() }}</p>
                                <p class="text-xs text-text-muted">{{ number_format($file->getSize() / 1024, 1) }} KB</p>
                            </div>
                        </div>
                        <button wire:key="btn-remove-file" type="button" wire:click="$set('file', null)" @click="document.getElementById('file-upload-input').value = ''" class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                            <i data-lucide="x" class="w-4 h-4" wire:ignore></i>
                        </button>
                    </div>
                @endif

                @error('file')
                    <div wire:key="upload-error" x-data x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })" class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0" wire:ignore></i>
                        {{ $message }}
                    </div>
                @enderror

                {{-- Process Button --}}
                @if($file && !$errors->has('file'))
                    <button
                        wire:key="process-btn"
                        type="button"
                        wire:click="processUpload"
                        wire:loading.attr="disabled"
                        wire:target="processUpload"
                        class="btn-primary w-full mt-6 py-3 text-base"
                    >
                        <span wire:loading.class="opacity-0" wire:target="processUpload" class="flex items-center justify-center gap-2 transition-opacity">
                            <i data-lucide="scan-line" class="w-5 h-5" wire:ignore></i>
                            Procesar Cotización
                        </span>
                        <span wire:loading wire:target="processUpload" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══════ PASO 2: PROCESAMIENTO ═══════ --}}
    @if($step === 2)
        <div class="card max-w-lg mx-auto" wire:poll.2s="checkProcessingStatus" x-data x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })">
            <div class="p-8 text-center">
                @if($processingStatus === 'processing' || $processingStatus === 'pending')
                    {{-- Animación de procesamiento --}}
                    <div class="mb-6">
                        <div class="w-20 h-20 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
                            <svg class="animate-spin h-10 w-10 text-primary-600" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-text-primary mb-2">Procesando tu cotización</h2>
                        <p class="text-sm text-text-muted">
                            Estamos extrayendo la información del documento mediante Inteligencia Artificial.<br>
                            Esto puede tomar hasta 30 segundos...
                        </p>
                    </div>

                    {{-- Animated progress bar --}}
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full animate-pulse" style="width: 70%; animation: progress-indeterminate 2s ease-in-out infinite"></div>
                    </div>

                    <p class="text-xs text-text-muted mt-4">
                        <i data-lucide="info" class="w-3 h-3 inline"></i>
                        No cierres esta página, el resultado aparecerá automáticamente.
                    </p>

                @elseif($processingStatus === 'failed')
                    {{-- Error state --}}
                    <div class="mb-6">
                        <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
                            <i data-lucide="alert-triangle" class="w-10 h-10 text-red-500"></i>
                        </div>
                        <h2 class="text-xl font-bold text-text-primary mb-2">Error al procesar</h2>
                        <p class="text-sm text-red-600 bg-red-50 p-3 rounded-xl">
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

        <style>
            @keyframes progress-indeterminate {
                0% { transform: translateX(-100%); }
                50% { transform: translateX(0); }
                100% { transform: translateX(100%); }
            }
        </style>
    @endif

    {{-- ═══════ PASO 3: FORMULARIO EDITABLE ═══════ --}}
    @if($step === 3)
        <form wire:submit="saveRequisition" x-data x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({ root: $el }) })">

            {{-- RF-REQ-06: Alertas de campos incompletos --}}
            @if(count($warnings) > 0)
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="text-sm font-semibold text-amber-800 mb-1">Campos pendientes de completar</h3>
                            <ul class="text-sm text-amber-700 space-y-0.5">
                                @foreach($warnings as $warning)
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

            {{-- General Info --}}
            <div class="card mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-text-primary mb-4 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-primary-600"></i>
                        Información General
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Proyecto *</label>
                            <x-custom-select 
                                wire:model="projectId" 
                                :options="$projects->pluck('name', 'id')->toArray()" 
                                placeholder="Seleccionar proyecto..." 
                            />
                            @error('projectId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Fecha de creación *</label>
                            <input wire:model="date" type="date" class="input">
                            @error('date') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Proveedor</label>
                            <div class="flex gap-2">
                                <x-custom-select 
                                    wire:model="supplierId" 
                                    :options="$suppliers->pluck('trade_name', 'id')->toArray()" 
                                    placeholder="{{ $supplierName ?: 'Seleccionar proveedor...' }}" 
                                    class="flex-1"
                                />
                                @if($supplierName && !$supplierId)
                                    <span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs font-medium self-center whitespace-nowrap">
                                        Detectado: {{ Str::limit($supplierName, 20) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Tienda / Sucursal</label>
                            <input wire:model="storeName" type="text" class="input" placeholder="Sucursal (opcional)">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Anotaciones</label>
                        <textarea wire:model="annotations" class="input" rows="2" placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                        @error('annotations') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Products Table --}}
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                            <i data-lucide="package" class="w-5 h-5 text-primary-600"></i>
                            Productos Extraídos
                            <span class="text-sm font-normal text-text-muted">({{ count($items) }} {{ count($items) === 1 ? 'producto' : 'productos' }})</span>
                        </h2>
                        <button type="button" wire:click="addItem" class="btn-secondary text-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Agregar producto
                        </button>
                    </div>

                    @error('items') <p class="mb-3 text-xs text-danger">{{ $message }}</p> @enderror

                    @if(count($items) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-surface-main">
                                        <th class="text-left px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[30%]">Producto</th>
                                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[10%]">Cant.</th>
                                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[10%]">Unidad</th>
                                        <th class="text-right px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[12%]">Precio U.</th>
                                        <th class="text-right px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[12%]">Subtotal</th>
                                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-text-muted uppercase w-[20%]">Homologación</th>
                                        <th class="px-3 py-2.5 w-[6%]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                        <tr class="border-t border-gray-50 hover:bg-surface-main/50 transition">
                                            {{-- Nombre --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.name" type="text" class="input text-sm" placeholder="Nombre del producto">
                                            </td>

                                            {{-- Cantidad --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" class="input text-sm text-center" placeholder="0">
                                            </td>

                                            {{-- Unidad --}}
                                            <td class="px-3 py-2">
                                                <x-custom-select 
                                                    wire:model="items.{{ $i }}.unit" 
                                                    :options="['pza' => 'Pieza', 'kg' => 'Kg', 'm' => 'Metro', 'm2' => 'm²', 'm3' => 'm³', 'lt' => 'Litro', 'bulto' => 'Bulto', 'rollo' => 'Rollo', 'caja' => 'Caja', 'servicio' => 'Servicio', 'lote' => 'Lote', 'galon' => 'Galón', 'tramo' => 'Tramo']" 
                                                    placeholder="Unidad..." 
                                                    class="text-sm"
                                                />
                                            </td>

                                            {{-- Precio Unitario --}}
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" class="input text-sm text-right" placeholder="0.00">
                                            </td>

                                            {{-- Subtotal (calculado) --}}
                                            <td class="px-3 py-2 text-right font-medium text-text-primary">
                                                ${{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2, '.', ',') }}
                                            </td>

                                            {{-- Homologación --}}
                                            <td class="px-3 py-2">
                                                @if(($item['homologation_status'] ?? 'pending') === 'homologated')
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-green-50 text-green-700 text-xs font-medium">
                                                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                                                            Homologado
                                                        </span>
                                                        <button type="button" wire:click="unhomologateItem({{ $i }})" class="p-0.5 rounded hover:bg-red-50 text-text-muted hover:text-danger transition" title="Quitar homologación">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div x-data="{ open: false }" class="relative">
                                                        @if(!empty($homologationSuggestions[$i]))
                                                            <button type="button" @click="open = !open" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium hover:bg-amber-100 transition">
                                                                <i data-lucide="search" class="w-3 h-3"></i>
                                                                {{ count($homologationSuggestions[$i]) }} sugerencia(s)
                                                            </button>
                                                            <div x-show="open" @click.outside="open = false" x-transition class="absolute z-30 top-full left-0 mt-1 bg-white shadow-xl rounded-xl border border-gray-100 w-64 overflow-hidden">
                                                                <div class="p-2 border-b border-gray-100">
                                                                    <p class="text-xs font-semibold text-text-muted">Seleccionar producto del catálogo:</p>
                                                                </div>
                                                                @foreach($homologationSuggestions[$i] as $suggestion)
                                                                    <button type="button"
                                                                        wire:click="homologateItem({{ $i }}, {{ $suggestion['id'] }})"
                                                                        @click="open = false"
                                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-primary-50 transition flex items-center justify-between">
                                                                        <span class="truncate">{{ $suggestion['canonical_name'] }}</span>
                                                                        <span class="text-xs text-text-muted shrink-0 ml-2">{{ $suggestion['similarity'] }}%</span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-gray-100 text-text-muted text-xs">
                                                                <i data-lucide="circle-dashed" class="w-3 h-3"></i>
                                                                Pendiente
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Delete --}}
                                            <td class="px-3 py-2">
                                                <button type="button" wire:click="removeItem({{ $i }})" class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-200 bg-surface-main">
                                        <td colspan="4" class="px-3 py-3 text-right text-sm font-semibold text-text-primary">Total estimado:</td>
                                        <td class="px-3 py-3 text-right text-base font-bold text-primary-600">
                                            ${{ number_format(collect($items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)), 2, '.', ',') }}
                                        </td>
                                        <td colspan="2" class="px-3 py-3">
                                            @php
                                                $pendingCount = collect($items)->where('homologation_status', 'pending')->count();
                                            @endphp
                                            @if($pendingCount > 0)
                                                <span class="text-xs text-amber-600">{{ $pendingCount }} sin homologar</span>
                                            @else
                                                <span class="text-xs text-green-600">✓ Todos homologados</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-text-muted">
                            <i data-lucide="package-open" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                            <p class="text-sm">No se detectaron productos. Agrégalos manualmente.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Raw Text (collapsible) --}}
            @if($rawText)
                <div class="card mb-6" x-data="{ showRaw: false }">
                    <div class="p-4">
                        <button type="button" @click="showRaw = !showRaw" class="flex items-center gap-2 text-sm font-medium text-text-muted hover:text-text-primary transition">
                            <i data-lucide="code" class="w-4 h-4"></i>
                            <span x-text="showRaw ? 'Ocultar texto extraído' : 'Ver texto extraído del documento'"></span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="showRaw && 'rotate-180'"></i>
                        </button>
                        <div x-show="showRaw" x-collapse class="mt-3">
                            <pre class="p-4 bg-gray-900 text-gray-100 rounded-xl text-xs font-mono overflow-x-auto max-h-64 overflow-y-auto">{{ $rawText }}</pre>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <button type="button" wire:click="resetWizard" class="btn-secondary">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Subir otro archivo
                </button>

                <div class="flex items-center gap-3">
                    <a href="{{ route('requisiciones.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary py-2.5 px-6" wire:loading.attr="disabled" wire:target="saveRequisition">
                        <span wire:loading.class="opacity-0" wire:target="saveRequisition" class="flex items-center gap-2 transition-opacity">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Guardar Requisición
                        </span>
                        <span wire:loading wire:target="saveRequisition" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
