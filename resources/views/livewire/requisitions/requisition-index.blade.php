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
    openPreview(url, mimeType) {
        this.previewUrl = url;
        this.previewType = mimeType;
        this.showPreviewModal = true;
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-widest mb-0.5">Compras</p>
            <h1 class="text-h1 text-text-primary">Requisiciones</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('requisiciones.upload') }}" class="btn-primary">
                <i data-lucide="scan-line" class="w-4 h-4"></i>
                Subir Cotización
            </a>
            <button wire:click="openCreateModal" class="btn-secondary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Manual
            </button>
        </div>
    </div>

    @if(session('success'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, icon: 'success', title: '{{ session('success') }}' }); $el.remove()"
            wire:key="toast-success-{{ microtime(true) }}">
        </div>
    @endif
    @if(session('error'))
        <div x-data
            x-init="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, icon: 'error', title: '{{ session('error') }}' }); $el.remove()"
            wire:key="toast-error-{{ microtime(true) }}">
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar requisición..."
                class="input pl-10">
        </div>
        <x-custom-select wire:model.live="statusFilter" :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']" placeholder="Todos los estados"
            class="w-auto min-w-[160px]" />
        <x-custom-select wire:model.live="projectFilter" :options="$projects->pluck('name', 'id')->toArray()"
            placeholder="Todos los proyectos" class="w-auto min-w-[180px]" />
        <x-custom-select wire:model.live="periodFilter"
            :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
            placeholder="Todos los períodos" class="w-auto min-w-[170px]" />
    </div>

    {{-- Requisitions list --}}
    <div class="space-y-3">
        @forelse($requisitions as $req)
            @php
                $sColors = ['borrador' => 'badge-secondary', 'pendiente' => 'badge-warning', 'aprobada' => 'badge-success', 'rechazada' => 'badge-danger'];
                $iconName = match ($req->status) { 'borrador' => 'file-edit', 'pendiente' => 'clock', 'aprobada' => 'check-circle', 'rechazada' => 'x-circle', default => 'file' };
                $iconBg = match ($req->status) { 'borrador' => 'bg-gray-50', 'pendiente' => 'bg-amber-50', 'aprobada' => 'bg-green-50', 'rechazada' => 'bg-red-50', default => 'bg-gray-50' };
                $iconColor = match ($req->status) { 'borrador' => 'text-gray-500', 'pendiente' => 'text-amber-600', 'aprobada' => 'text-green-600', 'rechazada' => 'text-red-500', default => 'text-gray-500' };
            @endphp
            <div x-data="{ open: false }" class="card">

                {{-- ── Cuerpo principal ── --}}
                <div class="flex items-start gap-4">

                    {{-- Ícono de estado --}}
                    <div class="w-9 h-9 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="{{ $iconName }}" class="w-4 h-4 {{ $iconColor }}"></i>
                    </div>

                    {{-- Info central --}}
                    <div class="flex-1 min-w-0">
                        {{-- Fila 1: número + badge --}}
                        <div class="flex items-center gap-2 mb-1.5">
                            <h3 class="text-small font-semibold text-text-primary">{{ $req->number ?? 'REQ-' . $req->id }}</h3>
                            <span class="badge {{ $sColors[$req->status] ?? '' }} shrink-0">{{ ucfirst($req->status) }}</span>
                        </div>

                        {{-- Fila 2: metadatos primarios --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs-fluid text-text-muted">
                            <span class="flex items-center gap-1">
                                <i data-lucide="hard-hat" class="w-3 h-3 shrink-0"></i>
                                <span class="truncate max-w-[160px]">{{ $req->project->name ?? '—' }}</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3 shrink-0"></i>
                                {{ $req->date?->format('d/m/Y') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 shrink-0"></i>
                                {{ $req->creator->name ?? '—' }}
                            </span>
                            @if($req->vendor)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="contact" class="w-3 h-3 shrink-0"></i>
                                    {{ $req->vendor->name }}
                                    @if($req->vendor->supplier)
                                        <span class="text-text-muted/60">· {{ $req->vendor->supplier->trade_name }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Fila 3: anotaciones (solo si existen) --}}
                        @if($req->annotations)
                            <p class="mt-1.5 text-xs-fluid text-text-muted italic truncate max-w-lg" title="{{ $req->annotations }}">
                                "{{ $req->annotations }}"
                            </p>
                        @endif
                    </div>

                    {{-- Total --}}
                    <div class="text-right shrink-0">
                        <p class="text-h2 text-text-primary leading-tight">${{ number_format($req->total, 2, '.', ',') }}</p>
                        <p class="text-xs-fluid text-text-muted">estimado</p>
                    </div>
                </div>

                {{-- ── Footer: trigger collapsible + acciones ── --}}
                <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">

                    {{-- Trigger de productos --}}
                    @if($req->items->isNotEmpty())
                        <button @click="open = !open"
                            class="flex items-center gap-1.5 text-xs-fluid font-medium text-text-muted hover:text-primary-600 transition-colors">
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="open && 'rotate-180'"></i>
                            <span>{{ $req->items->count() }} {{ $req->items->count() === 1 ? 'producto' : 'productos' }}</span>
                        </button>
                    @else
                        <span class="text-xs-fluid text-text-muted/50">Sin productos</span>
                    @endif

                    {{-- Acciones --}}
                    <div class="flex items-center gap-1">
                        @if($req->quotations->isNotEmpty())
                            @php
                                $firstQuot = $req->quotations->first();
                                $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                                $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                            @endphp
                            <button type="button" @click="openPreview('{{ $fileUrl }}', '{{ $mime }}')"
                                class="btn-icon-primary" title="Ver cotización adjunta">
                                <i data-lucide="file-search" class="w-4 h-4"></i>
                            </button>
                        @endif

                        @if($req->status === 'borrador')
                            <button wire:click="submitForApproval({{ $req->id }})"
                                wire:confirm="¿Enviar esta requisición a aprobación?"
                                class="btn-icon-primary" title="Enviar a aprobación">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                        @endif

                        @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                            <button wire:click="approve({{ $req->id }})"
                                wire:confirm="¿Aprobar esta requisición?"
                                class="btn-icon-primary" title="Aprobar">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="openRejectModal({{ $req->id }})"
                                class="btn-icon-danger" title="Rechazar">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        @endif

                        <a href="{{ route('requisiciones.pdf', $req->id) }}"
                            target="_blank"
                            class="btn-icon-primary" title="Descargar PDF">
                            <i data-lucide="file-down" class="w-4 h-4"></i>
                        </a>

                        @if(in_array($req->status, ['borrador', 'rechazada']))
                            <button wire:click="deleteRequisition({{ $req->id }})"
                                wire:confirm="¿Eliminar esta requisición?"
                                class="btn-icon-danger" title="Eliminar">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ── Tabla colapsable de productos ── --}}
                @if($req->items->isNotEmpty())
                    <div x-show="open" x-collapse x-cloak class="mt-3" style="display: none;">
                        <div class="rounded-lg border border-border overflow-hidden overflow-x-auto">
                            <table class="w-full text-body">
                                <thead>
                                    <tr class="bg-surface-main">
                                        <th class="text-left px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">Producto</th>
                                        <th class="text-center px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">Cant.</th>
                                        <th class="text-center px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">Unidad</th>
                                        <th class="text-right px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">P. Unit.</th>
                                        <th class="text-right px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">Subtotal</th>
                                        <th class="text-right px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">IVA</th>
                                        <th class="text-right px-4 py-2 text-xs-fluid font-semibold text-text-muted uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($req->items as $item)
                                        <tr class="border-t border-border/50 hover:bg-surface-main/60 transition-colors">
                                            <td class="px-4 py-2 font-medium text-text-primary">{{ $item->product_name ?? $item->product?->canonical_name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-center text-text-secondary">{{ rtrim(rtrim(number_format($item->quantity, 4), '0'), '.') }}</td>
                                            <td class="px-4 py-2 text-center text-text-muted">{{ $item->measure?->abbreviation ?? $item->unit ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right text-text-secondary">${{ number_format($item->unit_price, 2, '.', ',') }}</td>
                                            <td class="px-4 py-2 text-right text-text-secondary">${{ number_format($item->line_subtotal_computed, 2, '.', ',') }}</td>
                                            <td class="px-4 py-2 text-right">
                                                @if($item->tax_amount !== null)
                                                    <span class="text-text-muted">${{ number_format($item->tax_amount, 2, '.', ',') }}</span>
                                                @else
                                                    <span class="text-xs-fluid text-amber-500">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-text-primary">${{ number_format($item->line_total_computed, 2, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $reqSubtotal = $req->items->sum(fn($i) => $i->line_subtotal_computed);
                                        $reqTax      = $req->items->sum(fn($i) => (float)($i->tax_amount ?? 0));
                                        $reqTotal    = $req->total;
                                    @endphp
                                    <tr class="border-t border-border bg-surface-main">
                                        <td colspan="4" class="px-4 py-2 text-right text-xs-fluid text-text-muted">Totales:</td>
                                        <td class="px-4 py-2 text-right text-xs-fluid font-medium text-text-secondary">${{ number_format($reqSubtotal, 2, '.', ',') }}</td>
                                        <td class="px-4 py-2 text-right text-xs-fluid font-medium text-text-muted">
                                            @if($reqTax > 0)
                                                ${{ number_format($reqTax, 2, '.', ',') }}
                                            @else
                                                <span class="text-amber-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right text-small font-bold text-text-primary">${{ number_format($reqTotal, 2, '.', ',') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="card text-center py-12">
                <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                <p class="text-text-muted">No hay requisiciones registradas</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requisitions->links() }}</div>

    {{-- Create Requisition Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                    <h2 class="text-h2 font-semibold text-text-primary">Nueva Requisición</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-md hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="createRequisition" class="p-5 space-y-5">
                    {{-- 1. Datos Generales --}}
                    <div class="bg-surface-main border border-border p-4 rounded-lg space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <label class="label">Proyecto *</label>
                                <x-custom-select wire:model="reqProjectId" :options="$projects->pluck('name', 'id')->toArray()" placeholder="Seleccionar proyecto..." />
                                @error('reqProjectId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-1">
                                <label class="label">Vendedor (Opcional)</label>
                                @php
                                    $vendorOptions = [];
                                    foreach($vendors as $vendor) {
                                        $vendorOptions[$vendor->id] = $vendor->name . ' (' . ($vendor->supplier->trade_name ?? 'Sin Proveedor') . ')';
                                    }
                                @endphp
                                <x-custom-select wire:model="reqVendorId" :options="$vendorOptions" placeholder="Vendedor..." />
                                @error('reqVendorId') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-1">
                                <label class="label">Fecha *</label>
                                <input wire:model="reqDate" type="date" class="input">
                                @error('reqDate') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-4">
                                <label class="label">Anotaciones</label>
                                <textarea wire:model="reqAnnotations" class="input" rows="2"
                                    placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                                @error('reqAnnotations') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 2. Captura de Productos --}}
                    <div>
                        <h3 class="text-small font-semibold text-text-primary mb-3">Productos solicitados</h3>

                        {{-- Formulario para añadir --}}
                        <div class="bg-primary-50 border border-primary-100 p-4 rounded-lg mb-4">
                            <div class="flex flex-col sm:flex-row gap-3 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs-fluid font-medium text-text-primary mb-1.5">Producto *</label>
                                    <input wire:model="itemName" type="text" class="input text-body" placeholder="Ej. Cemento Cruz Azul">
                                </div>
                                <div class="w-full sm:w-24">
                                    <label class="block text-xs-fluid font-medium text-text-primary mb-1.5">Cant. *</label>
                                    <input wire:model="itemQuantity" type="number" step="0.01" class="input text-body" placeholder="0.00">
                                </div>
                                <div class="w-full sm:w-32">
                                    <label class="block text-xs-fluid font-medium text-text-primary mb-1.5">Unidad *</label>
                                    <x-custom-select wire:model="itemUnit" :options="['pza' => 'Pieza', 'kg' => 'Kg', 'm' => 'Metro', 'm2' => 'm²', 'm3' => 'm³', 'lt' => 'Litro', 'bulto' => 'Bulto', 'rollo' => 'Rollo']" placeholder="Unidad" />
                                </div>
                                <div class="w-full sm:w-28">
                                    <label class="block text-xs-fluid font-medium text-text-primary mb-1.5">Precio U.</label>
                                    <input wire:model="itemPrice" type="number" step="0.01" class="input text-body" placeholder="$ 0.00">
                                </div>
                                <div class="w-full sm:w-auto">
                                    <button type="button" wire:click="addItem" class="btn-primary w-full sm:w-auto h-[42px] px-4 flex items-center justify-center">
                                        <i data-lucide="plus" class="w-4 h-4 sm:mr-1"></i> <span class="hidden sm:inline">Añadir</span>
                                    </button>
                                </div>
                            </div>
                            @error('itemName') <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tabla de Productos Agregados --}}
                        @if(count($items) > 0)
                            <div class="rounded-lg border border-border overflow-hidden">
                                <table class="w-full text-body">
                                    <thead>
                                        <tr class="bg-surface-main">
                                            <th class="text-left px-4 py-3 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Producto</th>
                                            <th class="text-center px-4 py-3 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Cant.</th>
                                            <th class="text-right px-4 py-3 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Precio</th>
                                            <th class="text-right px-4 py-3 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Subtotal</th>
                                            <th class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $i => $item)
                                            <tr class="border-t border-border/60 hover:bg-surface-main/50 transition-colors">
                                                <td class="px-4 py-3 font-medium text-text-primary">{{ $item['name'] }}</td>
                                                <td class="px-4 py-3 text-center text-text-secondary">{{ $item['quantity'] }} <span class="text-xs-fluid text-text-muted">{{ $item['unit'] }}</span></td>
                                                <td class="px-4 py-3 text-right text-text-secondary">${{ number_format($item['unit_price'], 2) }}</td>
                                                <td class="px-4 py-3 text-right font-medium text-text-primary">
                                                    ${{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" wire:click="removeItem({{ $i }})"
                                                        class="text-text-muted hover:text-danger p-1 rounded hover:bg-red-50 transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-border bg-surface-main">
                                            <td colspan="3"
                                                class="px-4 py-3 text-right text-small font-medium text-text-secondary">Total estimado:</td>
                                            <td class="px-4 py-3 text-right text-body font-bold text-text-primary">
                                                ${{ number_format(collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']), 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 border-2 border-dashed border-border rounded-lg bg-surface-main/50">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="package-search" class="w-6 h-6 text-text-muted"></i>
                                </div>
                                <p class="text-body font-medium text-text-primary mb-1">No hay productos</p>
                                <p class="text-xs-fluid text-text-muted">Utiliza el formulario superior para añadir artículos.</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary relative" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="createRequisition"
                                class="transition-opacity">Crear Requisición</span>
                            <span wire:loading wire:target="createRequisition"
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
                </form>
            </div>
        </div>
    @endif

    {{-- Reject Modal (RF-REQ-09: comentario obligatorio) --}}
    @if($showRejectModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showRejectModal', false)"></div>
                <div class="relative bg-surface-card rounded-xl shadow-xl border border-border w-full max-w-md">
                    <div class="px-5 py-4 border-b border-border">
                        <h2 class="text-h2 font-semibold text-text-primary">Rechazar Requisición</h2>
                        <p class="text-body text-text-muted">Indica el motivo del rechazo (obligatorio)</p>
                    </div>
                    <form wire:submit="confirmReject" class="p-5 space-y-4">
                        <div>
                            <label class="label">Motivo del rechazo *</label>
                            <textarea wire:model="rejectionComment" class="input" rows="3"
                                placeholder="Explica por qué esta requisición fue rechazada..."></textarea>
                            @error('rejectionComment') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-border">
                            <button type="button" wire:click="$set('showRejectModal', false)"
                                class="btn-secondary">Cancelar</button>
                            <button type="submit" class="btn-danger">
                                Confirmar Rechazo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

{{-- ═══════ PREVIEW MODAL ═══════ --}}
<div x-show="showPreviewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="display: none;">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden"
        x-transition>
        <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-surface-card">
            <h3 class="text-h3 font-semibold text-text-primary flex items-center gap-2">
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